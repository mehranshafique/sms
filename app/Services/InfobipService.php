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
        $this->whatsappSender = config('sms.infobip.whatsapp_from'); // Configurable sender
    }

    /**
     * Default send method (SMS)
     */
    public function send(string $to, string $message): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "App {$this->apiKey}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post("{$this->baseUrl}/sms/2/text/advanced", [
                'messages' => [
                    [
                        'destinations' => [['to' => $to]],
                        'from' => 'Digitex', // Default SMS Sender ID
                        'text' => $message
                    ]
                ]
            ]);

            if ($response->successful()) {
                return true;
            }
            
            Log::error('Infobip SMS Error: ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('Infobip Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send WhatsApp Message
     * Endpoint: /whatsapp/1/message/text
     */
    public function sendWhatsApp(string $to, string $message): bool
    {
        try {
            // Ensure URL has https://
            $url = rtrim($this->baseUrl, '/') . '/whatsapp/1/message/text';
            
            $payload = [
                'from' => $this->whatsappSender,
                'to' => $to,
                'content' => [
                    'text' => $message
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => "App {$this->apiKey}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($url, $payload);

            if ($response->successful()) {
                Log::info("Infobip WhatsApp Sent to {$to}");
                return true;
            }
            
            Log::error('Infobip WhatsApp Error: ' . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error('Infobip WhatsApp Exception: ' . $e->getMessage());
            return false;
        }
    }
}