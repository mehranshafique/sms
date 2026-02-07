<?php

namespace App\Services\Sms;

use App\Interfaces\SmsGatewayInterface;
use App\Models\InstitutionSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;

class TwilioService implements SmsGatewayInterface
{
    protected $sid;
    protected $token;
    protected $fromNumber;
    protected $whatsappFrom;

    public function __construct($institutionId = null)
    {
        $this->sid = config('services.twilio.sid');
        $this->token = config('services.twilio.token');
        $this->fromNumber = config('services.twilio.from');

        $query = InstitutionSetting::query();
        if (is_null($institutionId)) {
            $query->whereNull('institution_id');
        } else {
            $query->where('institution_id', $institutionId);
        }

        $settings = $query->whereIn('key', ['twilio_sid', 'twilio_token', 'twilio_from', 'twilio_whatsapp_from'])
            ->pluck('value', 'key');

        if (isset($settings['twilio_token'])) {
            try {
                $this->token = Crypt::decryptString($settings['twilio_token']);
                $this->sid = $settings['twilio_sid'] ?? $this->sid;
                $this->fromNumber = $settings['twilio_from'] ?? $this->fromNumber;
                $this->whatsappFrom = $settings['twilio_whatsapp_from'] ?? $this->fromNumber;
            } catch (\Exception $e) {}
        }
    }

    public function send(string $to, string $message): array
    {
        return $this->sendSms($to, $message);
    }

    public function sendSms(string $to, string $message): array
    {
        return $this->dispatch($to, $this->fromNumber, $message);
    }

    public function sendWhatsApp(string $to, string $message): array
    {
        $to = 'whatsapp:' . $this->formatNumber($to);
        $from = 'whatsapp:' . $this->formatNumber($this->whatsappFrom);
        return $this->dispatch($to, $from, $message);
    }

    protected function dispatch($to, $from, $message)
    {
        if (!$this->sid || !$this->token) {
            return ['success' => false, 'message' => __('configuration.twilio_credentials_missing')];
        }

        try {
            $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->sid}/Messages.json";
            
            $response = Http::withBasicAuth($this->sid, $this->token)->asForm()->post($url, [
                'To' => $to,
                'From' => $from,
                'Body' => $message
            ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => __('configuration.sms_sent_success')];
            }

            return ['success' => false, 'message' => 'Twilio: ' . ($response->json()['message'] ?? 'Unknown Error')];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => __('configuration.gateway_connection_error')];
        }
    }

    private function formatNumber($number)
    {
        return preg_replace('/[^0-9]/', '', $number);
    }
}