<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use App\Services\AuditLogger;
use Illuminate\Support\Str;

class OtpAuthService
{
    public function __construct(
        protected NotificationService $notifications
    ) {}

    public function requestOtp(string $identifier, ?int $institutionId = null): array
    {
        $rateKey = 'otp-auth:' . md5($identifier);
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return ['success' => false, 'message' => __('auth.otp_rate_limited'), 'code' => 429];
        }
        RateLimiter::hit($rateKey, 600);

        $user = $this->resolveUser($identifier, $institutionId);
        if (!$user || !$user->is_active) {
            AuditLogger::log('otp_request_failed', 'Auth', 'OTP request failed for identifier: ' . $identifier);
            return ['success' => false, 'message' => __('auth.invalid_credentials'), 'code' => 404];
        }

        if (!$user->hasAnyRole(['Teacher', 'Staff', 'School Admin', 'Head Officer', 'Guardian', 'Student'])) {
            return ['success' => false, 'message' => __('auth.otp_not_allowed'), 'code' => 403];
        }

        $phone = $this->resolvePhone($user);
        if (!$phone) {
            return ['success' => false, 'message' => __('auth.no_phone_on_file'), 'code' => 422];
        }

        $otp = (string) random_int(100000, 999999);
        Cache::put($this->cacheKey($user->id), $otp, 600);

        $this->notifications->sendNotificationEvent('otp_login', $phone, [
            'OTP' => $otp,
            'Name' => $user->name,
            'SchoolName' => $user->institute?->name ?? config('app.name'),
        ], $user->institute_id, 'sms');

        return [
            'success' => true,
            'user_id' => $user->id,
            'masked_phone' => Str::mask($phone, '*', 3, -3),
        ];
    }

    public function verifyOtp(int $userId, string $otp): ?User
    {
        $failKey = 'otp-fail:' . $userId;
        if (RateLimiter::tooManyAttempts($failKey, 5)) {
            return null;
        }

        $cached = Cache::get($this->cacheKey($userId));
        if (!$cached || !hash_equals((string) $cached, trim($otp))) {
            RateLimiter::hit($failKey, 900);
            AuditLogger::log('otp_verify_failed', 'Auth', 'OTP verify failed for user #' . $userId);
            return null;
        }

        Cache::forget($this->cacheKey($userId));
        RateLimiter::clear($failKey);

        return User::find($userId);
    }

    public function resolveUser(string $identifier, ?int $institutionId = null): ?User
    {
        $credential = trim($identifier);
        $normalizedPhone = preg_replace('/\D+/', '', $credential);

        $query = User::query()
            ->where('email', $credential)
            ->orWhere('username', $credential)
            ->orWhere('shortcode', $credential);

        if (strlen($normalizedPhone) >= 8) {
            $query->orWhereRaw("REPLACE(REPLACE(REPLACE(phone, '+', ''), ' ', ''), '-', '') LIKE ?", ['%' . substr($normalizedPhone, -9)]);
        }

        $user = $query->first();

        if (!$user) {
            $staff = Staff::where('employee_id', $credential)->first();
            if (!$staff && strlen($normalizedPhone) >= 8) {
                $staff = Staff::whereRaw("REPLACE(REPLACE(REPLACE(phone, '+', ''), ' ', ''), '-', '') LIKE ?", ['%' . substr($normalizedPhone, -9)])->first();
            }
            $user = $staff?->user;
        }

        if ($user && $institutionId && (int) $user->institute_id !== (int) $institutionId) {
            $linked = $user->institutes()->where('institutions.id', $institutionId)->exists();
            if (!$linked) {
                return null;
            }
        }

        return $user;
    }

    private function resolvePhone(User $user): ?string
    {
        if ($user->phone) {
            return $user->phone;
        }

        return $user->staff?->phone;
    }

    private function cacheKey(int $userId): string
    {
        return 'otp_login_user_' . $userId;
    }
}
