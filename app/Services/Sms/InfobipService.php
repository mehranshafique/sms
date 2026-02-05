<?php

namespace App\Services\Sms;

use App\Interfaces\SmsGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InfobipService implements SmsGatewayInterface
{
    protected $baseUrl;
    protected $apiKey;
    protected $whatsappSender;

    public function __construct()
    {
        $this->baseUrl = config('sms.infobip.base_url');
        $this->apiKey = config('sms.infobip.api_key');
        $this->whatsappSender = config('sms.infobip.whatsapp_from');
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
                'messages' => [
                    [
                        'destinations' => [['to' => $to]],
                        'from' => 'Digitex',
                        'text' => $message
                    ]
                ]
            ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => __('configuration.sms_sent_success')];
            }
            
            $err = $response->json()['requestError']['serviceException']['text'] ?? 'Unknown Error';
            Log::error("Infobip SMS Error: $err");
            return ['success' => false, 'message' => __('configuration.gateway_response_error')];
            
        } catch (\Exception $e) {
            Log::error('Infobip Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => __('configuration.gateway_connection_error')];
        }
    }

    public function sendWhatsApp(string $to, string $message): array
    {
        try {
            $url = rtrim($this->baseUrl, '/') . '/whatsapp/1/message/text';
            
            $payload = [
                'from' => $this->whatsappSender,
                'to' => $to,
                'content' => ['text' => $message]
            ];

            $response = Http::withHeaders([
                'Authorization' => "App {$this->apiKey}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(30)->post($url, $payload);

            if ($response->successful()) {
                return ['success' => true, 'message' => __('configuration.whatsapp_sent_success')];
            }
            
            $err = $response->json()['requestError']['serviceException']['text'] ?? 'Unknown Error';
            Log::error("Infobip WhatsApp Error: $err");
            return ['success' => false, 'message' => __('configuration.gateway_response_error')];

        } catch (\Exception $e) {
            Log::error('Infobip WhatsApp Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => __('configuration.gateway_connection_error')];
        }
    }
}