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
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AttendanceApiController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function store(Request $request)
    {
        if ($request->header('X-Hardware-Secret') !== env('HARDWARE_SECRET')) {
            return response()->json(['message' => 'Unauthorized Hardware Device'], 401);
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

        return $this->handleAttendanceLogging($request, $uid, $method);
    }

    /**
     * Fetch Today's Attendance List for the POS/Mobile App
     */
    public function getTodayScans(Request $request)
    {
        if ($request->header('X-Hardware-Secret') !== env('HARDWARE_SECRET')) {
            return response()->json(['message' => 'Unauthorized Hardware Device'], 401);
        }

        $today = Carbon::today()->toDateString();
        
        $records = StudentAttendance::with('student:id,first_name,last_name,student_photo,admission_number')
            ->where('attendance_date', $today)
            ->latest('updated_at')
            ->get()
            ->map(function($att) {
                $isCheckOut = $att->check_out !== null;
                
                // Task 4: Show Time In and Out separately
                $timeIn = $att->check_in ? Carbon::parse($att->check_in)->format('h:i A') : '--:--';
                $timeOut = $att->check_out ? Carbon::parse($att->check_out)->format('h:i A') : '--:--';
                $time = $isCheckOut ? $timeOut : $timeIn; // Legacy fallback
                
                $admNo = $att->student->admission_number ?? 'N/A';
                
                return [
                    'id' => $att->id,
                    // Task Global: Append admission number to name
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

        $student = Student::with('parent', 'institution')
            ->whereIn('nfc_tag_uid', $possibleUids)
            ->orWhereIn('rfid_uid', $possibleUids) 
            ->orWhereIn('qr_code_token', $possibleUids)
            ->orWhereIn('admission_number', $possibleUids)
            ->first();

        if ($student) {
            return $this->processStudentAttendance($student, $date, $time, $scanTime, $method);
        }

        $staff = Staff::with('user', 'institution')
            ->whereIn('nfc_uid', $possibleUids)
            ->orWhereIn('rfid_uid', $possibleUids)
            ->orWhereIn('employee_id', $possibleUids) 
            ->first();

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

        $schoolStartTimeStr = InstitutionSetting::get($institutionId, 'school_start_time', '08:00');
        $lateMargin = (int) InstitutionSetting::get($institutionId, 'late_margin_time', 1); 
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

        $attendance = StudentAttendance::where('student_id', $student->id)
            ->where('attendance_date', $date)
            ->first();

        $action = '';

        if (!$attendance) {
            $attendance = StudentAttendance::create([
                'institution_id' => $institutionId,
                'academic_session_id' => $session->id,
                'class_section_id' => $enrollment->class_section_id,
                'student_id' => $student->id,
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

        $this->notifyParent($student, $action, $scanTime->format('h:i A'), $institutionId);
        
        $admNo = $student->admission_number ?? 'N/A';

        return response()->json([
            'status' => 'success',
            'success' => true,
            'action' => $action,
            'type' => 'student',
            // Global Task: Append Admission Number
            'name' => $student->first_name . ' ' . $student->last_name . ' (' . $admNo . ')',
            'time' => $scanTime->format('h:i A'),
            'punctuality' => $status,
            'ui_color' => $status === 'late' ? '#F59E0B' : '#10B981', 
            'message' => ($action === 'arrival' ? 'Welcome' : 'Goodbye') . ', ' . $student->first_name . '!'
        ], 200);
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
            'action' => $action,
            'type' => 'staff',
            'name' => optional($staff->user)->name ?? 'Staff Member',
            'time' => $scanTime->format('h:i A'),
            'punctuality' => $status,
            'ui_color' => $status === 'late' ? '#F59E0B' : '#10B981',
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

        $student = Student::with(['enrollments.classSection'])->whereIn('nfc_tag_uid', $possibleUids)
            ->orWhereIn('rfid_uid', $possibleUids)
            ->orWhereIn('admission_number', $possibleUids)
            ->first();
        
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student Card Not Found.'], 404);
        }

        // Fetch Real Invoices
        $invoices = Invoice::where('student_id', $student->id)->get();
        $unpaidInvoices = $invoices->whereIn('status', ['unpaid', 'partial', 'overdue']);
        
        // Task 1: Comprehensive Financial Breakdown
        $totalDue = $unpaidInvoices->sum(fn($inv) => $inv->total_amount - $inv->paid_amount);
        $totalBalance = $invoices->sum('total_amount');
        $paidBalance = $invoices->sum('paid_amount');
        $remainingBalance = max(0, $totalBalance - $paidBalance);

        // Fetch Last Payment
        $lastPayment = \App\Models\Payment::whereHas('invoice', fn($q) => $q->where('student_id', $student->id))
            ->latest('payment_date')->first();

        // Format Invoice Breakdown for the UI
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

        // 1. Chatbot Generated QR Code Pickup
        if (str_starts_with($uid, 'PKUP-') || str_starts_with($uid, 'QR-')) {
            $pickup = StudentPickup::with('student')->where('token', $uid)->first();

            if (!$pickup) return response()->json(['success' => false, 'message' => 'pickup_invalid_qr'], 404);
            
            // Allow 'scanned' or 'pending'. If it's already 'scanned' (completed), deny it.
            if (in_array($pickup->status, ['scanned', 'completed', 'approved'])) {
                return response()->json(['success' => false, 'message' => 'pickup_already_used'], 400);
            }

            // FIXED ENUM CRASH: Use 'scanned' instead of 'completed'
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

        // 2. Direct Physical NFC Tap Pickup
        $student = Student::whereIn('nfc_tag_uid', $possibleUids)
            ->orWhereIn('rfid_uid', $possibleUids)
            ->orWhereIn('admission_number', $possibleUids)
            ->first();

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

        $student = Student::whereIn('nfc_tag_uid', $possibleUids)
            ->orWhereIn('rfid_uid', $possibleUids)
            ->orWhereIn('admission_number', $possibleUids)
            ->first();

        if (!$student) return response()->json(['success' => false, 'message' => 'Student Not Found.'], 404);

        $institutionId = $student->institution_id;
        $admNo = $student->admission_number ?? 'N/A';
        
        // Check Financial Block
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
                ], 200); // 200 so the app shows the message gracefully
            }
        }

        // Fetch Real Exam Records
        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();
        
        $records = ExamRecord::with(['subject', 'exam.academicSession'])
            ->where('student_id', $student->id)
            ->whereHas('exam', fn($q) => $q->where('academic_session_id', $session->id ?? 0))
            ->latest('updated_at')
            ->get(); // Task 5: Removed ->take(6) to show all subjects

        $formattedRecords = $records->map(function($r) {
            return [
                'subject' => $r->subject->name ?? 'N/A',
                'marks' => $r->marks_obtained,
            ];
        });

        // Task 5: Fetch rich Exam Details
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

    private function notifyTeacher($pickup)
    {
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

            if ($teacher && $teacher->id && method_exists($this->notificationService, 'sendPushNotification')) {
                $title = __('notifications.parent_at_gate_title');
                if ($title === 'notifications.parent_at_gate_title') {
                    $title = "Parent at Gate 🚨";
                }
                $body = __('notifications.parent_at_gate_body', ['student_first_name' => $pickup->student->first_name]);
                if ($body === 'notifications.parent_at_gate_body') {
                    $body = "{$pickup->student->first_name}'s parent is waiting at the gate for pickup.";
                }

                $this->notificationService->sendPushNotification(
                    $teacher->id,
                    $title,
                    $body,
                    ['pickup_id' => $pickup->id, 'type' => 'pickup_requested']
                );
            }
        }
    }

    /**
     * Task 10: Fetch Today's Absentees for Teacher Dashboard
     */
    public function getTodayAbsentees(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('Teacher')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $staffId = $user->staff->id ?? null;
        $classIds = \App\Models\ClassSection::where('staff_id', $staffId)->pluck('id');
        
        $absentees = StudentAttendance::with('student:id,first_name,last_name,student_photo,admission_number')
            ->whereIn('class_section_id', $classIds)
            ->whereDate('attendance_date', Carbon::today()->toDateString())
            ->where('status', 'absent')
            ->get()
            ->map(function($att) {
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
     * Fetch Absentee Report Grouped by Class Sections for the Logged-in Teacher
     * Endpoint: GET /api/v1/attendance/absentees
     */
    public function getTeacherClassAbsentees(Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        
        // Ensure the user is a teacher and has a staff profile
        if (!$user->hasRole('Teacher') || !$user->staff) {
            return response()->json(['success' => false, 'message' => 'unauthorized_access'], 403);
        }

        $today = \Carbon\Carbon::today()->toDateString();
        $dayOfWeek = strtolower(now()->format('l')); // e.g., 'tuesday'
        $staffId = $user->staff->id;
        
        // SMART LOGIC: Check institution type for future Subject-wise attendance
        $institution = clone $user->institute;
        $instType = $institution->type ?? 'primary';
        $isSubjectWise = in_array($instType, ['university', 'vocational']);
        
        // --- THE FIX: Direct relationship queries to prevent 500 crashes ---
        $homeroomIds = \App\Models\ClassSection::where('staff_id', $staffId)->pluck('id')->toArray();
        
        $timetableIds = \App\Models\Timetable::where('teacher_id', $staffId)
            ->where('day_of_week', $dayOfWeek)
            ->pluck('class_section_id')->toArray();
            
        $allocatedIds = \App\Models\ClassSubject::where('teacher_id', $staffId)
            ->pluck('class_section_id')->toArray();

        $allAssignedClassIds = array_unique(array_merge($homeroomIds, $timetableIds, $allocatedIds));

        $sections = \App\Models\ClassSection::where('institution_id', $user->institute_id)
            ->where('is_active', true)
            ->whereIn('id', $allAssignedClassIds)
            ->get();

        $currentSession = \App\Models\AcademicSession::where('institution_id', $user->institute_id)
            ->where('is_current', true)->first();
        $sessionId = $currentSession ? $currentSession->id : null;
        // --- END FIX ---

        $report = [];

        foreach ($sections as $section) {
            // Get active enrollments for this specific section
            $enrollments = \App\Models\StudentEnrollment::with(['student.parent'])
                ->where('class_section_id', $section->id)
                ->where('academic_session_id', $sessionId)
                ->where('status', 'active')
                ->get();

            $studentIds = $enrollments->pluck('student_id')->toArray();
            $totalClass = count($studentIds);

            // Get today's attendance records for these students
            // NOTE: When Subject-wise attendance is implemented for University, this query will target SubjectAttendance instead.
            $attendances = \App\Models\StudentAttendance::whereIn('student_id', $studentIds)
                ->where('attendance_date', $today)
                ->get()
                ->keyBy('student_id');

            $presentCount = 0;
            $absenteesList = [];

            foreach ($enrollments as $enrollment) {
                $student = $enrollment->student;
                $attendance = $attendances->get($student->id);

                // If they have a record and are present/late, count as present
                if ($attendance && in_array($attendance->status, ['present', 'late'])) {
                    $presentCount++;
                } else {
                    // Otherwise, they are absent. Fetch parent contact safely using ?->
                    $parent = $student->parent;
                    $phone = $parent?->father_phone ?? $parent?->mother_phone ?? $parent?->guardian_phone ?? 'N/A';
                    
                    // Safely format name
                    $firstName = $student->first_name ?? '';
                    $lastName = $student->last_name ?? '';
                    $admNo = $student->admission_number ?? 'N/A';
                    $fullName = trim("{$firstName} {$lastName}");
                    if (empty($fullName)) $fullName = 'Unknown Student';

                    $absenteesList[] = [
                        'student_name' => "{$fullName} ({$admNo})",
                        'parent_phone' => $phone,
                    ];
                }
            }

            // Only include sections in the report if they have enrolled students
            if ($totalClass > 0) {
                $report[] = [
                    'section_name' => $section->name ?? 'Unknown Section',
                    'total_class' => $totalClass,
                    'total_present' => $presentCount,
                    'total_absent' => $totalClass - $presentCount,
                    'absentees' => $absenteesList,
                    'attendance_type' => $isSubjectWise ? 'subject' : 'daily' // Smart Flag for Frontend
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    /**
     * Fuzzy match for NFC/RFID UIDs
     */
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