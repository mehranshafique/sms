<?php

namespace App\Http\Controllers\Api\V1\Chatbot;

use App\Models\Student;
use App\Models\StudentPickup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PickupController extends ChatbotBaseController
{
    public function generateQr(Request $request)
    {
        $request->validate([
            'student_id' => 'required',
            'otp' => 'required|numeric',
            'requester_name' => 'nullable|string|max:100' // Optional name
        ]);

        $institutionId = $request->user()->institute_id;
        
        $student = Student::where('institution_id', $institutionId)
            ->where(function($q) use ($request) {
                $q->where('id', $request->student_id)
                  ->orWhere('admission_number', $request->student_id);
            })->first();

        if (!$student) return $this->sendError(__('chatbot.student_not_found'), 404);

        // Verify OTP
        $cacheKey = 'otp_pickup_' . $student->id;
        $cachedOtp = Cache::get($cacheKey);

        if (!$cachedOtp || $cachedOtp != $request->otp) {
            return $this->sendError(__('chatbot.invalid_otp'), 401);
        }

        Cache::forget($cacheKey);

        // Create new QR request
        $token = 'PKUP-' . Str::upper(Str::random(12));
        $requester = $request->requester_name ?? 'Parent';

        StudentPickup::create([
            'institution_id' => $institutionId,
            'student_id' => $student->id,
            'requested_by' => $requester,
            'token' => $token,
            'otp' => $request->otp,
            'status' => 'pending',
            'expires_at' => now()->addHours(2)
        ]);

        // Generate QR Image URL
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($token);

        return $this->sendResponse([
            'student_name' => $student->full_name,
            'pickup_by' => $requester,
            'qr_url' => $qrUrl,
            'qr_payload' => $token,
            'expires_in' => '2 hours',
            'message' => __('chatbot.qr_instruction')
        ], __('chatbot.qr_generated'));
    }
}