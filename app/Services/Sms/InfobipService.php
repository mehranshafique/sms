<?php

namespace App\Services\Sms;

use App\Interfaces\SmsGatewayInterface;
use App\Models\InstitutionSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;

class InfobipService implements SmsGatewayInterface
{
    protected $baseUrl;
    protected $apiKey;
    protected $whatsappSender;
    protected $senderId;

    public function __construct($institutionId = null)
    {
        // 1. Defaults
        $this->baseUrl = config('sms.infobip.base_url');
        $this->apiKey = config('sms.infobip.api_key');
        $this->whatsappSender = config('sms.infobip.whatsapp_from');
        $this->senderId = 'Digitex';

        // 2. DB Override
        $query = InstitutionSetting::query();
        if (is_null($institutionId)) {
            $query->whereNull('institution_id');
        } else {
            $query->where('institution_id', $institutionId);
        }

        $settings = $query->whereIn('key', ['infobip_api_key', 'infobip_base_url', 'infobip_sender_id'])
            ->pluck('value', 'key');

        if (isset($settings['infobip_api_key'])) {
            try {
                $this->apiKey = Crypt::decryptString($settings['infobip_api_key']);
                if (isset($settings['infobip_base_url'])) {
                    $this->baseUrl = $settings['infobip_base_url'];
                }
                if (isset($settings['infobip_sender_id'])) {
                    $this->senderId = $settings['infobip_sender_id'];
                }
            } catch (\Exception $e) {
                Log::error("Infobip Key Decryption Failed: " . $e->getMessage());
            }
        }
    }

    public function send(string $to, string $message): array
    {
        return $this->sendSms($to, $message);
    }

    public function sendSms(string $to, string $message): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "App {$this->apiKey}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(30)->post("{$this->baseUrl}/sms/2/text/advanced", [
                'messages' => [[
                    'destinations' => [['to' => $to]],
                    'from' => $this->senderId,
                    'text' => $message
                ]]
            ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => __('configuration.sms_sent_success')];
            }
            
            $err = $response->json()['requestError']['serviceException']['text'] ?? 'Unknown Error';
            Log::error("Infobip SMS Error: $err");
            return ['success' => false, 'message' => __('configuration.gateway_response_error')];
            
        } catch (\Exception $e) {
            return ['success' => false, 'message' => __('configuration.gateway_connection_error')];
        }
    }

    public function sendWhatsApp(string $to, string $message): array
    {
        try {
            $url = rtrim($this->baseUrl, '/') . '/whatsapp/1/message/text';
            
            $response = Http::withHeaders([
                'Authorization' => "App {$this->apiKey}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(30)->post($url, [
                'from' => $this->whatsappSender,
                'to' => $to,
                'content' => ['text' => $message]
            ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => __('configuration.whatsapp_sent_success')];
            }
            
            $err = $response->json()['requestError']['serviceException']['text'] ?? 'Unknown Error';
            Log::error("Infobip WhatsApp Error: $err");
            return ['success' => false, 'message' => __('configuration.gateway_response_error')];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => __('configuration.gateway_connection_error')];
        }
    }
}