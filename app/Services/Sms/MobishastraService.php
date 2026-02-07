<?php

namespace App\Services\Sms;

use App\Interfaces\SmsGatewayInterface;
use App\Models\InstitutionSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class MobishastraService implements SmsGatewayInterface
{
    protected $user;
    protected $password;
    protected $senderId;
    protected $baseUrl = 'https://mshastra.com/sendurlcomma.aspx';

    /**
     * @param int|null $institutionId If null, loads global settings. If set, loads school settings.
     */
    public function __construct($institutionId = null)
    {
        // 1. Defaults from Config (fallback)
        $this->user = config('sms.mobishastra.user');
        $this->password = config('sms.mobishastra.password');
        $this->senderId = config('sms.mobishastra.sender_id');

        // 2. Load Settings from DB based on context
        $query = InstitutionSetting::query();
        
        if (is_null($institutionId)) {
            $query->whereNull('institution_id');
        } else {
            $query->where('institution_id', $institutionId);
        }

        $settings = $query->whereIn('key', ['mobishastra_api_key', 'mobishastra_sender_id'])
            ->pluck('value', 'key');

        if (isset($settings['mobishastra_api_key'])) {
            try {
                // Assuming 'api_key' stores the password in encrypted format
                $this->password = Crypt::decryptString($settings['mobishastra_api_key']);
                
                // If Sender ID is overridden
                if (isset($settings['mobishastra_sender_id'])) {
                    $this->senderId = $settings['mobishastra_sender_id'];
                }
            } catch (\Exception $e) {
                Log::error("Mobishastra Key Decryption Failed: " . $e->getMessage());
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
            $cleanNumber = preg_replace('/[^0-9]/', '', $to);
            $countryCode = substr($cleanNumber, 0, 3); 

            $queryParams = [
                'user'        => $this->user,
                'pwd'         => $this->password,
                'senderid'    => $this->senderId,
                'CountryCode' => '+' . $countryCode,
                'mobileno'    => '+' . $cleanNumber,
                'msgtext'     => $message,
                'smstype'     => '0/4/3' // Standard ASCII
            ];

            // Use HTTP Client without verification for legacy APIs if needed, though withVerifying is better
            $response = Http::withoutVerifying()
                ->timeout(30)
                ->get($this->baseUrl, $queryParams);

            $body = $response->body();

            if ($response->successful()) {
                // Check Provider Response Text for success keywords
                if (stripos($body, 'Successful') !== false || stripos($body, 'Active') !== false || stripos($body, 'Submitted') !== false) {
                    return ['success' => true, 'message' => __('configuration.sms_sent_success')];
                }
                
                Log::error('Mobishastra API Error', ['body' => $body]);
                return ['success' => false, 'message' => __('configuration.gateway_response_error') . ': ' . Str::limit($body, 50)];
            }

            return ['success' => false, 'message' => __('configuration.gateway_connection_error')];

        } catch (\Exception $e) {
            Log::error('Mobishastra Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => __('configuration.gateway_connection_error')];
        }
    }
    
    public function sendWhatsApp(string $to, string $message): array
    {
        return ['success' => false, 'message' => 'WhatsApp not supported by Mobishastra driver.'];
    }
}