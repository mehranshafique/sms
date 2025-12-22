<?php

namespace App\Services\Sms;

use App\Interfaces\SmsGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MobishastraService implements SmsGatewayInterface
{
    protected $creds;
    protected $headers;
    protected $baseUrl = 'https://mshastra.com/sendurlcomma.aspx';

    public function __construct()
    {
        // Load credentials from config
        $this->creds = config('sms.mobishastra');
        
        $this->headers = [
            'AppID'     => $this->creds['app_id'] ?? 'huidu_liang',
            'AppSecret' => $this->creds['app_secret'] ?? '',
            'Cookie'    => 'ASP.NET_SessionId=x41ydxkwjfy3kfk3kfm0sa1a',
        ];
    }

    public function send(string $to, string $message): bool
    {
        try {
            // 1. Sanitize Phone Number
            $cleanNumber = preg_replace('/[^0-9]/', '', $to);
            
            // 2. Extract Country Code (assuming logic from your snippet: first 3 chars)
            // Adjust this logic if country codes vary (e.g. +92 vs +1)
            $countryCode = substr($cleanNumber, 0, 3); 

            // 3. Prepare Parameters
            $queryParams = [
                'user'        => $this->creds['user'],
                'pwd'         => $this->creds['password'],
                'senderid'    => $this->creds['sender_id'],
                'CountryCode' => '+' . $countryCode,
                'mobileno'    => '+' . $cleanNumber,
                'msgtext'     => $message,
                'smstype'     => '0/4/3' // As per your provided file
            ];

            // 4. Send Request
            // withoutVerifying() is used because your snippet had 'verify' => false
            // In production, fixing SSL on the server is recommended instead.
            $response = Http::withHeaders($this->headers)
                ->withoutVerifying() 
                ->timeout(50)
                ->get($this->baseUrl, $queryParams);

            if ($response->successful()) {
                Log::info('Mobishastra SMS Sent', [
                    'to' => $cleanNumber, 
                    'response' => $response->body()
                ]);
                return true;
            }

            Log::error('Mobishastra SMS Failed', [
                'status' => $response->status(), 
                'body' => $response->body()
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('Mobishastra Exception: ' . $e->getMessage());
            // We return false so the app doesn't crash, just logs the failure
            return false;
        }
    }
}