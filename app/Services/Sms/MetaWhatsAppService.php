<?php

namespace App\Services\Sms;

use App\Interfaces\SmsGatewayInterface;
use App\Models\InstitutionSetting;
use App\Services\MessageLogService;
use App\Services\PaymentGateways\PaymentPhoneHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;

class MetaWhatsAppService implements SmsGatewayInterface
{
    protected $accessToken;
    protected $phoneNumberId;
    protected $version = 'v18.0';

    public function __construct($institutionId = null, $fallbackInstitutionId = null)
    {
        $this->loadCredentials($institutionId);

        if ((!$this->accessToken || !$this->phoneNumberId) && !is_null($fallbackInstitutionId) && $fallbackInstitutionId !== $institutionId) {
            $this->loadCredentials($fallbackInstitutionId);
        }

        if ((!$this->accessToken || !$this->phoneNumberId) && !is_null($institutionId)) {
            $this->loadCredentials(null);
        }
    }

    protected function loadCredentials($institutionId): void
    {
        $query = InstitutionSetting::query();
        if (is_null($institutionId)) {
            $query->whereNull('institution_id');
        } else {
            $query->where('institution_id', $institutionId);
        }

        $settings = $query->whereIn('key', ['meta_access_token', 'meta_phone_number_id'])->pluck('value', 'key');

        if (empty($settings['meta_access_token']) || empty($settings['meta_phone_number_id'])) {
            return;
        }

        try {
            $this->accessToken = Crypt::decryptString($settings['meta_access_token']);
            $this->phoneNumberId = $settings['meta_phone_number_id'];
        } catch (\Exception $e) {
            Log::error('Meta WhatsApp credential decryption failed: ' . $e->getMessage());
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

        $msisdn = PaymentPhoneHelper::toMsisdn($to, config('sms.default_country_code', '243'));
        if ($msisdn === '' || strlen($msisdn) < 10) {
            return [
                'success' => false,
                'message' => __('configuration.whatsapp_invalid_number'),
                'msisdn' => $msisdn,
            ];
        }

        try {
            $url = "https://graph.facebook.com/{$this->version}/{$this->phoneNumberId}/messages";

            $response = Http::withToken($this->accessToken)->post($url, [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $msisdn,
                'type' => 'text',
                'text' => ['preview_url' => false, 'body' => $message],
            ]);

            $payload = $response->json();

            if ($response->successful() && !empty($payload['messages'][0]['id'])) {
                return [
                    'success' => true,
                    'message' => __('configuration.whatsapp_sent_success'),
                    'provider_message_id' => $payload['messages'][0]['id'],
                    'msisdn' => $msisdn,
                ];
            }

            $err = $payload['error']['message'] ?? ($response->body() ?: 'Meta API Error');
            $code = $payload['error']['code'] ?? null;
            $errorDetails = $payload['error']['error_data']['details'] ?? null;

            // Meta rejects free-form text outside the 24h customer-care window.
            $isTemplateWindow = in_array((int) $code, [131047, 131026, 131051], true)
                || stripos($err, '24 hour') !== false
                || stripos($err, 'template') !== false;

            if ($isTemplateWindow) {
                $err = __('configuration.whatsapp_template_required');
            }

            Log::error('Meta WhatsApp send failed', [
                'response' => $payload,
                'to' => MessageLogService::maskPhone($msisdn),
                'error_code' => $code,
                'error_details' => $errorDetails,
            ]);

            return [
                'success' => false,
                'message' => $err,
                'error_code' => $code,
                'msisdn' => $msisdn,
                'error_details' => $errorDetails,
            ];

        } catch (\Exception $e) {
            Log::error('Meta WhatsApp connection error: ' . $e->getMessage(), [
                'to' => MessageLogService::maskPhone($msisdn),
            ]);

            return [
                'success' => false,
                'message' => __('configuration.gateway_connection_error'),
                'msisdn' => $msisdn,
            ];
        }
    }
}
