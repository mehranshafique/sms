<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Staff;
use App\Models\StudentAttendance;
use App\Models\StaffAttendance;
use App\Models\AcademicSession;
use Carbon\Carbon;

class AttendanceApiController extends Controller
{
    /**
     * Handle Single Terminal Scan (QR or NFC)
     */
    public function store(Request $request)
    {
        $request->validate([
            'uid' => 'required|string', // QR Token or NFC UID
            'type' => 'required|in:student,staff',
            'timestamp' => 'required|date',
            'device_id' => 'nullable|string'
        ]);

        $date = Carbon::parse($request->timestamp)->format('Y-m-d');
        $time = Carbon::parse($request->timestamp)->format('H:i:s');

        if ($request->type === 'student') {
            return $this->markStudent($request->uid, $date, $time);
        } else {
            return $this->markStaff($request->uid, $date, $time);
        }
    }

    private function markStudent($uid, $date, $time)
    {
        // Find Student by QR or NFC
        $student = Student::where('qr_code_token', $uid)
            ->orWhere('nfc_tag_uid', $uid)
            ->first();

        if (!$student) {
            return response()->json(['status' => 'error', 'message' => 'Student not found'], 404);
        }

        // Get Active Enrollment for Session
        $session = AcademicSession::where('institution_id', $student->institution_id)
            ->where('is_current', true)
            ->first();

        if (!$session) return response()->json(['message' => 'No active session'], 400);

        $enrollment = $student->enrollments()
            ->where('academic_session_id', $session->id)
            ->first();

        if (!$enrollment) return response()->json(['message' => 'Student not enrolled in current session'], 400);

        // Update or Create Attendance
        // Logic: First scan = Present (Check-in time if needed, though usually students just marked present)
        StudentAttendance::updateOrCreate(
            [
                'student_enrollment_id' => $enrollment->id,
                'attendance_date' => $date
            ],
            [
                'status' => 'present',
                'remarks' => 'Terminal Scan: ' . $time
            ]
        );

        return response()->json([
            'status' => 'success',
            'name' => $student->full_name,
            'type' => 'student',
            'time' => $time
        ]);
    }

    private function markStaff($uid, $date, $time)
    {
        // Assume Staff model has similar tokens, or use Employee ID
        // For this example, assuming 'nfc_uid' column exists on Staff or linking via User
        $staff = Staff::where('employee_id', $uid)->first(); // Simplified matching

        if (!$staff) {
            return response()->json(['status' => 'error', 'message' => 'Staff not found'], 404);
        }

        // Check-in / Check-out Logic
        $attendance = StaffAttendance::firstOrNew([
            'staff_id' => $staff->id,
            'attendance_date' => $date
        ]);

        $attendance->institution_id = $staff->institution_id;
        $attendance->status = 'present';
        $attendance->method = 'nfc'; // or qr based on request

        if (!$attendance->exists) {
            $attendance->check_in = $time;
            $message = 'Check-in Recorded';
        } else {
            $attendance->check_out = $time;
            $message = 'Check-out Recorded';
        }

        $attendance->save();

        return response()->json([
            'status' => 'success',
            'name' => $staff->full_name,
            'action' => $message,
            'time' => $time
        ]);
    }
}