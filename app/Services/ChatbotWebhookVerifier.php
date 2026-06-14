<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatbotWebhookVerifier
{
    public function verify(Request $request, string $provider): bool
    {
        return match (strtolower($provider)) {
            'twilio' => $this->verifyTwilio($request),
            'meta' => $this->verifyMeta($request),
            'infobip' => $this->verifyInfobip($request),
            default => $this->verifySharedSecret($request),
        };
    }

    private function verifyTwilio(Request $request): bool
    {
        $token = config('services.chatbot.twilio_auth_token');
        if (empty($token)) {
            Log::warning('Chatbot Twilio webhook rejected: TWILIO_AUTH_TOKEN not configured.');
            return false;
        }

        $signature = $request->header('X-Twilio-Signature', '');
        if ($signature === '') {
            return false;
        }

        $url = $request->fullUrl();
        $params = $request->post();
        ksort($params);
        $data = $url;
        foreach ($params as $key => $value) {
            $data .= $key . $value;
        }

        $expected = base64_encode(hash_hmac('sha1', $data, $token, true));

        return hash_equals($expected, $signature);
    }

    private function verifyMeta(Request $request): bool
    {
        $secret = config('services.chatbot.meta_app_secret');
        if (empty($secret)) {
            Log::warning('Chatbot Meta webhook rejected: META_WHATSAPP_APP_SECRET not configured.');
            return false;
        }

        $signature = $request->header('X-Hub-Signature-256', '');
        if (!str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }

    private function verifyInfobip(Request $request): bool
    {
        $apiKey = config('services.chatbot.infobip_api_key');
        if (empty($apiKey)) {
            Log::warning('Chatbot Infobip webhook rejected: INFOBIP_API_KEY not configured.');
            return false;
        }

        $provided = $request->header('Authorization', '');
        if (str_starts_with($provided, 'App ')) {
            $provided = substr($provided, 4);
        }

        return hash_equals($apiKey, trim($provided));
    }

    private function verifySharedSecret(Request $request): bool
    {
        $secret = config('services.chatbot.webhook_secret', env('CHATBOT_WEBHOOK_SECRET'));
        if (empty($secret)) {
            Log::warning('Chatbot webhook rejected: CHATBOT_WEBHOOK_SECRET not configured.');
            return false;
        }

        $provided = $request->header('X-Chatbot-Secret', '');

        return hash_equals($secret, (string) $provided);
    }
}
