<?php

namespace App\Services\Sms;

use App\Interfaces\SmsGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MobishastraService implements SmsGatewayInterface
{
    protected $creds;
    protected $baseUrl = 'https://mshastra.com/sendurlcomma.aspx';

    public function __construct()
    {
        $this->creds = config('sms.mobishastra');
    }

    public function send(string $to, string $message): array
    {
        try {
            $cleanNumber = preg_replace('/[^0-9]/', '', $to);
            $countryCode = substr($cleanNumber, 0, 3); 

            $queryParams = [
                'user'        => $this->creds['user'],
                'pwd'         => $this->creds['password'],
                'senderid'    => $this->creds['sender_id'],
                'CountryCode' => '+' . $countryCode,
                'mobileno'    => '+' . $cleanNumber,
                'msgtext'     => $message,
                'smstype'     => '0/4/3'
            ];

            // Use HTTP Client
            $response = Http::withoutVerifying()
                ->timeout(30)
                ->get($this->baseUrl, $queryParams);

            $body = $response->body();

            // 1. Check HTTP Status
            if ($response->successful()) {
                // 2. Check Provider Response Text
                // Mobishastra returns "Send Successful" or similar positive string on 200 OK
                if (stripos($body, 'Successful') !== false || stripos($body, 'Active') !== false) {
                    Log::info('Mobishastra Sent', ['to' => $cleanNumber]);
                    return ['success' => true, 'message' => __('configuration.sms_sent_success')];
                }
                
                // If 200 OK but text indicates error (e.g. "Invalid Login")
                Log::error('Mobishastra API Error', ['body' => $body]);
                return ['success' => false, 'message' => __('configuration.gateway_response_error') . ': ' . Str::limit($body, 50)];
            }

            // HTTP Error (4xx, 5xx)
            Log::error('Mobishastra HTTP Error', ['status' => $response->status()]);
            return ['success' => false, 'message' => __('configuration.gateway_response_error')];

        } catch (\Exception $e) {
            // SECURITY CRITICAL: Never return $e->getMessage() directly as it contains URL parameters with password
            Log::error('Mobishastra Connection Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false, 
                'message' => __('configuration.gateway_connection_error')
            ];
        }
    }
}