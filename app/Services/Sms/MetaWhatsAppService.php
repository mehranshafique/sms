<?php

namespace App\Services\Sms;

use App\Interfaces\SmsGatewayInterface;
use App\Models\InstitutionSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;

class MetaWhatsAppService implements SmsGatewayInterface
{
    protected $accessToken;
    protected $phoneNumberId;
    protected $version = 'v18.0';

    public function __construct($institutionId = null)
    {
        $query = InstitutionSetting::query();
        if (is_null($institutionId)) {
            $query->whereNull('institution_id');
        } else {
            $query->where('institution_id', $institutionId);
        }

        $settings = $query->whereIn('key', ['meta_access_token', 'meta_phone_number_id'])->pluck('value', 'key');

        if (isset($settings['meta_access_token'])) {
            try {
                $this->accessToken = Crypt::decryptString($settings['meta_access_token']);
                $this->phoneNumberId = $settings['meta_phone_number_id'];
            } catch (\Exception $e) {}
        }
    }

    public function send(string $to, string $message): array
    {
        return $this->sendWhatsApp($to, $message);
    }

    public function sendSms(string $to, string $message): array
    {
        return ['success' => false, 'message' => 'Meta does not support SMS.'];
    }

    public function sendWhatsApp(string $to, string $message): array
    {
        if (!$this->accessToken || !$this->phoneNumberId) {
            return ['success' => false, 'message' => __('configuration.meta_credentials_missing')];
        }

        try {
            $url = "https://graph.facebook.com/{$this->version}/{$this->phoneNumberId}/messages";
            
            $response = Http::withToken($this->accessToken)->post($url, [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => preg_replace('/[^0-9]/', '', $to),
                'type' => 'text',
                'text' => ['preview_url' => false, 'body' => $message]
            ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => __('configuration.whatsapp_sent_success')];
            }
            
            $err = $response->json()['error']['message'] ?? 'Meta API Error';
            return ['success' => false, 'message' => $err];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => __('configuration.gateway_connection_error')];
        }
    }
}