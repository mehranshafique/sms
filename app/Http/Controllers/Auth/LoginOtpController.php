<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\OtpAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginOtpController extends Controller
{
    public function requestOtp(Request $request, OtpAuthService $otpAuth)
    {
        $request->validate(['identifier' => 'required|string']);

        $result = $otpAuth->requestOtp($request->identifier);

        if (!$result['success']) {
            AuditLogger::log('otp_request_failed', 'Auth', 'Web OTP request failed for: ' . $request->identifier);

            return back()->withErrors(['identifier' => $result['message'] ?? __('auth.failed')])->withInput();
        }

        $request->session()->put('otp_user_id', $result['user_id']);
        $request->session()->put('otp_masked_phone', $result['masked_phone'] ?? '');

        return back()->with('otp_sent', true);
    }

    public function verifyOtp(Request $request, OtpAuthService $otpAuth)
    {
        $request->validate(['otp' => 'required|string|size:6']);

        $userId = (int) $request->session()->get('otp_user_id');
        if (!$userId) {
            return redirect()->route('login')->withErrors(['otp' => __('auth.invalid_otp')]);
        }

        $user = $otpAuth->verifyOtp($userId, $request->otp);
        if (!$user) {
            AuditLogger::log('otp_verify_failed', 'Auth', 'Web OTP verify failed for user #' . $userId);
            return back()->withErrors(['otp' => __('auth.invalid_otp')]);
        }

        $request->session()->forget(['otp_user_id', 'otp_masked_phone']);
        $request->session()->regenerate();
        Auth::login($user, $request->boolean('remember'));

        AuditLogger::log('otp_login', 'Auth', 'User logged in via OTP');

        return redirect()->intended(route('dashboard'));
    }
}
