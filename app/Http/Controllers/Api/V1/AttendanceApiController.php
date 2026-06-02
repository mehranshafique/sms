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

class AttendanceApiController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Universal Scan Endpoint (Handles RFID, NFC, and QR)
     */
    public function store(Request $request)
    {
        if ($request->header('X-Hardware-Secret') !== env('HARDWARE_SECRET')) {
            return response()->json(['message' => __('api.unauthorized_hardware')], 401);
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
            return response()->json(['message' => __('api.unauthorized_hardware')], 401);
        }

        $today = Carbon::today()->toDateString();
        
        $records = StudentAttendance::with('student:id,first_name,last_name,student_photo,admission_number')
            ->where('attendance_date', $today)
            ->latest('updated_at')
            ->take(30)
            ->get()
            ->map(function($att) {
                $isCheckOut = $att->check_out !== null;
                $time = $isCheckOut ? Carbon::parse($att->check_out)->format('h:i A') : Carbon::parse($att->check_in)->format('h:i A');
                
                return [
                    'id' => $att->id,
                    'student_name' => $att->student->full_name ?? __('api.unknown'),
                    'admission_no' => $att->student->admission_number ?? 'N/A',
                    'photo' => $att->student->student_photo ? asset('storage/'.$att->student->student_photo) : null,
                    'time' => $time,
                    'action' => $isCheckOut ? __('api.departure') : __('api.arrival'),
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
        return response()->json(['status' => 'error', 'success' => false, 'message' => __('api.unknown_card')], 404);
    }

    private function processStudentAttendance($student, $date, $time, $scanTime, $method)
    {
        if ($student->status !== 'active') {
            return response()->json(['status' => 'error', 'success' => false, 'message' => __('api.student_inactive')], 403);
        }

        $institutionId = $student->institution_id;
        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();
        
        if (!$session) {
            return response()->json(['status' => 'error', 'success' => false, 'message' => __('api.no_active_session')], 400);
        }

        $enrollment = StudentEnrollment::where('student_id', $student->id)
            ->where('academic_session_id', $session->id)
            ->where('status', 'active')
            ->first();

        if (!$enrollment) {
            return response()->json(['status' => 'error', 'success' => false, 'message' => __('api.student_not_enrolled')], 403);
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
                return response()->json(['status' => 'error', 'message' => __('api.checkout_before_checkin')], 400);
            }

            if ($scanTime->lt($checkInTime->copy()->addMinutes($cooldownMinutes))) {
                return response()->json(['status' => 'ignored', 'message' => __('api.cooldown_active', ['mins' => $cooldownMinutes])], 200);
            }

            $attendance->update(['check_out' => $time]);
            $action = 'departure';
        }

        $this->notifyParent($student, $action, $scanTime->format('h:i A'), $institutionId);

        $msg = $action === 'arrival' ? __('api.welcome') : __('api.goodbye');
        return response()->json([
            'status' => 'success',
            'success' => true,
            'action' => $action,
            'type' => 'student',
            'name' => $student->first_name . ' ' . $student->last_name,
            'time' => $scanTime->format('h:i A'),
            'punctuality' => $status,
            'ui_color' => $status === 'late' ? '#F59E0B' : '#10B981', 
            'message' => "{$msg}, {$student->first_name}!"
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
                return response()->json(['status' => 'error', 'message' => __('api.checkout_before_checkin')], 400);
            }

            if ($scanTime->lt($checkInTime->copy()->addMinutes($cooldownMinutes))) {
                return response()->json(['status' => 'ignored', 'message' => __('api.cooldown_active', ['mins' => $cooldownMinutes])], 200);
            }

            $attendance->update(['check_out' => $time]);
            $action = 'departure';
        }

        return response()->json([
            'status' => 'success',
            'action' => $action,
            'type' => 'staff',
            'name' => optional($staff->user)->name ?? __('api.staff_member'),
            'time' => $scanTime->format('h:i A'),
            'punctuality' => $status,
            'ui_color' => $status === 'late' ? '#F59E0B' : '#10B981',
            'message' => __('api.staff_attendance_marked')
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

    // =========================================================================
    // V2 NEW MODULES (Rich Data Integration)
    // =========================================================================

    private function handleFeeCheck($uid)
    {
        $possibleUids = $this->getPossibleUids($uid);

        $student = Student::whereIn('nfc_tag_uid', $possibleUids)
            ->orWhereIn('rfid_uid', $possibleUids)
            ->orWhereIn('admission_number', $possibleUids)
            ->first();
        
        if (!$student) {
            return response()->json(['success' => false, 'message' => __('api.student_card_not_found')], 404);
        }

        $invoices = Invoice::where('student_id', $student->id)->whereIn('status', ['unpaid', 'partial', 'overdue'])->get();
        $totalDue = $invoices->sum(fn($inv) => $inv->total_amount - $inv->paid_amount);

        $invoiceBreakdown = $invoices->map(function($inv) {
            return [
                'invoice_number' => $inv->invoice_number,
                'amount_due' => number_format($inv->total_amount - $inv->paid_amount, 2),
                'due_date' => $inv->due_date->format('d M, Y'),
                'status' => ucfirst($inv->status)
            ];
        });

        if ($totalDue > 0) {
            return response()->json([
                'success' => false, 
                'message' => __('api.payment_threshold_not_met'),
                'data' => [
                    'student_name' => $student->full_name,
                    'balance' => number_format($totalDue, 2),
                    'invoices' => $invoiceBreakdown, 
                    'color' => '#dc2626' 
                ]
            ]);
        }

        return response()->json([
            'success' => true, 
            'message' => __('api.payment_threshold_met'),
            'data' => [
                'student_name' => $student->full_name,
                'balance' => '0.00',
                'invoices' => [],
                'color' => '#16a34a' 
            ]
        ]);
    }

    private function handlePickup($uid, $deviceId)
{
    $possibleUids = $this->getPossibleUids($uid);

    // 1. Check for Chatbot QR Code first
    if (str_starts_with($uid, 'PKUP-') || str_starts_with($uid, 'QR-')) {
        $pickup = StudentPickup::with('student')->where('token', $uid)->first();

        if (!$pickup) return response()->json(['success' => false, 'message' => 'pickup_invalid_qr'], 404);
        
        if (in_array($pickup->status, ['scanned', 'completed', 'approved'])) {
            return response()->json(['success' => false, 'message' => 'pickup_already_used'], 400);
        }

        $pickup->update([
            'status' => 'scanned', // Places it in the waiting queue
            'scanned_at' => now(), 
            'scanned_by_device' => $deviceId
        ]);

        $this->notifyTeacher($pickup);
        
        // Return Translation Keys, not hardcoded English
        return response()->json([
            'success' => true, 
            'message' => 'pickup_wait_for_teacher', 
            'data' => [
                'student_name' => $pickup->student->full_name,
                'color' => '#F59E0B' // Amber/Warning to indicate "Waiting"
            ]
        ]);
    }

    // 2. Direct Physical NFC Tap Pickup
    $student = Student::whereIn('nfc_tag_uid', $possibleUids)
        ->orWhereIn('rfid_uid', $possibleUids)
        ->orWhereIn('admission_number', $possibleUids)
        ->first();

    if ($student) {
        // Create a new pending request instead of instantly releasing
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

        return response()->json([
            'success' => true, 
            'message' => 'pickup_wait_for_teacher',
            'data' => [
                'student_name' => $student->full_name,
                'color' => '#F59E0B' // Amber/Warning to indicate "Waiting"
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

        if (!$student) return response()->json(['success' => false, 'message' => __('api.student_not_found')], 404);

        $institutionId = $student->institution_id;
        
        $isBlocked = InstitutionSetting::get($institutionId, 'block_reports_on_debt', 0);
        if ($isBlocked) {
            $unpaid = Invoice::where('student_id', $student->id)->whereIn('status', ['unpaid', 'partial', 'overdue'])->sum(DB::raw('total_amount - paid_amount'));
            if ($unpaid > 0) {
                return response()->json([
                    'success' => false,
                    'message' => __('api.financial_block', ['amount' => number_format($unpaid, 2)]),
                    'data' => [
                        'student_name' => $student->full_name,
                        'color' => '#dc2626'
                    ]
                ], 200); 
            }
        }

        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();
        
        $records = ExamRecord::with('subject')
            ->where('student_id', $student->id)
            ->whereHas('exam', fn($q) => $q->where('academic_session_id', $session->id ?? 0))
            ->latest('updated_at')
            ->take(6)
            ->get()
            ->map(function($r) {
                return [
                    'subject' => $r->subject->name ?? 'N/A',
                    'marks' => $r->marks_obtained,
                ];
            });

        return response()->json([
            'success' => true, 
            'message' => __('api.report_card_signed'),
            'data' => [
                'student_name' => $student->full_name,
                'recent_marks' => $records,
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
                
                // Fallback if the language file is missing the key
                if ($message === 'notifications.teacher_pickup_alert') {
                    $message = "🚨 Student Pickup Alert: {$pickup->student->full_name}'s parent is waiting at the gate.";
                }
                
                $this->notificationService->performSend($teacher->phone, $message, $pickup->institution_id, false, 'whatsapp');
            }

            // NEW: Send Push Notification to Teacher's Mobile App
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