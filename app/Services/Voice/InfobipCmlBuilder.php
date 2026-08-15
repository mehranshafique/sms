<?php

namespace App\Services\Voice;

class InfobipCmlBuilder
{
    protected string $dtmfCallbackUrl;

    protected int $dtmfTimeout;

    public function __construct(?string $dtmfCallbackUrl = null, int $dtmfTimeout = 8)
    {
        $this->dtmfTimeout = $dtmfTimeout;
        $this->dtmfCallbackUrl = $dtmfCallbackUrl ?: $this->defaultDtmfUrl();
    }

    protected function defaultDtmfUrl(): string
    {
        return $this->callbackUrl('dtmf');
    }

    public function callbackUrl(string $path): string
    {
        $base = rtrim((string) config('app.url'), '/');
        $secret = config('services.chatbot.webhook_secret');
        $query = $secret ? ('?secret=' . urlencode($secret)) : '';

        return $base . '/api/v1/voice/infobip/' . trim($path, '/') . $query;
    }

    /**
     * @return array{actions: list<array<string, mixed>>}
     */
    public function sayAndCapture(string $text, string $language = 'fr'): array
    {
        return [
            'actions' => [
                $this->sayAction($text, $language),
                $this->captureDtmfAction(),
            ],
        ];
    }

    /**
     * @return array{actions: list<array<string, mixed>>}
     */
    public function sayAndHangup(string $text, string $language = 'fr'): array
    {
        return [
            'actions' => [
                $this->sayAction($text, $language),
                ['action' => 'hangup'],
            ],
        ];
    }

    /**
     * @return array{actions: list<array<string, mixed>>}
     */
    public function hangup(): array
    {
        return [
            'actions' => [
                ['action' => 'hangup'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function sayAction(string $text, string $language = 'fr'): array
    {
        $lang = str_starts_with(strtolower($language), 'en') ? 'en' : 'fr';

        return [
            'action' => 'say',
            'text' => $this->sanitizeTts($text),
            'language' => $lang,
            'voicePreferences' => [
                'voiceGender' => 'FEMALE',
            ],
        ];
    }

    /**
     * Say a prompt, then collect a multi-digit value such as a PIN.
     *
     * @return array{actions: list<array<string, mixed>>}
     */
    public function sayAndCaptureDigits(string $text, string $language, int $maxLength, ?string $terminator = '#'): array
    {
        return [
            'actions' => [
                $this->sayAction($text, $language),
                $this->captureDtmfAction($maxLength, $terminator),
            ],
        ];
    }

    /**
     * Say a prompt, record the caller's spoken question, then hand the recording
     * to the callback which returns the next actions (Phase 3 AI agent).
     *
     * @return array{actions: list<array<string, mixed>>}
     */
    public function sayAndRecord(string $text, string $language, int $timeout = 12, int $maxSilence = 3): array
    {
        return [
            'actions' => [
                $this->sayAction($text, $language),
                [
                    'action' => 'record',
                    'timeout' => $timeout,
                    'maxSilence' => $maxSilence,
                    'playBeep' => true,
                    'escapeDtmf' => '*',
                    'callback' => [
                        'url' => $this->callbackUrl('recording'),
                        'method' => 'POST',
                    ],
                ],
            ],
        ];
    }

    /**
     * Bridge the caller to a school agent. WhatsApp / WebRTC / Viber only —
     * PSTN bridging is intentionally not supported.
     *
     * @return array{actions: list<array<string, mixed>>}
     */
    public function sayAndDial(
        string $text,
        string $language,
        string $endpointType,
        string $identity,
        int $connectTimeout = 25,
        int $maxDuration = 900
    ): array {
        $type = strtoupper($endpointType);
        $endpoint = ['type' => $type];

        if ($type === 'WEBRTC') {
            $endpoint['identity'] = $identity;
        } else {
            $endpoint['phoneNumber'] = preg_replace('/\D+/', '', $identity) ?: $identity;
        }

        return [
            'actions' => [
                $this->sayAction($text, $language),
                [
                    'action' => 'dial',
                    'endpoints' => [$endpoint],
                    'connectTimeout' => max(5, min(30, $connectTimeout)),
                    'maxDuration' => $maxDuration,
                    'answerBeforeConnecting' => true,
                    'callback' => [
                        'url' => $this->callbackUrl('transfer'),
                        'method' => 'POST',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function captureDtmfAction(int $maxLength = 1, ?string $terminator = null): array
    {
        $action = [
            'action' => 'captureDtmf',
            'maxLength' => max(1, min(12, $maxLength)),
            'timeout' => $this->dtmfTimeout,
            'callOnEmpty' => true,
            'callback' => [
                'url' => $this->dtmfCallbackUrl ?: $this->defaultDtmfUrl(),
                'method' => 'POST',
            ],
        ];

        if ($terminator !== null && $terminator !== '') {
            $action['terminator'] = $terminator;
        }

        return $action;
    }

    protected function sanitizeTts(string $text): string
    {
        $text = strip_tags($text);
        $text = preg_replace('/\*+/', '', $text) ?? $text;
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim(mb_substr($text, 0, 900));
    }
}
