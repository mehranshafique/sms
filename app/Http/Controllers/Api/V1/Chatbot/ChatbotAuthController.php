<?php

namespace App\Http\Controllers\Api\V1\Chatbot;

use App\Models\Student;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ChatbotAuthController extends ChatbotBaseController
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Request OTP for sensitive actions (e.g. Pickup QR)
     */
    public function requestOtp(Request $request)
    {
        $request->validate(['student_id' => 'required']);
        $institutionId = $request->user()->institute_id;

        $student = Student::where('institution_id', $institutionId)
            ->where(function($q) use ($request) {
                $q->where('id', $request->student_id)
                  ->orWhere('admission_number', $request->student_id);
            })->first();

        if (!$student) return $this->sendError(__('chatbot.student_not_found'), 404);

        // Generate 6-digit OTP
        $otp = rand(100000, 999999);
        
        // Store in Cache for 10 minutes (Key: otp_pickup_{student_id})
        $cacheKey = 'otp_pickup_' . $student->id;
        Cache::put($cacheKey, $otp, 600);

        // Send via Notification Service (SMS/WhatsApp)
        $this->notificationService->sendOtpNotification($student, $otp);

        return $this->sendResponse([
            'student_id' => $student->id,
            'masked_phone' => Str::mask($student->parent->father_phone ?? $student->parent->mother_phone ?? 'XXXX', '*', 3, -3)
        ], __('chatbot.otp_sent'));
    }
}