<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Staff;
use App\Models\StudentAttendance;
use App\Models\StaffAttendance;
use App\Models\StudentPickup;
use App\Models\StudentEnrollment;
use App\Models\AcademicSession;
use App\Models\InstitutionSetting;
use App\Models\ExamRecord;
use App\Models\Invoice;
use App\Models\Institution;
use App\Models\Timetable;
use App\Services\NotificationService;
use App\Services\InAppNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AttendanceApiController extends Controller
{
    protected NotificationService $notificationService;
    protected InAppNotificationService $inAppNotifications;

    public function __construct(
        NotificationService $notificationService,
        InAppNotificationService $inAppNotifications
    ) {
        $this->notificationService = $notificationService;
        $this->inAppNotifications = $inAppNotifications;
    }

    public function store(Request $request)
    {
        if ($denied = $this->denyInvalidHardware($request)) {
            return $denied;
        }

        $request->validate([
            'uid' => 'required|string', 
            'device_id' => 'nullable|string',
            'timestamp' => 'nullable|date',
            'method' => 'nullable|string',
            'purpose' => 'nullable|string'
        ]);

        $uid = trim($request->uid);
        $purpose = $request->purpose ?? 'attendance'; 
        $method = $request->input('method', 'rfid'); 

        if ($purpose === 'fee_check') {
            return $this->handleFeeCheck($uid);
        }
        if ($purpose === 'pickup') {
            return $this->handlePickup($uid, $request->device_id);
        }
        if ($purpose === 'report_card') {
            return $this->handleReportCard($uid);
        }
        if ($purpose === 'identity_check') {
            return $this->handleIdentityCheck($uid);
        }

        return $this->handleAttendanceLogging($request, $uid, $method);
    }

    /**
     * Bulk attendance scan (offline sync from hardware terminals).
     */
    public function bulkStore(Request $request)
    {
        if ($denied = $this->denyInvalidHardware($request)) {
            return $denied;
        }

        $request->validate([
            'scans' => 'required|array|min:1|max:500',
            'scans.*.uid' => 'required|string',
            'scans.*.timestamp' => 'nullable|date',
            'scans.*.method' => 'nullable|string',
            'scans.*.purpose' => 'nullable|string',
            'device_id' => 'nullable|string',
        ]);

        $results = [];

        foreach ($request->scans as $index => $scan) {
            $single = new Request(array_merge($scan, [
                'device_id' => $scan['device_id'] ?? $request->device_id,
            ]));
            $single->headers->replace($request->headers->all());

            $response = $this->store($single);
            $body = json_decode($response->getContent(), true);

            $results[] = [
                'index' => $index,
                'uid' => $scan['uid'],
                'success' => $response->getStatusCode() < 400,
                'response' => $body,
            ];
        }

        $successCount = collect($results)->where('success', true)->count();

        return response()->json([
            'success' => true,
            'processed' => count($results),
            'succeeded' => $successCount,
            'failed' => count($results) - $successCount,
            'results' => $results,
        ]);
    }

    /**
     * Fetch Today's Attendance List for the POS/Mobile App
     * SCOPED: Students only see their own. Parents see kids. Teachers see their classes.
     */
    public function getTodayScans(Request $request)
    {
        $user = Auth::guard('sanctum')->user() ?? Auth::user();
        $isHardware = $this->isValidHardwareRequest($request);

        if (!$user && !$isHardware) {
            return response()->json(['message' => 'Unauthorized Access'], 401);
        }

        $today = Carbon::today()->toDateString();
        
        $query = StudentAttendance::with('student:id,first_name,last_name,student_photo,admission_number')
            ->where('attendance_date', $today)
            ->latest('updated_at');

        if ($isHardware) {
            $institutionId = $request->header('X-Institution-Id');
            if (!$institutionId) {
                return response()->json(['message' => 'X-Institution-Id header required'], 400);
            }
            $query->where('institution_id', $institutionId);
        } elseif ($user) {
        // --- SMART SCOPING BASED ON USER ROLE ---
            // STRICT CHECK: Safely checks roles AND relationships
            if ($user->hasRole('Student') || $user->student) {
                // Students only see themselves
                $studentId = $user->student->id ?? 0;
                $query->where('student_id', $studentId);
            } elseif ($user->hasRole('Guardian') || \App\Models\StudentParent::where('user_id', $user->id)->exists()) {
                // Parents only see their children
                $parent = \App\Models\StudentParent::where('user_id', $user->id)->first();
                $childIds = \App\Models\Student::where('parent_id', $parent->id ?? 0)->pluck('id');
                $query->whereIn('student_id', $childIds);
            } elseif ($user->hasRole('Teacher') && $user->staff) {
                // Teachers only see their assigned classes
                $staffId = $user->staff->id;
                $classIds = \App\Models\ClassSection::where('staff_id', $staffId)->pluck('id');
                $query->whereIn('class_section_id', $classIds);
            } elseif (!$user->hasRole(['Super Admin', 'Head Officer', 'School Admin'])) {
                // Fallback: block any rogue non-admin roles
                $query->where('id', 0); 
            }
        }

        $records = $query->get()->map(function($att) {
            $isCheckOut = $att->check_out !== null;
            
            $timeIn = $att->check_in ? Carbon::parse($att->check_in)->format('h:i A') : '--:--';
            $timeOut = $att->check_out ? Carbon::parse($att->check_out)->format('h:i A') : '--:--';
            $time = $isCheckOut ? $timeOut : $timeIn; 
            
            $admNo = $att->student->admission_number ?? 'N/A';
            
            return [
                'id' => $att->id,
                'student_name' => ($att->student->full_name ?? 'Unknown') . ' (' . $admNo . ')',
                'admission_no' => $admNo,
                'photo' => $att->student->student_photo ? asset('storage/'.$att->student->student_photo) : null,
                'time' => $time,
                'time_in' => $timeIn,
                'time_out' => $timeOut,
                'action' => $isCheckOut ? 'Departure' : 'Arrival',
                'status' => ucfirst($att->status),
                'status_color' => $att->status === 'late' ? '#F59E0B' : '#10B981',
                'action_color' => $isCheckOut ? '#6366F1' : '#3B82F6',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $records
        ]);
    }

    private function handleAttendanceLogging(Request $request, $uid, $method)
    {
        $scanTime = $request->timestamp ? Carbon::parse($request->timestamp) : Carbon::now();
        $date = $scanTime->toDateString();
        $time = $scanTime->format('H:i:s');
        
        $possibleUids = $this->getPossibleUids($uid);

        $student = $this->findStudentByScanUids($possibleUids);

        if ($student) {
            return $this->processStudentAttendance($student, $date, $time, $scanTime, $method);
        }

        $staff = $this->findStaffByScanUids($possibleUids);

        if ($staff) {
            return $this->processStaffAttendance($staff, $date, $time, $scanTime, $method);
        }

        Log::warning("Universal Scan Failed: Unknown UID - {$uid}");
        return response()->json(['status' => 'error', 'success' => false, 'message' => 'Unknown Tag or Card'], 404);
    }

    private function processStudentAttendance($student, $date, $time, $scanTime, $method)
    {
        if ($student->status !== 'active') {
            return response()->json(['status' => 'error', 'success' => false, 'message' => 'Student account is inactive.'], 403);
        }

        $institutionId = $student->institution_id;
        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();

        if (!$session) {
            return response()->json(['status' => 'error', 'success' => false, 'message' => 'No active academic session'], 400);
        }

        $enrollment = StudentEnrollment::where('student_id', $student->id)
            ->where('academic_session_id', $session->id)
            ->where('status', 'active')
            ->first();

        if (!$enrollment) {
            return response()->json(['status' => 'error', 'success' => false, 'message' => 'Student is not enrolled in the active session.'], 403);
        }

        $lateMargin = (int) InstitutionSetting::get($institutionId, 'late_margin_time', 1);
        $cooldownMinutes = (int) InstitutionSetting::get($institutionId, 'double_tap_wait_time', 15);
        $isSubjectWise = $this->isSubjectWiseInstitution($institutionId);

        $gateContext = $this->resolveGateAttendanceStatus(
            $enrollment->class_section_id,
            $institutionId,
            $scanTime,
            $lateMargin
        );

        $status = $gateContext['status'];
        $subjectId = $isSubjectWise ? ($gateContext['subject_id'] ?? null) : null;
        $slotLabel = $gateContext['slot_label'] ?? null;

        $attendanceQuery = StudentAttendance::where('student_id', $student->id)
            ->where('attendance_date', $date)
            ->where('class_section_id', $enrollment->class_section_id);

        if ($isSubjectWise && $subjectId) {
            $attendanceQuery->where('subject_id', $subjectId);
        } else {
            $attendanceQuery->whereNull('subject_id');
        }

        $attendance = $attendanceQuery->first();
        $action = '';

        if (!$attendance) {
            StudentAttendance::create([
                'institution_id' => $institutionId,
                'academic_session_id' => $session->id,
                'class_section_id' => $enrollment->class_section_id,
                'student_id' => $student->id,
                'subject_id' => $subjectId,
                'attendance_date' => $date,
                'status' => $status,
                'check_in' => $time,
                'method' => $method,
            ]);
            $action = 'arrival';
        } else {
            $cleanCheckInTime = Carbon::parse($attendance->check_in)->format('H:i:s');
            $checkInTime = Carbon::parse($date . ' ' . $cleanCheckInTime);

            if ($scanTime->lt($checkInTime)) {
                return response()->json(['status' => 'error', 'message' => 'Check-out time cannot be before check-in time.'], 400);
            }

            if ($scanTime->lt($checkInTime->copy()->addMinutes($cooldownMinutes))) {
                return response()->json(['status' => 'ignored', 'message' => "Cooldown active. Please wait {$cooldownMinutes} minutes."], 200);
            }

            $attendance->update(['check_out' => $time]);
            $action = 'departure';
            $status = $attendance->status;
        }

        $this->notifyParent($student, $action, $scanTime->format('h:i A'), $institutionId);

        $admNo = $student->admission_number ?? 'N/A';
        $statusColor = match ($status) {
            'present' => '#10B981',
            'late' => '#F59E0B',
            'absent' => '#DC2626',
            default => '#6B7280',
        };

        return response()->json([
            'status' => 'success',
            'success' => true,
            'action' => $action,
            'type' => 'student',
            'name' => $student->first_name . ' ' . $student->last_name . ' (' . $admNo . ')',
            'time' => $scanTime->format('h:i A'),
            'punctuality' => $status,
            'subject' => $slotLabel,
            'ui_color' => $statusColor,
            'message' => ($action === 'arrival' ? 'Welcome' : 'Goodbye') . ', ' . $student->first_name . '!',
        ], 200);
    }

    private function isSubjectWiseInstitution(int $institutionId): bool
    {
        $institution = Institution::find($institutionId);
        if (!$institution) {
            return false;
        }
        $type = is_object($institution->type) ? $institution->type->value : $institution->type;

        return in_array($type, ['university', 'vocational', 'lmd'], true);
    }

    /**
     * Determine present / late / absent from timetable slot and late margin.
     */
    private function resolveGateAttendanceStatus(
        int $classSectionId,
        int $institutionId,
        Carbon $scanTime,
        int $lateMarginMinutes
    ): array {
        $day = strtolower($scanTime->format('l'));
        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();

        $slotsQuery = Timetable::with('subject')
            ->where('class_section_id', $classSectionId)
            ->whereRaw('LOWER(day_of_week) = ?', [$day])
            ->orderBy('start_time');

        if ($session) {
            $slotsQuery->where('academic_session_id', $session->id);
        }

        $slots = $slotsQuery->get();
        $dateStr = $scanTime->format('Y-m-d');

        // Active class period
        foreach ($slots as $slot) {
            $start = Carbon::parse("{$dateStr} {$slot->start_time}");
            $end = Carbon::parse("{$dateStr} {$slot->end_time}");
            if ($scanTime->between($start, $end)) {
                $lateThreshold = $start->copy()->addMinutes($lateMarginMinutes);
                return [
                    'status' => $scanTime->lte($lateThreshold) ? 'present' : 'late',
                    'subject_id' => $slot->subject_id,
                    'slot_label' => $slot->subject?->name,
                ];
            }
        }

        // After a class ended → absent for the most recently ended slot
        $lastEnded = null;
        foreach ($slots as $slot) {
            $end = Carbon::parse("{$dateStr} {$slot->end_time}");
            if ($scanTime->gt($end)) {
                $lastEnded = $slot;
            }
        }

        if ($lastEnded) {
            return [
                'status' => 'absent',
                'subject_id' => $lastEnded->subject_id,
                'slot_label' => $lastEnded->subject?->name,
            ];
        }

        // No timetable — fall back to school start time
        $schoolStartTimeStr = InstitutionSetting::get($institutionId, 'school_start_time', '08:00');
        try {
            $parsedStartTime = Carbon::parse($schoolStartTimeStr);
            $expectedTime = $scanTime->copy()->setTime($parsedStartTime->hour, $parsedStartTime->minute, 0);
            $expectedTime->addMinutes($lateMarginMinutes);
            $isLate = $scanTime->gt($expectedTime);
        } catch (\Exception $e) {
            $isLate = $scanTime->format('H:i') > '08:00';
        }

        return [
            'status' => $isLate ? 'late' : 'present',
            'subject_id' => null,
            'slot_label' => null,
        ];
    }

    private function processStaffAttendance($staff, $date, $time, $scanTime, $method)
    {
        $institutionId = $staff->institution_id;
        
        $schoolStartTimeStr = InstitutionSetting::get($institutionId, 'school_start_time', '08:00');
        $lateMargin = (int) InstitutionSetting::get($institutionId, 'late_margin_time', 2); 
        $cooldownMinutes = (int) InstitutionSetting::get($institutionId, 'double_tap_wait_time', 15);

        try {
            $parsedStartTime = Carbon::parse($schoolStartTimeStr);
            $expectedTime = $scanTime->copy()->setTime($parsedStartTime->hour, $parsedStartTime->minute, 0);
            $expectedTime->addMinutes($lateMargin)->addSeconds(59);
            $isLate = $scanTime->gt($expectedTime);
        } catch (\Exception $e) {
            $isLate = $scanTime->format('H:i') > '08:00'; 
        }
        
        $status = $isLate ? 'late' : 'present';

        $attendance = StaffAttendance::where('staff_id', $staff->id)
            ->where('attendance_date', $date)
            ->first();

        $action = '';

        if (!$attendance) {
            StaffAttendance::create([
                'institution_id' => $institutionId,
                'staff_id' => $staff->id,
                'attendance_date' => $date,
                'status' => $status,
                'check_in' => $time,
                'method' => $method,
            ]);
            $action = 'arrival';
        } else {
            $cleanCheckInTime = Carbon::parse($attendance->check_in)->format('H:i:s');
            $checkInTime = Carbon::parse($date . ' ' . $cleanCheckInTime);
            
            if ($scanTime->lt($checkInTime)) {
                return response()->json(['status' => 'error', 'message' => 'Check-out time cannot be before check-in time.'], 400);
            }

            if ($scanTime->lt($checkInTime->copy()->addMinutes($cooldownMinutes))) {
                return response()->json(['status' => 'ignored', 'message' => "Cooldown active. Please wait {$cooldownMinutes} minutes."], 200);
            }

            $attendance->update(['check_out' => $time]);
            $action = 'departure';
        }

        return response()->json([
            'status' => 'success',
            'success' => true,
            'action' => $action,
            'type' => 'staff',
            'name' => optional($staff->user)->name ?? 'Staff Member',
            'time' => $scanTime->format('h:i A'),
            'punctuality' => $status,
            'ui_color' => $status === 'late' ? '#F59E0B' : '#10B981',
            'message' => ($action === 'arrival' ? 'Check-in recorded' : 'Check-out recorded') . ' for ' . (optional($staff->user)->name ?? 'staff'),
        ], 200);
    }

    private function notifyParent($student, $action, $timeStr, $institutionId)
    {
        $parent = $student->parent;
        if (!$parent) return;

        $phoneField = ($parent->primary_guardian ?? 'father') . '_phone';
        $phone = $parent->$phoneField ?? $parent->father_phone ?? $parent->mother_phone ?? $parent->guardian_phone;

        if (!$phone) return;

        $eventKey = $action === 'arrival' ? 'student_arrival' : 'student_departure';
        
        $data = [
            'StudentName' => $student->first_name,
            'ParentName' => $parent->father_name ?? 'Parent',
            'Time' => $timeStr,
            'Date' => now()->format('d M Y'),
            'SchoolName' => optional($student->institution)->name ?? 'School'
        ];

        $this->notificationService->sendNotificationEvent($eventKey, $phone, $data, $institutionId, 'whatsapp');
        $this->notificationService->sendNotificationEvent($eventKey, $phone, $data, $institutionId, 'sms');
    }

    private function handleFeeCheck($uid)
    {
        $possibleUids = $this->getPossibleUids($uid);

        $student = $this->findStudentByScanUids($possibleUids);
        
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student Card Not Found.'], 404);
        }

        $student->load(['enrollments.classSection']);

        $invoices = Invoice::where('student_id', $student->id)->get();
        $unpaidInvoices = $invoices->whereIn('status', ['unpaid', 'partial', 'overdue']);
        
        $totalDue = $unpaidInvoices->sum(fn($inv) => $inv->total_amount - $inv->paid_amount);
        $totalBalance = $invoices->sum('total_amount');
        $paidBalance = $invoices->sum('paid_amount');
        $remainingBalance = max(0, $totalBalance - $paidBalance);

        $lastPayment = \App\Models\Payment::whereHas('invoice', fn($q) => $q->where('student_id', $student->id))
            ->latest('payment_date')->first();

        $invoiceBreakdown = $unpaidInvoices->map(function($inv) {
            return [
                'invoice_number' => $inv->invoice_number,
                'amount_due' => number_format($inv->total_amount - $inv->paid_amount, 2),
                'due_date' => $inv->due_date->format('d M, Y'),
                'status' => ucfirst($inv->status)
            ];
        })->values();

        $admNo = $student->admission_number ?? 'N/A';
        $activeEnrollment = $student->enrollments->where('status', 'active')->first();
        $className = $activeEnrollment->classSection->name ?? 'N/A';

        $dataPayload = [
            'student_name' => $student->full_name . ' (' . $admNo . ')',
            'admission_number' => $admNo,
            'class_name' => $className,
            'total_balance' => number_format($totalBalance, 2),
            'paid_balance' => number_format($paidBalance, 2),
            'remaining_balance' => number_format($remainingBalance, 2),
            'last_payment_date' => $lastPayment ? Carbon::parse($lastPayment->payment_date)->format('d M, Y') : 'N/A',
            'last_payment_amount' => $lastPayment ? number_format($lastPayment->amount, 2) : '0.00',
            'invoices' => $invoiceBreakdown, 
            'color' => $totalDue > 0 ? '#dc2626' : '#16a34a'
        ];

        if ($totalDue > 0) {
            return response()->json([
                'success' => false, 
                'message' => "Payment Threshold Not Met.",
                'data' => $dataPayload
            ]);
        }

        return response()->json([
            'success' => true, 
            'message' => 'Payment Threshold Met. Account Cleared.',
            'data' => $dataPayload
        ]);
    }

    private function handlePickup($uid, $deviceId)
    {
        $possibleUids = $this->getPossibleUids($uid);

        if (str_starts_with($uid, 'PKUP-') || str_starts_with($uid, 'QR-')) {
            $pickup = StudentPickup::with('student')->where('token', $uid)->first();

            if (!$pickup) return response()->json(['success' => false, 'message' => 'pickup_invalid_qr'], 404);
            
            if (in_array($pickup->status, ['scanned', 'completed', 'approved'])) {
                return response()->json(['success' => false, 'message' => 'pickup_already_used'], 400);
            }

            $pickup->update(['status' => 'scanned', 'scanned_at' => now(), 'scanned_by_device' => $deviceId]);
            $this->notifyTeacher($pickup);
            
            $admNo = $pickup->student->admission_number ?? 'N/A';

            return response()->json([
                'success' => true, 
                'message' => 'pickup_wait_for_teacher',
                'data' => [
                    'student_name' => $pickup->student->full_name . ' (' . $admNo . ')',
                    'color' => '#F59E0B'
                ]
            ]);
        }

        $student = $this->findStudentByScanUids($possibleUids);

        if ($student) {
            $pickup = StudentPickup::create([
                'institution_id' => $student->institution_id,
                'student_id' => $student->id,
                'requested_by' => 'NFC Gate Tap', 
                'status' => 'scanned',
                'scanned_at' => now(),
                'scanned_by_device' => $deviceId,
                'token' => 'NFC-' . \Illuminate\Support\Str::random(8),
            ]);

            $this->notifyTeacher($pickup);
            $admNo = $student->admission_number ?? 'N/A';

            return response()->json([
                'success' => true, 
                'message' => 'pickup_wait_for_teacher',
                'data' => [
                    'student_name' => $student->full_name . ' (' . $admNo . ')',
                    'color' => '#F59E0B'
                ]
            ]);
        }

        return response()->json(['success' => false, 'message' => 'pickup_unrecognized_card'], 404);
    }

    private function handleReportCard($uid)
    {
        $possibleUids = $this->getPossibleUids($uid);

        $student = $this->findStudentByScanUids($possibleUids);

        if (!$student) return response()->json(['success' => false, 'message' => 'Student Not Found.'], 404);

        $institutionId = $student->institution_id;
        $admNo = $student->admission_number ?? 'N/A';
        
        $isBlocked = InstitutionSetting::get($institutionId, 'block_reports_on_debt', 0);
        if ($isBlocked) {
            $unpaid = Invoice::where('student_id', $student->id)->whereIn('status', ['unpaid', 'partial', 'overdue'])->sum(DB::raw('total_amount - paid_amount'));
            if ($unpaid > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Financial Block: Outstanding balance of $" . number_format($unpaid, 2),
                    'data' => [
                        'student_name' => $student->full_name . ' (' . $admNo . ')',
                        'color' => '#dc2626'
                    ]
                ], 200); 
            }
        }

        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();
        
        $records = ExamRecord::with(['subject', 'exam.academicSession'])
            ->where('student_id', $student->id)
            ->whereHas('exam', fn ($q) => $q
                ->where('academic_session_id', $session->id ?? 0)
                ->where('status', 'published'))
            ->latest('updated_at')
            ->get(); 

        $formattedRecords = $records->map(function($r) {
            return [
                'subject' => $r->subject->name ?? 'N/A',
                'marks' => $r->marks_obtained,
            ];
        });

        $firstRecord = $records->first();
        $examDetails = [
            'name' => $firstRecord->exam->name ?? 'N/A',
            'year' => $firstRecord->exam->academicSession->name ?? 'N/A',
            'category' => $firstRecord->exam->category ? ucwords(str_replace('_', ' ', $firstRecord->exam->category)) : 'N/A',
        ];

        return response()->json([
            'success' => true, 
            'message' => 'Report Card Access Granted & Digitally Signed!',
            'data' => [
                'student_name' => $student->full_name . ' (' . $admNo . ')',
                'exam_details' => $examDetails,
                'recent_marks' => $formattedRecords,
                'color' => '#16a34a'
            ]
        ]);
    }

    private function handleIdentityCheck($uid)
    {
        $possibleUids = $this->getPossibleUids($uid);
        $student = $this->findStudentByScanUids($possibleUids);

        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found.'], 404);
        }

        $student->load(['parent', 'classSection.gradeLevel', 'enrollments.classSection.gradeLevel']);

        $className = 'N/A';
        $sectionName = 'N/A';
        $enrollment = $student->enrollments()->where('status', 'active')->latest()->first();

        if ($enrollment?->classSection) {
            $sectionName = $enrollment->classSection->name ?? 'N/A';
            $className = $enrollment->classSection->gradeLevel->name ?? 'N/A';
        } elseif ($student->classSection) {
            $sectionName = $student->classSection->name ?? 'N/A';
            $className = $student->classSection->gradeLevel->name ?? 'N/A';
        }

        $parent = $student->parent;
        $photoUrl = $student->student_photo ? asset('storage/' . $student->student_photo) : null;

        return response()->json([
            'success' => true,
            'message' => 'Student identity verified.',
            'data' => [
                'student_id' => $student->id,
                'full_name' => $student->full_name,
                'admission_number' => $student->admission_number,
                'roll_number' => $student->roll_number,
                'gender' => $student->gender,
                'dob' => $student->dob?->format('Y-m-d'),
                'blood_group' => $student->blood_group,
                'class_name' => $className,
                'section_name' => $sectionName,
                'status' => $student->status,
                'mobile_number' => $student->mobile_number,
                'email' => $student->email,
                'photo_url' => $photoUrl,
                'parent_name' => $parent?->father_name ?? $parent?->mother_name,
                'parent_phone' => $parent?->father_phone ?? $parent?->mother_phone,
                'current_address' => $student->current_address,
                'color' => '#2563eb',
            ],
        ]);
    }

    private function notifyTeacher($pickup)
    {
        try {
            $this->inAppNotifications->notifyPickupScanned($pickup);
            $this->inAppNotifications->notifyClassTeacherPickup($pickup);
        } catch (\Throwable $e) {
            Log::error('Pickup teacher notification failed: ' . $e->getMessage());
        }

        $enrollment = StudentEnrollment::with(['classSection.classTeacher.user'])
            ->where('student_id', $pickup->student_id)
            ->where('status', 'active')
            ->latest()
            ->first();

        if ($enrollment && $enrollment->classSection && $enrollment->classSection->classTeacher) {
            $teacher = $enrollment->classSection->classTeacher->user;

            if ($teacher && $teacher->phone) {
                $message = __('notifications.teacher_pickup_alert', ['student_name' => $pickup->student->full_name]);
                if ($message === 'notifications.teacher_pickup_alert') {
                    $message = "🚨 Student Pickup Alert: {$pickup->student->full_name}'s parent is waiting at the gate.";
                }
                $this->notificationService->performSend($teacher->phone, $message, $pickup->institution_id, false, 'whatsapp');
            }
        }
    }

    /**
     * Fetch Today's Absentees for Teacher Dashboard
     * SCOPED: Block Students & Parents
     */
    public function getTodayAbsentees(Request $request)
    {
        $user = Auth::guard('sanctum')->user() ?? Auth::user();
        
        if (!$user || $user->hasRole(['Student', 'Guardian'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. Staff only.'], 403);
        }

        $query = StudentAttendance::with('student:id,first_name,last_name,student_photo,admission_number')
            ->whereDate('attendance_date', Carbon::today()->toDateString())
            ->where('status', 'absent');

        // Scope to teacher's classes
        if ($user->hasRole('Teacher') && $user->staff) {
            $staffId = $user->staff->id;
            $classIds = \App\Models\ClassSection::where('staff_id', $staffId)->pluck('id');
            $query->whereIn('class_section_id', $classIds);
        }

        $absentees = $query->get()->map(function($att) {
            $admNo = $att->student->admission_number ?? 'N/A';
            return [
                'id' => $att->student->id,
                'student_name' => ($att->student->full_name ?? 'Unknown') . ' (' . $admNo . ')',
                'admission_no' => $admNo,
                'photo' => $att->student->student_photo ? asset('storage/'.$att->student->student_photo) : null,
            ];
        });
            
        return response()->json([
            'success' => true, 
            'data' => $absentees
        ]);
    }

    /**
     * Fetch Absentee Report Grouped by Class Sections and Dates
     * SCOPED: Block Students & Parents
     */
    public function getTeacherClassAbsentees(Request $request)
    {
        $user = Auth::guard('sanctum')->user() ?? Auth::user();
        
        Log::info('--- START getTeacherClassAbsentees ---', ['user_id' => $user->id ?? 'guest']);
        
        // Strict Guard: Prevent Students/Parents from accessing staff data
        if (!$user || $user->hasRole(['Student', 'Guardian'])) {
            Log::warning('Unauthorized access attempt in getTeacherClassAbsentees', [
                'roles' => $user ? $user->getRoleNames() : 'none', 
            ]);
            return response()->json(['success' => false, 'message' => 'unauthorized_access: Staff only'], 403);
        }

        $dayOfWeek = strtolower(now()->format('l'));
        
        $institution = $user->institute;
        $instType = $institution ? $institution->type : 'primary';
        $isSubjectWise = in_array(is_object($instType) ? $instType->value : $instType, ['university', 'vocational', 'lmd']);
        
        $allAssignedClassIds = [];
        
        // --- 1. ADMIN BYPASS: See all classes in their institution ---
        if ($user->hasRole(['Super Admin', 'Head Officer', 'School Admin'])) {
            $query = \App\Models\ClassSection::where('is_active', true);
            
            if (!$user->hasRole('Super Admin') && $user->institute_id) {
                $query->where('institution_id', $user->institute_id);
            }
            
            $allAssignedClassIds = $query->pluck('id')->toArray();
        } 
        // --- 2. TEACHER / STAFF: See only assigned classes ---
        elseif ($user->staff) {
            $staffId = $user->staff->id;
            
            $homeroomIds = \App\Models\ClassSection::where('staff_id', $staffId)->pluck('id')->toArray();
            
            $timetableIds = \App\Models\Timetable::where('teacher_id', $staffId)
                ->where('day_of_week', $dayOfWeek)
                ->pluck('class_section_id')->toArray();
                
            $allocatedIds = \App\Models\ClassSubject::where('teacher_id', $staffId)
                ->pluck('class_section_id')->toArray();

            $allAssignedClassIds = array_unique(array_merge($homeroomIds, $timetableIds, $allocatedIds));
        } else {
            return response()->json(['success' => false, 'message' => 'unauthorized_access'], 403);
        }

        $sectionsQuery = \App\Models\ClassSection::with('gradeLevel')->where('is_active', true)
            ->whereIn('id', $allAssignedClassIds);
            
        if ($user->institute_id && !$user->hasRole('Super Admin')) {
            $sectionsQuery->where('institution_id', $user->institute_id);
        }
        
        $sections = $sectionsQuery->get();

        $currentSession = \App\Models\AcademicSession::where('is_current', true);
        if ($user->institute_id && !$user->hasRole('Super Admin')) {
            $currentSession->where('institution_id', $user->institute_id);
        }
        $session = $currentSession->first();
        $sessionId = $session ? $session->id : null;

        // Fetch backward a specific number of days
        $daysToFetch = (int) $request->input('days', 3); 
        $dates = [];
        for ($i = 0; $i < $daysToFetch; $i++) {
            $dates[] = \Carbon\Carbon::today()->subDays($i)->toDateString();
        }

        $report = [];

        // FIXED LOGIC: Wrap the sections loop inside the dates loop
        foreach ($dates as $date) {
            $dateObj = \Carbon\Carbon::parse($date);
            $isToday = $dateObj->isToday();
            $dateLabel = $isToday ? 'Today (' . $dateObj->format('d M') . ')' : $dateObj->format('l, d M');
            $daySections = [];

            foreach ($sections as $section) {
                $enrollmentQuery = \App\Models\StudentEnrollment::with(['student.parent'])
                    ->whereHas('student')
                    ->where('class_section_id', $section->id)
                    ->where('status', 'active');

                if ($sessionId) {
                    $enrollmentQuery->where('academic_session_id', $sessionId);
                }

                $enrollments = $enrollmentQuery->get();

                $studentIds = $enrollments->pluck('student_id')->toArray();
                $totalClass = count($studentIds);

                $attendances = \App\Models\StudentAttendance::whereIn('student_id', $studentIds)
                    ->where('attendance_date', $date)
                    ->get()
                    ->keyBy('student_id');

                $presentCount = 0;
                $absenteesList = [];

                foreach ($enrollments as $enrollment) {
                    $student = $enrollment->student;
                    if (!$student) continue;

                    $attendance = $attendances->get($student->id);

                    if ($attendance && in_array($attendance->status, ['present', 'late'])) {
                        $presentCount++;
                    } else {
                        $parent = $student->parent;
                        $phone = $parent?->father_phone
                            ?? $parent?->mother_phone
                            ?? $parent?->guardian_phone
                            ?? $student->mobile_number
                            ?? 'N/A';
                        $parentName = $parent?->father_name
                            ?? $parent?->mother_name
                            ?? $parent?->guardian_name
                            ?? 'N/A';
                        
                        $firstName = $student->first_name ?? '';
                        $lastName = $student->last_name ?? '';
                        $admNo = $student->admission_number ?? 'N/A';
                        $fullName = trim("{$firstName} {$lastName}");
                        if (empty($fullName)) $fullName = 'Unknown Student';

                        $absenteesList[] = [
                            'student_id' => $student->id,
                            'student_name' => "{$fullName} ({$admNo})",
                            'parent_name' => $parentName,
                            'parent_phone' => $phone,
                        ];
                    }
                }

                // Append if there are absentees, or if it is "Today" we show the overall report regardless
                if ($totalClass > 0 && ($totalClass - $presentCount > 0 || $isToday)) {
                    $daySections[] = [
                        'class_name' => $section->gradeLevel->name ?? 'N/A', 
                        'section_name' => $section->name ?? 'Unknown Section',
                        'total_class' => $totalClass,
                        'total_present' => $presentCount,
                        'total_absent' => $totalClass - $presentCount,
                        'absentees' => $absenteesList,
                        'attendance_type' => $isSubjectWise ? 'subject' : 'daily'
                    ];
                }
            }

            // CRITICAL FIX: Append the populated daily groupings to the main report array 
            if (!empty($daySections)) {
                $report[] = [
                    'date' => $date,
                    'label' => $dateLabel,
                    'is_today' => $isToday,
                    'sections' => $daySections
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    /**
     * Manually notify parents of absent students (SMS / WhatsApp).
     */
    public function notifyAbsentStudents(Request $request)
    {
        $user = Auth::guard('sanctum')->user() ?? Auth::user();

        if (!$user || $user->hasRole(['Student', 'Guardian'])) {
            return response()->json(['success' => false, 'message' => 'unauthorized_access: Staff only'], 403);
        }

        $request->validate([
            'student_ids' => 'required|array|min:1|max:100',
            'student_ids.*' => 'integer|exists:students,id',
            'date' => 'nullable|date',
            'channels' => 'nullable|array|min:1',
            'channels.*' => 'in:sms,whatsapp',
        ]);

        $date = $request->input('date', Carbon::today()->toDateString());
        $channels = $request->input('channels', ['sms', 'whatsapp']);
        $channels = array_values(array_unique(array_intersect($channels, ['sms', 'whatsapp'])));

        if (empty($channels)) {
            return response()->json([
                'success' => false,
                'message' => 'Select at least one channel: sms or whatsapp.',
            ], 422);
        }

        $assignedClassIds = $this->resolveAssignedClassIdsForUser($user);

        if (empty($assignedClassIds) && !$user->hasRole(['Super Admin', 'Head Officer', 'School Admin'])) {
            return response()->json(['success' => false, 'message' => 'No assigned classes found.'], 403);
        }

        $results = [];
        $sentCount = 0;
        $failedCount = 0;

        foreach ($request->student_ids as $studentId) {
            $student = Student::with(['parent', 'institution', 'enrollments'])->find($studentId);

            if (!$student) {
                $results[] = ['student_id' => $studentId, 'success' => false, 'message' => 'Student not found.'];
                $failedCount++;
                continue;
            }

            if (!$this->teacherCanAccessStudent($user, $student, $assignedClassIds)) {
                $results[] = ['student_id' => $studentId, 'success' => false, 'message' => 'Not authorized for this student.'];
                $failedCount++;
                continue;
            }

            $parentPhone = $student->parent?->father_phone
                ?? $student->parent?->mother_phone
                ?? $student->parent?->guardian_phone;

            if (!$parentPhone) {
                $results[] = [
                    'student_id' => $studentId,
                    'student_name' => $student->full_name,
                    'success' => false,
                    'message' => 'No parent phone number on file.',
                ];
                $failedCount++;
                continue;
            }

            $institutionId = $student->institution_id;
            $eventKey = 'student_absent';
            $payload = [
                'StudentName' => $student->full_name,
                'Date' => Carbon::parse($date)->format('d/m/Y'),
                'SchoolName' => $student->institution?->name ?? 'School',
            ];

            $channelsSent = [];
            $channelErrors = [];

            foreach ($channels as $channel) {
                $response = $this->notificationService->sendNotificationEvent(
                    $eventKey,
                    $parentPhone,
                    $payload,
                    $institutionId,
                    $channel
                );

                if (($response['success'] ?? false) === true) {
                    $channelsSent[] = $channel;
                } else {
                    $channelErrors[] = $channel . ': ' . ($response['message'] ?? 'Failed');
                }
            }

            if (!empty($channelsSent)) {
                $sentCount++;
                $results[] = [
                    'student_id' => $studentId,
                    'student_name' => $student->full_name,
                    'success' => true,
                    'channels' => $channelsSent,
                    'message' => 'Notification sent via ' . implode(', ', $channelsSent) . '.',
                ];
            } else {
                $failedCount++;
                $results[] = [
                    'student_id' => $studentId,
                    'student_name' => $student->full_name,
                    'success' => false,
                    'message' => !empty($channelErrors)
                        ? implode(' | ', $channelErrors)
                        : 'No active SMS/WhatsApp template for absence alerts.',
                ];
            }
        }

        return response()->json([
            'success' => $sentCount > 0,
            'sent' => $sentCount,
            'failed' => $failedCount,
            'results' => $results,
            'message' => $sentCount > 0
                ? "Notifications sent for {$sentCount} student(s)."
                : 'No notifications were sent.',
        ], $sentCount > 0 ? 200 : 422);
    }

    private function resolveAssignedClassIdsForUser($user): array
    {
        if ($user->hasRole(['Super Admin', 'Head Officer', 'School Admin'])) {
            $query = \App\Models\ClassSection::where('is_active', true);
            if (!$user->hasRole('Super Admin') && $user->institute_id) {
                $query->where('institution_id', $user->institute_id);
            }
            return $query->pluck('id')->all();
        }

        if (!$user->hasRole('Teacher') || !$user->staff) {
            return [];
        }

        $staffId = $user->staff->id;
        $dayOfWeek = strtolower(now()->format('l'));

        $homeroomIds = \App\Models\ClassSection::where('staff_id', $staffId)->pluck('id')->all();
        $timetableIds = Timetable::where('teacher_id', $staffId)
            ->where('day_of_week', $dayOfWeek)
            ->pluck('class_section_id')->all();
        $allocatedIds = \App\Models\ClassSubject::where('teacher_id', $staffId)
            ->pluck('class_section_id')->all();

        return array_values(array_unique(array_merge($homeroomIds, $timetableIds, $allocatedIds)));
    }

    private function teacherCanAccessStudent($user, Student $student, array $assignedClassIds): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ($user->institute_id && $student->institution_id != $user->institute_id) {
            return false;
        }

        if ($user->hasRole(['Head Officer', 'School Admin'])) {
            return true;
        }

        return StudentEnrollment::where('student_id', $student->id)
            ->where('status', 'active')
            ->whereIn('class_section_id', $assignedClassIds)
            ->exists();
    }

    private function denyInvalidHardware(Request $request): ?\Illuminate\Http\JsonResponse
    {
        $secret = config('services.hardware.secret', env('HARDWARE_SECRET'));
        if (empty($secret)) {
            Log::critical('HARDWARE_SECRET is not configured — hardware API disabled');
            return response()->json(['message' => 'Hardware API is disabled'], 503);
        }

        $provided = (string) $request->header('X-Hardware-Secret', '');
        if (!hash_equals($secret, $provided)) {
            return response()->json(['message' => 'Unauthorized Hardware Device'], 401);
        }

        return null;
    }

    private function isValidHardwareRequest(Request $request): bool
    {
        return $this->denyInvalidHardware($request) === null;
    }

    private function findStudentByScanUids(array $possibleUids): ?Student
    {
        return Student::with('parent', 'institution')
            ->where(function ($q) use ($possibleUids) {
                $q->whereIn('nfc_tag_uid', $possibleUids)
                    ->orWhereIn('rfid_uid', $possibleUids)
                    ->orWhereIn('qr_code_token', $possibleUids)
                    ->orWhereIn('admission_number', $possibleUids);
            })
            ->first();
    }

    private function findStaffByScanUids(array $possibleUids): ?Staff
    {
        return Staff::with('user', 'institution')
            ->where(function ($q) use ($possibleUids) {
                $q->whereIn('nfc_uid', $possibleUids)
                    ->orWhereIn('rfid_uid', $possibleUids)
                    ->orWhereIn('employee_id', $possibleUids);
            })
            ->first();
    }

    private function getPossibleUids($uid)
    {
        $clean = str_replace([':', ' ', '-'], '', $uid);
        $lower = strtolower($clean);
        $upper = strtoupper($clean);
        
        $withColonsLower = implode(':', str_split($lower, 2));
        $withColonsUpper = implode(':', str_split($upper, 2));
        
        return array_unique([$uid, $clean, $lower, $upper, $withColonsLower, $withColonsUpper]);
    }
}