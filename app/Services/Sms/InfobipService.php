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

        $settings = $query->whereIn('key', ['infobip_api_key', 'infobip_base_url', 'infobip_sender_id', 'infobip_whatsapp_from'])
            ->pluck('value', 'key');

        if (isset($settings['infobip_api_key'])) {
            try {
                $this->apiKey = Crypt::decryptString($settings['infobip_api_key']);
                if (isset($settings['infobip_base_url'])) {
                    $url = $settings['infobip_base_url'];
                    // Ensure HTTPS
                    if (!str_starts_with($url, 'http')) {
                        $url = "https://$url.api.infobip.com";
                    }
                    $this->baseUrl = $url;
                }
                if (isset($settings['infobip_sender_id'])) $this->senderId = $settings['infobip_sender_id'];
                if (isset($settings['infobip_whatsapp_from'])) $this->whatsappSender = $settings['infobip_whatsapp_from'];
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
            return ['success' => false, 'message' => $err];
            
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
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
            
            $err = $response->json();
            Log::error("Infobip WhatsApp Error: " . json_encode($err));
            return ['success' => false, 'message' => 'Failed to send WhatsApp'];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send WhatsApp Document (PDF, Doc)
     */
    public function sendWhatsAppFile(string $to, string $fileUrl, string $caption = '', string $filename = 'document.pdf'): array
    {
        try {
            $url = rtrim($this->baseUrl, '/') . '/whatsapp/1/message/document';
            
            $payload = [
                'from' => $this->whatsappSender,
                'to' => $to,
                'content' => [
                    'mediaUrl' => $fileUrl,
                    'caption' => $caption,
                    'filename' => $filename
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => "App {$this->apiKey}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(30)->post($url, $payload);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Document sent successfully'];
            }
            
            $err = $response->json();
            Log::error("Infobip File Error: " . json_encode($err));
            return ['success' => false, 'message' => 'Failed to send document'];

        } catch (\Exception $e) {
            Log::error("Infobip File Exception: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send WhatsApp Image (JPG, PNG)
     */
    public function sendWhatsAppImage(string $to, string $imageUrl, string $caption = ''): array
    {
        try {
            $url = rtrim($this->baseUrl, '/') . '/whatsapp/1/message/image';
            
            $payload = [
                'from' => $this->whatsappSender,
                'to' => $to,
                'content' => [
                    'mediaUrl' => $imageUrl,
                    'caption' => $caption
                ],
                // Add tracking options if needed, but keeping simple for now to avoid errors
            ];

            $response = Http::withHeaders([
                'Authorization' => "App {$this->apiKey}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(30)->post($url, $payload);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Image sent successfully'];
            }
            
            $err = $response->json();
            Log::error("Infobip Image Error: " . json_encode($err));
            return ['success' => false, 'message' => 'Failed to send image'];

        } catch (\Exception $e) {
            Log::error("Infobip Image Exception: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}