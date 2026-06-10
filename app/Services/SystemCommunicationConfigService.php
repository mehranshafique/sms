<?php

namespace App\Services;

use App\Models\InstitutionSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

class SystemCommunicationConfigService
{
    /**
     * Apply global (institution_id = null) SMS/WhatsApp settings over config/.env defaults.
     */
    public function applyGlobalOverrides(): void
    {
        if (!Schema::hasTable('institution_settings')) {
            return;
        }

        $settings = InstitutionSetting::whereNull('institution_id')
            ->whereIn('group', ['sms', 'system'])
            ->pluck('value', 'key');

        if ($settings->isEmpty()) {
            return;
        }

        if ($settings->has('sms_provider')) {
            config(['sms.default' => $settings['sms_provider']]);
        }

        if ($settings->has('whatsapp_provider')) {
            config(['sms.whatsapp_default' => $settings['whatsapp_provider']]);
        }

        if ($settings->has('mobishastra_user')) {
            config(['sms.mobishastra.user' => $settings['mobishastra_user']]);
        }

        if ($settings->has('mobishastra_sender_id')) {
            config(['sms.mobishastra.sender_id' => $settings['mobishastra_sender_id']]);
        }

        if ($settings->has('mobishastra_password')) {
            $password = $this->decrypt($settings['mobishastra_password']);
            if ($password !== null) {
                config(['sms.mobishastra.password' => $password]);
            }
        }

        if ($settings->has('infobip_subdomain') && $settings['infobip_subdomain'] !== '') {
            config(['sms.infobip.base_url' => 'https://' . $settings['infobip_subdomain'] . '.api.infobip.com']);
        }

        if ($settings->has('infobip_whatsapp_from')) {
            config(['sms.infobip.whatsapp_from' => $settings['infobip_whatsapp_from']]);
        }

        if ($settings->has('infobip_api_key')) {
            $apiKey = $this->decrypt($settings['infobip_api_key']);
            if ($apiKey !== null) {
                config(['sms.infobip.api_key' => $apiKey]);
            }
        }
    }

    private function decrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }
}
