<?php

namespace App\Services\Voice;

use App\Models\InstitutionSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Minimal Infobip Calls API helper for the voice IVR.
 *
 * Credentials follow the same resolution order as InfobipService: institution
 * settings first (per-school Infobip account), then platform config.
 */
class InfobipVoiceClient
{
    public function baseUrl(?int $institutionId): string
    {
        $subdomain = $institutionId
            ? InstitutionSetting::get($institutionId, 'infobip_subdomain')
            : null;

        if ($subdomain) {
            return 'https://' . trim($subdomain) . '.api.infobip.com';
        }

        return rtrim((string) config('sms.infobip.base_url'), '/');
    }

    public function apiKey(?int $institutionId): ?string
    {
        $stored = $institutionId
            ? InstitutionSetting::get($institutionId, 'infobip_api_key')
            : null;

        if ($stored) {
            try {
                return Crypt::decryptString($stored);
            } catch (\Throwable $e) {
                return $stored;
            }
        }

        return config('sms.infobip.api_key') ?: config('services.chatbot.infobip_api_key');
    }

    /**
     * Download a call recording as raw bytes, either from a direct URL supplied
     * in the webhook or by file id through the Calls API.
     */
    public function downloadRecording(?int $institutionId, ?string $fileId, ?string $fileUrl = null): ?string
    {
        $apiKey = $this->apiKey($institutionId);
        if (! $apiKey) {
            Log::warning('Voice recording download skipped: Infobip API key missing.');

            return null;
        }

        $url = $fileUrl;
        if (! $url) {
            if (! $fileId) {
                return null;
            }
            $url = $this->baseUrl($institutionId) . '/calls/1/recording/file/' . urlencode($fileId);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "App {$apiKey}",
            ])->timeout(30)->get($url);

            if (! $response->successful()) {
                Log::warning('Voice recording download failed', [
                    'status' => $response->status(),
                    'file_id' => $fileId,
                ]);

                return null;
            }

            return $response->body();
        } catch (\Throwable $e) {
            Log::warning('Voice recording download exception: ' . $e->getMessage());

            return null;
        }
    }
}
