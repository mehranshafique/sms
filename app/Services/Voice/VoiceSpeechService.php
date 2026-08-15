<?php

namespace App\Services\Voice;

use App\Services\Ai\AiManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Speech-to-text for the Phase 3 voice agent.
 *
 * Uses the same OpenAI-compatible provider as the rest of the AI layer, hitting
 * the /audio/transcriptions endpoint. Never throws: a failed transcription just
 * means the IVR falls back to the keypad menu.
 */
class VoiceSpeechService
{
    public function __construct(
        protected AiManager $ai,
        protected InfobipVoiceClient $client
    ) {
    }

    /**
     * @return array{ok: bool, text: ?string, error: ?string}
     */
    public function transcribeRecording(?int $institutionId, ?string $fileId, ?string $fileUrl, string $locale = 'fr'): array
    {
        $audio = $this->client->downloadRecording($institutionId, $fileId, $fileUrl);

        if ($audio === null || $audio === '') {
            return ['ok' => false, 'text' => null, 'error' => 'recording_unavailable'];
        }

        return $this->transcribeBytes($institutionId, $audio, $locale);
    }

    /**
     * @return array{ok: bool, text: ?string, error: ?string}
     */
    public function transcribeBytes(?int $institutionId, string $audio, string $locale = 'fr'): array
    {
        $creds = $this->ai->resolveCredentials($institutionId);

        if (empty($creds['key'])) {
            return ['ok' => false, 'text' => null, 'error' => 'not_configured'];
        }

        $baseUrl = rtrim($creds['base_url'] ?? config('ai.base_url'), '/');
        $model = config('ai.stt_model', 'whisper-1');

        try {
            $response = Http::withToken($creds['key'])
                ->timeout((int) config('ai.stt_timeout', 30))
                ->attach('file', $audio, 'call.wav')
                ->post($baseUrl . '/audio/transcriptions', [
                    'model' => $model,
                    'language' => $locale === 'en' ? 'en' : 'fr',
                    'response_format' => 'json',
                ]);

            if (! $response->successful()) {
                Log::warning('Voice transcription failed', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 300),
                ]);

                return ['ok' => false, 'text' => null, 'error' => 'provider_error'];
            }

            $text = trim((string) ($response->json('text') ?? ''));

            if ($text === '') {
                return ['ok' => false, 'text' => null, 'error' => 'empty_transcript'];
            }

            return ['ok' => true, 'text' => mb_substr($text, 0, 500), 'error' => null];
        } catch (\Throwable $e) {
            Log::warning('Voice transcription exception: ' . $e->getMessage());

            return ['ok' => false, 'text' => null, 'error' => 'exception'];
        }
    }
}
