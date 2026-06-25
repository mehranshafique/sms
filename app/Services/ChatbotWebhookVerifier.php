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
            'telegram' => $this->verifyTelegram($request),
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
        // Infobip inbound webhooks often do NOT send Authorization headers.
        // Accept: App API key, shared secret header/query, or explicit skip flag.

        $apiKey = config('services.chatbot.infobip_api_key');
        if (!empty($apiKey)) {
            $provided = $request->header('Authorization', '');
            if (str_starts_with($provided, 'App ')) {
                $provided = substr($provided, 4);
            }
            if (trim($provided) !== '' && hash_equals($apiKey, trim($provided))) {
                return true;
            }
        }

        if ($this->matchesSharedSecret($request)) {
            return true;
        }

        if (config('services.chatbot.infobip_skip_verify', false)) {
            Log::info('Chatbot Infobip webhook accepted (INFOBIP_WEBHOOK_SKIP_VERIFY=true).');
            return true;
        }

        Log::warning('Chatbot Infobip webhook rejected: set INFOBIP_API_KEY, CHATBOT_WEBHOOK_SECRET, or INFOBIP_WEBHOOK_SKIP_VERIFY=true.');
        return false;
    }

    private function verifyTelegram(Request $request): bool
    {
        $botToken = config('services.chatbot.telegram_bot_token');
        if (empty($botToken)) {
            Log::warning('Chatbot Telegram webhook rejected: TELEGRAM_BOT_TOKEN not configured.');
            return false;
        }

        // Telegram does not sign webhooks — use shared secret in URL or header.
        if ($this->matchesSharedSecret($request)) {
            return true;
        }

        if (config('services.chatbot.telegram_skip_verify', false)) {
            return true;
        }

        Log::warning('Chatbot Telegram webhook rejected: append ?secret=YOUR_CHOTBOT_WEBHOOK_SECRET to the webhook URL.');
        return false;
    }

    private function verifySharedSecret(Request $request): bool
    {
        if ($this->matchesSharedSecret($request)) {
            return true;
        }

        $secret = config('services.chatbot.webhook_secret', env('CHATBOT_WEBHOOK_SECRET'));
        if (empty($secret)) {
            Log::warning('Chatbot webhook rejected: CHATBOT_WEBHOOK_SECRET not configured.');
            return false;
        }

        return false;
    }

    private function matchesSharedSecret(Request $request): bool
    {
        $secret = config('services.chatbot.webhook_secret', env('CHATBOT_WEBHOOK_SECRET'));
        if (empty($secret)) {
            return false;
        }

        $provided = $request->header('X-Chatbot-Secret', '')
            ?: $request->query('secret', '')
            ?: $request->input('secret', '');

        return $provided !== '' && hash_equals($secret, (string) $provided);
    }
}
