<?php

namespace App\Services;

use App\Models\MessageLog;
use Illuminate\Support\Facades\Log;

class MessageLogService
{
    /**
     * Append-only compact send log (no message body — keeps storage small).
     */
    public function log(array $data): ?MessageLog
    {
        try {
            return MessageLog::create([
                'institution_id' => $data['institution_id'] ?? null,
                'channel' => substr((string) ($data['channel'] ?? 'sms'), 0, 16),
                'event_key' => isset($data['event_key']) ? substr((string) $data['event_key'], 0, 64) : null,
                'to_masked' => self::maskPhone($data['to'] ?? ''),
                'status' => substr((string) ($data['status'] ?? 'failed'), 0, 16),
                'provider' => isset($data['provider']) ? substr((string) $data['provider'], 0, 32) : null,
                'provider_msg_id' => isset($data['provider_msg_id']) ? substr((string) $data['provider_msg_id'], 0, 64) : null,
                'error' => isset($data['error']) ? mb_substr((string) $data['error'], 0, 191) : null,
                'credited' => (bool) ($data['credited'] ?? false),
                'related_type' => $data['related_type'] ?? null,
                'related_id' => $data['related_id'] ?? null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('MessageLog write failed: ' . $e->getMessage());
            return null;
        }
    }

    public static function maskPhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';
        if ($digits === '') {
            return '****';
        }
        if (strlen($digits) <= 4) {
            return str_repeat('*', strlen($digits));
        }

        return str_repeat('*', max(0, strlen($digits) - 4)) . substr($digits, -4);
    }
}
