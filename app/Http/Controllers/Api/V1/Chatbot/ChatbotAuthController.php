<?php

namespace App\Http\Controllers\Api\V1\Chatbot;

use App\Models\Student;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class ChatbotAuthController extends ChatbotBaseController
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function requestOtp(Request $request)
    {
        $request->validate(['student_id' => 'required']);
        $user = $request->user();

        $rateKey = 'chatbot-otp:' . $user->id;
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return $this->sendError(__('chatbot.otp_rate_limited') ?? 'Too many OTP requests. Try again later.', 429);
        }
        RateLimiter::hit($rateKey, 600);

        $institutionId = $user->institute_id;
        $student = Student::where('institution_id', $institutionId)
            ->where(function ($q) use ($request) {
                $q->where('id', $request->student_id)
                    ->orWhere('admission_number', $request->student_id);
            })->first();

        if (!$student) {
            return $this->sendError(__('chatbot.student_not_found'), 404);
        }

        $otp = random_int(100000, 999999);
        $cacheKey = 'otp_pickup_' . $student->id;
        Cache::put($cacheKey, (string) $otp, 600);

        $this->notificationService->sendOtpNotification($student, $otp);

        return $this->sendResponse([
            'student_id' => $student->id,
            'masked_phone' => Str::mask($student->parent->father_phone ?? $student->parent->mother_phone ?? 'XXXX', '*', 3, -3),
        ], __('chatbot.otp_sent'));
    }
}
