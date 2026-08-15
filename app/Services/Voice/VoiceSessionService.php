<?php

namespace App\Services\Voice;

use App\Models\InstitutionSetting;
use App\Models\VoiceSession;
use Illuminate\Support\Facades\Log;

class VoiceSessionService
{
    public function findByCallId(string $callId): ?VoiceSession
    {
        return VoiceSession::where('call_id', $callId)->first();
    }

    public function startOrResume(
        string $callId,
        string $phoneNumber,
        ?string $toNumber,
        ?int $institutionId,
        string $locale = 'fr'
    ): VoiceSession {
        $session = $this->findByCallId($callId);

        if ($session) {
            $session->fill([
                'phone_number' => $phoneNumber,
                'to_number' => $toNumber,
                'institution_id' => $institutionId ?? $session->institution_id,
                'locale' => $locale ?: $session->locale,
            ]);
            $session->save();

            return $session->fresh();
        }

        return VoiceSession::create([
            'call_id' => $callId,
            'phone_number' => $phoneNumber,
            'to_number' => $toNumber,
            'institution_id' => $institutionId,
            'locale' => $locale,
            'state' => VoiceSession::STATE_WELCOME,
            'menu_profile' => 'guest',
            'turns' => 0,
            'started_at' => now(),
        ]);
    }

    public function bumpTurn(VoiceSession $session, ?string $digit = null): VoiceSession
    {
        $session->turns = (int) $session->turns + 1;
        if ($digit !== null && $digit !== '') {
            $session->last_digit = $digit;
        }
        $session->save();

        return $session;
    }

    public function maxTurns(int $institutionId): int
    {
        $raw = InstitutionSetting::get($institutionId, 'voice_ivr_max_turns', 8);

        return max(3, min(30, (int) $raw));
    }

    public function end(VoiceSession $session, ?string $reason = null): VoiceSession
    {
        $meta = $session->meta ?? [];
        if ($reason) {
            $meta['end_reason'] = $reason;
        }

        $session->update([
            'state' => VoiceSession::STATE_ENDED,
            'ended_at' => now(),
            'meta' => $meta,
        ]);

        Log::info('Voice session ended', [
            'call_id' => $session->call_id,
            'institution_id' => $session->institution_id,
            'reason' => $reason,
            'turns' => $session->turns,
        ]);

        return $session;
    }
}
