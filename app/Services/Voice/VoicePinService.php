<?php

namespace App\Services\Voice;

use App\Models\InstitutionSetting;
use App\Models\StudentParent;
use App\Models\VoiceParentPin;
use App\Models\VoiceSession;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Phase 2 privacy gate: a spoken PIN before any child-specific answer.
 *
 * Schools that share phone numbers across families can require a 4-digit PIN.
 * Non-sensitive options (notices, school contact, admission info) stay open.
 */
class VoicePinService
{
    public const MAX_ATTEMPTS = 3;
    public const LOCK_MINUTES = 30;

    /** Options that may only be answered once the caller proved identity. */
    public const SENSITIVE_INTENTS = [
        'fees',
        'fee_details',
        'attendance',
        'ptm',
        'pickup',
        'requests',
        'ai',
    ];

    public function isRequired(?int $institutionId): bool
    {
        if (! $institutionId) {
            return false;
        }

        return (string) InstitutionSetting::get($institutionId, 'voice_ivr_parent_pin_required', '0') === '1';
    }

    public function intentIsSensitive(string $intent): bool
    {
        return in_array($intent, self::SENSITIVE_INTENTS, true);
    }

    /**
     * Does this session still have to pass the PIN gate for the given intent?
     */
    public function mustVerify(VoiceSession $session, string $intent): bool
    {
        // Guests have no parent record to authenticate against; their menu only
        // exposes public information anyway.
        if (! $session->isParentMenu()) {
            return false;
        }

        if (! $this->isRequired($session->institution_id)) {
            return false;
        }

        if (! $this->intentIsSensitive($intent)) {
            return false;
        }

        return ! $session->pin_verified;
    }

    public function pinFor(VoiceSession $session): ?VoiceParentPin
    {
        if (! $session->parent_id || ! $session->institution_id) {
            return null;
        }

        return VoiceParentPin::where('institution_id', $session->institution_id)
            ->where('parent_id', $session->parent_id)
            ->first();
    }

    public function hasPin(VoiceSession $session): bool
    {
        return $this->pinFor($session) !== null;
    }

    /**
     * @return array{status: 'ok'|'invalid'|'locked'|'missing'|'exhausted'}
     */
    public function verify(VoiceSession $session, string $candidate): array
    {
        $record = $this->pinFor($session);

        if (! $record) {
            return ['status' => 'missing'];
        }

        if ($record->isLocked()) {
            return ['status' => 'locked'];
        }

        $candidate = preg_replace('/\D+/', '', $candidate) ?? '';

        if ($candidate !== '' && Hash::check($candidate, $record->pin_hash)) {
            $record->update([
                'failed_attempts' => 0,
                'locked_until' => null,
                'last_used_at' => now(),
            ]);

            $session->update([
                'pin_verified' => true,
                'pin_attempts' => 0,
            ]);

            Log::info('Voice PIN verified', [
                'call_id' => $session->call_id,
                'parent_id' => $session->parent_id,
            ]);

            return ['status' => 'ok'];
        }

        $attempts = (int) $session->pin_attempts + 1;
        $session->update(['pin_attempts' => $attempts]);
        $record->increment('failed_attempts');

        if ($attempts >= self::MAX_ATTEMPTS) {
            $record->update(['locked_until' => now()->addMinutes(self::LOCK_MINUTES)]);

            Log::warning('Voice PIN locked after failed attempts', [
                'call_id' => $session->call_id,
                'parent_id' => $session->parent_id,
            ]);

            return ['status' => 'exhausted'];
        }

        return ['status' => 'invalid'];
    }

    public function setPin(int $institutionId, StudentParent $parent, string $pin, ?int $setBy = null): VoiceParentPin
    {
        return VoiceParentPin::updateOrCreate(
            ['institution_id' => $institutionId, 'parent_id' => $parent->id],
            [
                'pin_hash' => Hash::make($pin),
                'failed_attempts' => 0,
                'locked_until' => null,
                'set_by' => $setBy,
            ]
        );
    }

    public function clearPin(int $institutionId, int $parentId): void
    {
        VoiceParentPin::where('institution_id', $institutionId)
            ->where('parent_id', $parentId)
            ->delete();
    }
}
