<?php

namespace App\Services\Voice;

use App\Models\InstitutionSetting;
use App\Models\VoiceSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Phase 4: hand the caller over to a human at the school.
 *
 * Only Infobip Conversations-style endpoints are allowed (WhatsApp, WebRTC,
 * Viber). PSTN/landline bridging is deliberately unsupported because Meta and
 * Infobip forbid it for WhatsApp Business Calling.
 */
class VoiceTransferService
{
    public const ALLOWED_ENDPOINTS = ['webrtc', 'whatsapp', 'viber'];

    public function isEnabled(?int $institutionId): bool
    {
        if (! $institutionId) {
            return false;
        }

        if ((string) InstitutionSetting::get($institutionId, 'voice_ivr_transfer_enabled', '0') !== '1') {
            return false;
        }

        return $this->identity($institutionId) !== null;
    }

    public function endpointType(?int $institutionId): string
    {
        $type = strtolower((string) InstitutionSetting::get($institutionId, 'voice_ivr_transfer_endpoint_type', 'whatsapp'));

        return in_array($type, self::ALLOWED_ENDPOINTS, true) ? $type : 'whatsapp';
    }

    public function identity(?int $institutionId): ?string
    {
        $identity = trim((string) InstitutionSetting::get($institutionId, 'voice_ivr_transfer_identity', ''));

        return $identity !== '' ? $identity : null;
    }

    /**
     * Schools can restrict transfers to office hours; outside them the caller
     * hears the secretary message instead of ringing an empty desk.
     */
    public function isWithinWorkingHours(?int $institutionId, ?Carbon $now = null): bool
    {
        $start = trim((string) InstitutionSetting::get($institutionId, 'voice_ivr_transfer_hours_start', ''));
        $end = trim((string) InstitutionSetting::get($institutionId, 'voice_ivr_transfer_hours_end', ''));

        if ($start === '' || $end === '') {
            return true;
        }

        $now = $now ?? now();

        try {
            $from = Carbon::parse($start, $now->timezone)->setDateFrom($now);
            $to = Carbon::parse($end, $now->timezone)->setDateFrom($now);
        } catch (\Throwable $e) {
            return true;
        }

        if ($to->lessThanOrEqualTo($from)) {
            return true;
        }

        return $now->betweenIncluded($from, $to);
    }

    public function isAvailable(?int $institutionId): bool
    {
        return $this->isEnabled($institutionId) && $this->isWithinWorkingHours($institutionId);
    }

    public function markTransferred(VoiceSession $session): void
    {
        $session->update([
            'state' => VoiceSession::STATE_TRANSFER,
            'transferred_at' => now(),
            'meta' => array_merge($session->meta ?? [], [
                'transfer_endpoint' => $this->endpointType($session->institution_id),
            ]),
        ]);

        Log::info('Voice call transferred to school agent', [
            'call_id' => $session->call_id,
            'institution_id' => $session->institution_id,
            'endpoint' => $this->endpointType($session->institution_id),
        ]);
    }
}
