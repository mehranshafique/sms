<?php

namespace App\Services;

use App\Models\InstitutionSetting;

class NotificationPreferenceService
{
    /** Events that bypass institution preference toggles (always deliver). */
    private const ALWAYS_ENABLED_EVENTS = [
        'institution_created',
        'low_balance',
        'subscription_expiry',
        'subscription_expired',
        'system_alert',
        'user_welcome',
        'otp_verification',
    ];

    /**
     * Check whether a notification channel is enabled for an event at a school.
     *
     * When no preference row exists yet:
     * - system (in-app bell): enabled by default
     * - sms / whatsapp / email: disabled by default (cost / opt-in)
     */
    public function isChannelEnabled(?int $institutionId, string $eventKey, string $channel): bool
    {
        if (in_array($eventKey, self::ALWAYS_ENABLED_EVENTS, true)) {
            return true;
        }

        $prefs = $this->getEventPreferences($institutionId, $eventKey);

        if ($prefs === null) {
            return $channel === 'system';
        }

        return !empty($prefs[$channel]);
    }

    public function isSystemEnabled(?int $institutionId, string $eventKey): bool
    {
        return $this->isChannelEnabled($institutionId, $eventKey, 'system');
    }

    /**
     * @return array<string, bool>|null  null when no notify_{eventKey} setting exists
     */
    public function getEventPreferences(?int $institutionId, string $eventKey): ?array
    {
        $settingKey = 'notify_' . $eventKey;

        $raw = InstitutionSetting::where('institution_id', $institutionId)
            ->where('key', $settingKey)
            ->value('value');

        if (!$raw && $institutionId) {
            $raw = InstitutionSetting::whereNull('institution_id')
                ->where('key', $settingKey)
                ->value('value');
        }

        if (!$raw) {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }
}
