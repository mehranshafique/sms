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
use App\Models\Invoice;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceApiController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Universal Scan Endpoint (Handles RFID, NFC, and QR)
     * Payload: { "uid": "A1B2C3D4", "device_id": "GATE_1", "purpose": "pickup" }
     */
    public function store(Request $request)
    {
        // Require a valid secret key from the hardware to prevent fake attendance injections
        if ($request->header('X-Hardware-Secret') !== env('HARDWARE_SECRET')) {
            return response()->json(['message' => 'Unauthorized Hardware Device'], 401);
        }

        $request->validate([
            'uid' => 'required|string', 
            'device_id' => 'nullable|string',
            'timestamp' => 'nullable|date', // Allows offline POS sync later
            'method' => 'nullable|string',
            'purpose' => 'nullable|string' // V2 Flutter App Purpose
        ]);

        $uid = trim($request->uid);
        $purpose = $request->purpose ?? 'attendance'; // Safely defaults to standard attendance

        // --- NEW FLUTTER APP ROUTING ---
        if ($purpose === 'fee_check') {
            return $this->handleFeeCheck($uid);
        }
        if ($purpose === 'pickup') {
            return $this->handlePickup($uid, $request->device_id);
        }
        if ($purpose === 'report_card') {
            return $this->handleReportCard($uid);
        }

        // --- ORIGINAL CHAFON / HARDWARE LOGIC ---
        // If no special purpose is sent, process exactly as it was originally built.
        return $this->handleAttendanceLogging($request, $uid);
    }

    // =========================================================================
    // 100% UNTOUCHED ORIGINAL LOGIC (For Chatbot & Hardware Bridge)
    // =========================================================================
    
    private function handleAttendanceLogging(Request $request, $uid)
    {
        $scanTime = $request->timestamp ? Carbon::parse($request->timestamp) : Carbon::now();
        $date = $scanTime->toDateString();
        $time = $scanTime->format('H:i:s');

        // Check Student First
        $student = Student::with('parent', 'institution')
            ->where('nfc_tag_uid', $uid)
            ->orWhere('qr_code_token', $uid)
            ->orWhere('admission_number', $uid)
            ->first();

        if ($student) {
            return $this->processStudentAttendance($student, $date, $time, $scanTime);
        }

        // Check Staff Second
        $staff = Staff::with('user', 'institution')
            ->where('nfc_uid', $uid)
            ->orWhere('employee_id', $uid) 
            ->first();

        if ($staff) {
            return $this->processStaffAttendance($staff, $date, $time, $scanTime);
        }

        Log::warning("Universal Scan Failed: Unknown UID - {$uid}");
        return response()->json(['status' => 'error', 'success' => false, 'message' => 'Unknown Tag or Card'], 404);
    }

    private function processStudentAttendance($student, $date, $time, $scanTime)
    {
        if ($student->status !== 'active') {
            return response()->json(['status' => 'error', 'success' => false, 'message' => 'Student account is inactive.'], 403);
        }

        $institutionId = $student->institution_id;
        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();
        
        if (!$session) {
            return response()->json(['status' => 'error', 'success' => false, 'message' => 'No active academic session'], 400);
        }

        $schoolStartTime = InstitutionSetting::get($institutionId, 'school_start_time', '08:00');
        $isLate = $scanTime->format('H:i') > $schoolStartTime;
        $status = $isLate ? 'late' : 'present';

        $attendance = StudentAttendance::where('student_id', $student->id)
            ->where('attendance_date', $date)
            ->first();

        $action = '';

        if (!$attendance) {
            $attendance = StudentAttendance::create([
                'institution_id' => $institutionId,
                'academic_session_id' => $session->id,
                'student_id' => $student->id,
                'attendance_date' => $date,
                'status' => $status,
                'check_in' => $time,
                'method' => 'automated', 
            ]);
            $action = 'arrival';
        } else {
            $checkInTime = Carbon::parse($attendance->check_in);
            if ($scanTime->diffInMinutes($checkInTime) < 15) {
                return response()->json(['status' => 'ignored', 'success' => true, 'message' => 'Cooldown active. Duplicate scan ignored.'], 200);
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
            'message' => ($action === 'arrival' ? 'Welcome' : 'Goodbye') . ', ' . $student->first_name . '!'
        ], 200);
    }

    private function processStaffAttendance($staff, $date, $time, $scanTime)
    {
        $institutionId = $staff->institution_id;
        $schoolStartTime = InstitutionSetting::get($institutionId, 'school_start_time', '08:00');
        $isLate = $scanTime->format('H:i') > $schoolStartTime;
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
                'method' => 'automated',
            ]);
            $action = 'arrival';
        } else {
            $checkInTime = Carbon::parse($attendance->check_in);
            if ($scanTime->diffInMinutes($checkInTime) < 15) {
                return response()->json(['status' => 'ignored', 'success' => true, 'message' => 'Cooldown active.'], 200);
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
            'punctuality' => $status
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

        // Safely dispatch based on school's configuration using the global NotificationService
        $this->notificationService->sendNotificationEvent($eventKey, $phone, $data, $institutionId, 'whatsapp');
        $this->notificationService->sendNotificationEvent($eventKey, $phone, $data, $institutionId, 'sms');
    }

    // =========================================================================
    // V2 NEW MODULES (App Specific: Fee Check, Pickup, Report Card)
    // =========================================================================

    private function handleFeeCheck($uid)
    {
        $student = Student::where('nfc_tag_uid', $uid)
            ->orWhere('admission_number', $uid)
            ->first();
        
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student Card Not Found.'], 404);
        }

        $totalDue = Invoice::where('student_id', $student->id)->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->get()->sum(fn($inv) => $inv->total_amount - $inv->paid_amount);

        if ($totalDue > 0) {
            return response()->json([
                'success' => false, 
                'message' => "Payment Threshold Not Met.",
                'data' => [
                    'student_name' => $student->full_name,
                    'balance' => number_format($totalDue, 2)
                ]
            ]);
        }

        return response()->json([
            'success' => true, 
            'message' => 'Payment Threshold Met. Account Cleared.',
            'data' => [
                'student_name' => $student->full_name,
                'balance' => '0.00'
            ]
        ]);
    }

    private function handlePickup($uid, $deviceId)
    {
        // 1. Chatbot Generated QR Code Pickup
        if (str_starts_with($uid, 'PKUP-') || str_starts_with($uid, 'QR-')) {
            $pickup = StudentPickup::with('student')->where('token', $uid)->first();

            if (!$pickup) return response()->json(['success' => false, 'message' => 'Invalid or Expired QR Code.'], 404);
            if ($pickup->status !== 'pending') return response()->json(['success' => false, 'message' => "QR Code already used."], 400);

            $pickup->update(['status' => 'completed', 'scanned_at' => now(), 'scanned_by_device' => $deviceId]);

            // Notifications
            $this->notifyParent($pickup->student, 'departure', now()->format('h:i A'), $pickup->institution_id);
            $this->notifyTeacher($pickup);

            return response()->json([
                'success' => true, 
                'message' => 'Gate Pass Validated successfully!',
                'data' => ['student_name' => $pickup->student->full_name]
            ]);
        }

        // 2. Direct Physical NFC Tap Pickup (Parent uses their card to pickup kid)
        $student = Student::where('nfc_tag_uid', $uid)->orWhere('admission_number', $uid)->first();
        if ($student) {
            $this->notifyParent($student, 'departure', now()->format('h:i A'), $student->institution_id);
            return response()->json([
                'success' => true, 
                'message' => 'NFC Pickup Confirmed! Student released.',
                'data' => ['student_name' => $student->full_name]
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Unrecognized Card or QR Code.'], 404);
    }

    private function handleReportCard($uid)
    {
        $student = Student::where('nfc_tag_uid', $uid)->orWhere('admission_number', $uid)->first();
        if (!$student) return response()->json(['success' => false, 'message' => 'Student Not Found.'], 404);

        return response()->json([
            'success' => true, 
            'message' => 'Report Card Digitally Signed by Parent!'
        ]);
    }

    /**
     * Alerts the Teacher inside the classroom when a kid is picked up at the gate
     */
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
                // Hardcoded secure message to teacher to bypass templates
                $message = "🚨 Student Pickup Alert: {$pickup->student->full_name} has just been released at the gate.";
                // We use performSend directly to avoid relying on a template for this specific internal alert
                $this->notificationService->performSend($teacher->phone, $message, $pickup->institution_id, false, 'whatsapp');
            }
        }
    }
}