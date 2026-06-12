<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin, provider-agnostic client for an OpenAI-compatible Chat Completions API.
 *
 * This class NEVER throws into the calling flow. Any failure (network, auth,
 * malformed response) is caught and returned as a structured array so AI can
 * never break the surrounding request.
 */
class AiClient
{
    /**
     * @param array $messages  [['role'=>'system','content'=>...], ['role'=>'user','content'=>...]]
     * @param array $creds      ['key'=>, 'model'=>, 'base_url'=>]
     * @param array $opts       ['max_tokens'=>, 'temperature'=>]
     * @return array ['ok'=>bool, 'content'=>?string, 'usage'=>array, 'error'=>?string]
     */
    public function chat(array $messages, array $creds, array $opts = []): array
    {
        $usage = ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];

        if (empty($creds['key'])) {
            return ['ok' => false, 'content' => null, 'usage' => $usage, 'error' => 'not_configured'];
        }

        $baseUrl = rtrim($creds['base_url'] ?? config('ai.base_url'), '/');
        $model   = $creds['model'] ?? config('ai.model');

        try {
            $response = Http::withToken($creds['key'])
                ->timeout((int) config('ai.timeout', 45))
                ->acceptJson()
                ->post($baseUrl . '/chat/completions', [
                    'model'       => $model,
                    'messages'    => $messages,
                    'max_tokens'  => (int) ($opts['max_tokens'] ?? config('ai.max_tokens', 800)),
                    'temperature' => (float) ($opts['temperature'] ?? config('ai.temperature', 0.7)),
                ]);

            if (!$response->successful()) {
                Log::warning('AI request failed', ['status' => $response->status(), 'body' => $response->body()]);
                return ['ok' => false, 'content' => null, 'usage' => $usage, 'error' => 'provider_error'];
            }

            $data = $response->json();
            $message = $data['choices'][0]['message'] ?? [];
            $content = $message['content'] ?? null;

            if ($content === null && !empty($message['refusal'])) {
                $content = $message['refusal'];
            }

            if (!empty($data['usage'])) {
                $usage = [
                    'prompt_tokens'     => (int) ($data['usage']['prompt_tokens'] ?? 0),
                    'completion_tokens' => (int) ($data['usage']['completion_tokens'] ?? 0),
                    'total_tokens'      => (int) ($data['usage']['total_tokens'] ?? 0),
                ];
            }

            if ($content === null) {
                return ['ok' => false, 'content' => null, 'usage' => $usage, 'error' => 'empty_response'];
            }

            return ['ok' => true, 'content' => trim($content), 'usage' => $usage, 'error' => null];
        } catch (\Throwable $e) {
            Log::warning('AI request exception', ['message' => $e->getMessage()]);
            return ['ok' => false, 'content' => null, 'usage' => $usage, 'error' => 'exception'];
        }
    }
}
