<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StudentPickup;
use App\Models\StudentEnrollment;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PickupScanController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Scan QR Code (Gatekeeper/Admin mobile app)
     */
    public function scan(Request $request)
    {
        $request->validate(['qr_code' => 'required|string']);

        $user = Auth::user();
        $institutionId = $user->institute_id;

        $pickup = StudentPickup::with('student')
            ->where('token', $request->qr_code)
            ->where('institution_id', $institutionId)
            ->first();

        if (!$pickup) {
            return response()->json(['success' => false, 'message' => __('chatbot.invalid_qr')], 404);
        }

        if ($pickup->status !== 'pending') {
            return response()->json(['success' => false, 'message' => __('chatbot.qr_already_used', ['status' => $pickup->status])], 400);
        }

        if ($pickup->expires_at < now()) {
            $pickup->update(['status' => 'expired']);
            return response()->json(['success' => false, 'message' => __('chatbot.qr_expired')], 400);
        }

        $pickup->update([
            'status' => 'scanned',
            'scanned_by' => $user->id,
            'scanned_at' => now(),
        ]);

        $this->notifyTeacher($pickup);

        return response()->json([
            'success' => true,
            'message' => __('chatbot.scan_success'),
            'data' => [
                'student_name' => $pickup->student->full_name,
                'admission_no' => $pickup->student->admission_number,
                'class' => $this->getStudentClass($pickup->student->id),
                'photo' => $pickup->student->student_photo ? asset('storage/' . $pickup->student->student_photo) : null,
            ],
        ]);
    }

    private function notifyTeacher($pickup): void
    {
        $enrollment = StudentEnrollment::with(['classSection.classTeacher.user'])
            ->where('student_id', $pickup->student_id)
            ->where('status', 'active')
            ->latest()
            ->first();

        if ($enrollment && $enrollment->classSection && $enrollment->classSection->classTeacher) {
            $teacher = $enrollment->classSection->classTeacher->user;

            $message = __('chatbot.teacher_pickup_alert', [
                'student' => $pickup->student->full_name,
                'gate' => Auth::user()->name,
            ]);

            $this->notificationService->performSend($teacher->phone, $message, $pickup->institution_id, true);
        }
    }

    private function getStudentClass($studentId): string
    {
        $enrollment = StudentEnrollment::with('classSection')
            ->where('student_id', $studentId)
            ->where('status', 'active')
            ->latest()
            ->first();

        return $enrollment ? $enrollment->classSection->name : 'N/A';
    }
}
