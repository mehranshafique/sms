<?php

namespace App\Services\Sms;

use App\Interfaces\SmsGatewayInterface;
use App\Models\InstitutionSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;

class SignalWireService implements SmsGatewayInterface
{
    protected $projectId;
    protected $token;
    protected $spaceUrl;
    protected $fromNumber;

    public function __construct($institutionId = null)
    {
        $query = InstitutionSetting::query();
        if (is_null($institutionId)) {
            $query->whereNull('institution_id');
        } else {
            $query->where('institution_id', $institutionId);
        }

        $settings = $query->whereIn('key', ['sw_project_id', 'sw_token', 'sw_space_url', 'sw_from'])
            ->pluck('value', 'key');

        if (isset($settings['sw_token'])) {
            try {
                $this->token = Crypt::decryptString($settings['sw_token']);
                $this->projectId = $settings['sw_project_id'] ?? '';
                $this->spaceUrl = $settings['sw_space_url'] ?? '';
                $this->fromNumber = $settings['sw_from'] ?? '';
            } catch (\Exception $e) {}
        }
    }

    public function send(string $to, string $message): array
    {
        return $this->sendSms($to, $message);
    }

    public function sendSms(string $to, string $message): array
    {
        if (!$this->projectId || !$this->token || !$this->spaceUrl) {
            return ['success' => false, 'message' => __('configuration.sw_credentials_missing')];
        }

        try {
            $url = "https://{$this->spaceUrl}/api/laml/2010-04-01/Accounts/{$this->projectId}/Messages.json";

            $response = Http::withBasicAuth($this->projectId, $this->token)->asForm()->post($url, [
                'To' => '+' . preg_replace('/[^0-9]/', '', $to),
                'From' => '+' . preg_replace('/[^0-9]/', '', $this->fromNumber),
                'Body' => $message
            ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => __('configuration.sms_sent_success')];
            }

            return ['success' => false, 'message' => 'SignalWire: ' . ($response->json()['message'] ?? 'Unknown Error')];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => __('configuration.gateway_connection_error')];
        }
    }

    public function sendWhatsApp(string $to, string $message): array
    {
        return ['success' => false, 'message' => 'Not supported by SignalWire driver yet.'];
    }
}