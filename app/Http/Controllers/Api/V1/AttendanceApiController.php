<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Staff;
use App\Models\StudentAttendance;
use App\Models\StaffAttendance;
use App\Models\AcademicSession;
use App\Models\InstitutionSetting;
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
     * Payload: { "uid": "A1B2C3D4", "device_id": "GATE_1" }
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
            'method' => 'nullable|string|in:manual,qr,nfc,rfid,biometric,automated' 
        ]);

        $uid = trim($request->uid);
        $scanMethod = $request->input('method', 'rfid'); // Default method changed to 'rfid'
        $scanTime = $request->timestamp ? Carbon::parse($request->timestamp) : Carbon::now();
        $date = $scanTime->toDateString();
        $time = $scanTime->format('H:i:s');

        // 1. Auto-Detect User Type (Check Student first, then Staff)
        $student = Student::with('parent', 'institution')
            ->where('nfc_tag_uid', $uid)
            ->orWhere('qr_code_token', $uid)
            ->first();

        if ($student) {
            return $this->processStudentAttendance($student, $date, $time, $scanTime, $scanMethod);
        }

        $staff = Staff::with('user', 'institution')
            ->where('nfc_uid', $uid) 
            ->orWhere('employee_id', $uid) 
            ->orWhere('qr_code_token', $uid)
            ->first();

        if ($staff) {
            return $this->processStaffAttendance($staff, $date, $time, $scanTime, $scanMethod);
        }

        Log::warning("Universal Scan Failed: Unknown UID - {$uid} from Device: " . $request->device_id);
        return response()->json(['status' => 'error', 'message' => 'Unknown Tag or Card'], 404);
    }

    private function processStudentAttendance($student, $date, $time, $scanTime, $scanMethod = 'rfid')
    {
        if ($student->status !== 'active') {
            return response()->json(['status' => 'error', 'message' => 'Student account is inactive.'], 403);
        }

        $institutionId = $student->institution_id;
        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();
        
        if (!$session) {
            return response()->json(['status' => 'error', 'message' => 'No active academic session'], 400);
        }

        // Fetch the active enrollment to get the class_section_id
        $enrollment = \App\Models\StudentEnrollment::where('student_id', $student->id)
            ->where('academic_session_id', $session->id)
            ->where('status', 'active')
            ->first();

        if (!$enrollment) {
            return response()->json(['status' => 'error', 'message' => 'Student is not enrolled in the active session.'], 403);
        }

        // Fetch Dynamic Settings
        $schoolStartTimeStr = InstitutionSetting::get($institutionId, 'school_start_time', '08:00');
        $lateMargin = (int) InstitutionSetting::get($institutionId, 'late_margin_time', 1); // Grace period in minutes
        $cooldownMinutes = (int) InstitutionSetting::get($institutionId, 'double_tap_wait_time', 15); // Wait time before checkout

        // Calculate Punctuality robustly (Handles 12h AM/PM and 24h safely)
        try {
            // Parses string like "05:40 PM" or "17:40"
            $parsedStartTime = Carbon::parse($schoolStartTimeStr);
            
            // Set the parsed hour/minute into today's scan context
            $expectedTime = $scanTime->copy()->setTime($parsedStartTime->hour, $parsedStartTime->minute, 0);
            
            // Add the Margin Time AND 59 seconds to ensure they aren't marked late in the same minute
            $expectedTime->addMinutes($lateMargin)->addSeconds(59);
            
            $isLate = $scanTime->gt($expectedTime);
        } catch (\Exception $e) {
            $isLate = $scanTime->format('H:i') > '08:00'; // Extreme Fallback
        }
        
        $status = $isLate ? 'late' : 'present';

        $attendance = StudentAttendance::where('student_id', $student->id)
            ->where('attendance_date', $date)
            ->first();

        $action = '';

        if (!$attendance) {
            // ARRIVAL (First scan of the day)
            $attendance = StudentAttendance::create([
                'institution_id' => $institutionId,
                'academic_session_id' => $session->id,
                'class_section_id' => $enrollment->class_section_id,
                'student_id' => $student->id,
                'attendance_date' => $date,
                'status' => $status,
                'check_in' => $time,
                'method' => $scanMethod, 
            ]);
            $action = 'arrival';
        } else {
            // DEPARTURE (Second scan)
            $cleanCheckInTime = Carbon::parse($attendance->check_in)->format('H:i:s');
            $checkInTime = Carbon::parse($date . ' ' . $cleanCheckInTime);
            
            // VALIDATION: Hardware Check-out cannot be before Check-in
            if ($scanTime->lt($checkInTime)) {
                return response()->json(['status' => 'error', 'message' => 'Check-out time cannot be before check-in time.'], 400);
            }

            // Dynamic anti-double-tap cooldown
            if ($scanTime->lt($checkInTime->copy()->addMinutes($cooldownMinutes))) {
                return response()->json(['status' => 'ignored', 'message' => "Cooldown active. Please wait {$cooldownMinutes} minutes before checking out."], 200);
            }

            $attendance->update(['check_out' => $time]);
            $action = 'departure';
        }

        // Fire Notifications to Parents
        $this->notifyParent($student, $action, $scanTime->format('h:i A'), $institutionId);

        return response()->json([
            'status' => 'success',
            'action' => $action,
            'type' => 'student',
            'name' => $student->first_name . ' ' . $student->last_name,
            'time' => $scanTime->format('h:i A'),
            'punctuality' => $status
        ], 200);
    }

    private function processStaffAttendance($staff, $date, $time, $scanTime, $scanMethod = 'rfid')
    {
        $institutionId = $staff->institution_id;
        
        // Fetch Dynamic Settings
        $schoolStartTimeStr = InstitutionSetting::get($institutionId, 'school_start_time', '08:00');
        $lateMargin = (int) InstitutionSetting::get($institutionId, 'late_margin_time', 2); // Grace period in minutes
        $cooldownMinutes = (int) InstitutionSetting::get($institutionId, 'double_tap_wait_time', 15);

        // Calculate Punctuality robustly (Handles 12h AM/PM and 24h safely)
        try {
            $parsedStartTime = Carbon::parse($schoolStartTimeStr);
            $expectedTime = $scanTime->copy()->setTime($parsedStartTime->hour, $parsedStartTime->minute, 0);
            
            // Add the Margin Time AND 59 seconds to ensure they aren't marked late in the same minute
            $expectedTime->addMinutes($lateMargin)->addSeconds(59);
            
            $isLate = $scanTime->gt($expectedTime);
        } catch (\Exception $e) {
            $isLate = $scanTime->format('H:i') > '08:00'; // Extreme Fallback
        }
        
        $status = $isLate ? 'late' : 'present';

        $attendance = StaffAttendance::where('staff_id', $staff->id)
            ->where('attendance_date', $date)
            ->first();

        $action = '';

        if (!$attendance) {
            // ARRIVAL
            StaffAttendance::create([
                'institution_id' => $institutionId,
                'staff_id' => $staff->id,
                'attendance_date' => $date,
                'status' => $status,
                'check_in' => $time,
                'method' => $scanMethod,
            ]);
            $action = 'arrival';
        } else {
            // DEPARTURE
            $cleanCheckInTime = Carbon::parse($attendance->check_in)->format('H:i:s');
            $checkInTime = Carbon::parse($date . ' ' . $cleanCheckInTime);
            
            // VALIDATION: Hardware Check-out cannot be before Check-in
            if ($scanTime->lt($checkInTime)) {
                return response()->json(['status' => 'error', 'message' => 'Check-out time cannot be before check-in time.'], 400);
            }

            // Dynamic anti-double-tap cooldown
            if ($scanTime->lt($checkInTime->copy()->addMinutes($cooldownMinutes))) {
                return response()->json(['status' => 'ignored', 'message' => "Cooldown active. Please wait {$cooldownMinutes} minutes before checking out."], 200);
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
}