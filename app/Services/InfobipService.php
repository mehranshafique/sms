<?php

namespace App\Services\Sms;

use App\Interfaces\SmsGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InfobipService implements SmsGatewayInterface
{
    protected $baseUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('sms.infobip.base_url');
        $this->apiKey = config('sms.infobip.api_key');
    }

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
                        'from' => 'Digitex',
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
}