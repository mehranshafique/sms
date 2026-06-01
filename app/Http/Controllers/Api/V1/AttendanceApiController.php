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
        $method = $request->input('method', 'rfid'); // Extract method or fallback

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
     * NEW: Fetch Today's Attendance List for the POS/Mobile App
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
            ->take(30)
            ->get()
            ->map(function($att) {
                $isCheckOut = $att->check_out !== null;
                $time = $isCheckOut ? Carbon::parse($att->check_out)->format('h:i A') : Carbon::parse($att->check_in)->format('h:i A');
                
                return [
                    'id' => $att->id,
                    'student_name' => $att->student->full_name ?? 'Unknown',
                    'admission_no' => $att->student->admission_number ?? 'N/A',
                    'photo' => $att->student->student_photo ? asset('storage/'.$att->student->student_photo) : null,
                    'time' => $time,
                    'action' => $isCheckOut ? 'Departure' : 'Arrival',
                    'status' => ucfirst($att->status),
                    // UI Color Codes for Flutter
                    'status_color' => $att->status === 'late' ? '#F59E0B' : '#10B981', // Amber (Late) / Emerald (Present)
                    'action_color' => $isCheckOut ? '#6366F1' : '#3B82F6', // Indigo (Out) / Blue (In)
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $records
        ]);
    }

    // =========================================================================
    // V1 CORE ATTENDANCE LOGIC
    // =========================================================================
    
    private function handleAttendanceLogging(Request $request, $uid, $method)
    {
        $scanTime = $request->timestamp ? Carbon::parse($request->timestamp) : Carbon::now();
        $date = $scanTime->toDateString();
        $time = $scanTime->format('H:i:s');

        $student = Student::with('parent', 'institution')
            ->where('nfc_tag_uid', $uid)
            ->orWhere('rfid_uid', $uid) 
            ->orWhere('qr_code_token', $uid)
            ->orWhere('admission_number', $uid)
            ->first();

        if ($student) {
            return $this->processStudentAttendance($student, $date, $time, $scanTime, $method);
        }

        $staff = Staff::with('user', 'institution')
            ->where('nfc_uid', $uid)
            ->orWhere('rfid_uid', $uid)
            ->orWhere('employee_id', $uid) 
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

        return response()->json([
            'status' => 'success',
            'success' => true,
            'action' => $action,
            'type' => 'student',
            'name' => $student->first_name . ' ' . $student->last_name,
            'time' => $scanTime->format('h:i A'),
            'punctuality' => $status,
            'ui_color' => $status === 'late' ? '#F59E0B' : '#10B981', // Send color code for the app
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

    // =========================================================================
    // V2 NEW MODULES (Rich Data Integration)
    // =========================================================================

    private function handleFeeCheck($uid)
    {
        $student = Student::where('nfc_tag_uid', $uid)
            ->orWhere('rfid_uid', $uid)
            ->orWhere('admission_number', $uid)
            ->first();
        
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student Card Not Found.'], 404);
        }

        // Fetch Real Invoices
        $invoices = Invoice::where('student_id', $student->id)->whereIn('status', ['unpaid', 'partial', 'overdue'])->get();
        $totalDue = $invoices->sum(fn($inv) => $inv->total_amount - $inv->paid_amount);

        // Format Invoice Breakdown for the UI
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
                'message' => "Payment Threshold Not Met.",
                'data' => [
                    'student_name' => $student->full_name,
                    'balance' => number_format($totalDue, 2),
                    'invoices' => $invoiceBreakdown, // Send Real Data to the App
                    'color' => '#dc2626' // Red
                ]
            ]);
        }

        return response()->json([
            'success' => true, 
            'message' => 'Payment Threshold Met. Account Cleared.',
            'data' => [
                'student_name' => $student->full_name,
                'balance' => '0.00',
                'invoices' => [],
                'color' => '#16a34a' // Green
            ]
        ]);
    }

    private function handlePickup($uid, $deviceId)
    {
        // 1. Chatbot Generated QR Code Pickup
        if (str_starts_with($uid, 'PKUP-') || str_starts_with($uid, 'QR-')) {
            $pickup = StudentPickup::with('student')->where('token', $uid)->first();

            if (!$pickup) return response()->json(['success' => false, 'message' => 'Invalid or Expired QR Code.'], 404);
            
            // Allow 'scanned' or 'pending'. If it's already 'scanned' (completed), deny it.
            if (in_array($pickup->status, ['scanned', 'completed'])) {
                return response()->json(['success' => false, 'message' => 'QR Code already used.'], 400);
            }

            // FIXED ENUM CRASH: Use 'scanned' instead of 'completed'
            $pickup->update(['status' => 'scanned', 'scanned_at' => now(), 'scanned_by_device' => $deviceId]);

            $this->notifyParent($pickup->student, 'departure', now()->format('h:i A'), $pickup->institution_id);
            $this->notifyTeacher($pickup);

            return response()->json([
                'success' => true, 
                'message' => 'Gate Pass Validated successfully!',
                'data' => [
                    'student_name' => $pickup->student->full_name,
                    'color' => '#16a34a'
                ]
            ]);
        }

        // 2. Direct Physical NFC Tap Pickup
        $student = Student::where('nfc_tag_uid', $uid)
            ->orWhere('rfid_uid', $uid)
            ->orWhere('admission_number', $uid)
            ->first();

        if ($student) {
            $this->notifyParent($student, 'departure', now()->format('h:i A'), $student->institution_id);
            return response()->json([
                'success' => true, 
                'message' => 'NFC Pickup Confirmed! Student released.',
                'data' => [
                    'student_name' => $student->full_name,
                    'color' => '#16a34a'
                ]
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Unrecognized Card or QR Code.'], 404);
    }

    private function handleReportCard($uid)
    {
        $student = Student::where('nfc_tag_uid', $uid)
            ->orWhere('rfid_uid', $uid)
            ->orWhere('admission_number', $uid)
            ->first();

        if (!$student) return response()->json(['success' => false, 'message' => 'Student Not Found.'], 404);

        $institutionId = $student->institution_id;
        
        // Check Financial Block
        $isBlocked = InstitutionSetting::get($institutionId, 'block_reports_on_debt', 0);
        if ($isBlocked) {
            $unpaid = Invoice::where('student_id', $student->id)->whereIn('status', ['unpaid', 'partial', 'overdue'])->sum(DB::raw('total_amount - paid_amount'));
            if ($unpaid > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Financial Block: Outstanding balance of $" . number_format($unpaid, 2),
                    'data' => [
                        'student_name' => $student->full_name,
                        'color' => '#dc2626'
                    ]
                ], 200); // 200 so the app shows the message gracefully
            }
        }

        // Fetch Real Exam Records
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
            'message' => 'Report Card Access Granted & Digitally Signed!',
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
                $message = "🚨 Student Pickup Alert: {$pickup->student->full_name} has just been released at the gate.";
                $this->notificationService->performSend($teacher->phone, $message, $pickup->institution_id, false, 'whatsapp');
            }
        }
    }
}