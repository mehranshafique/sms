<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $events = [
        'teacher_welcome',
        'staff_welcome',
        'user_welcome',
        'head_officer_welcome',
        'institution_created',
        'student_welcome',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('sms_templates')) {
            return;
        }

        $rows = DB::table('sms_templates')
            ->whereIn('event_key', $this->events)
            ->get(['id', 'event_key', 'body', 'available_tags']);

        foreach ($rows as $row) {
            $body = (string) $row->body;
            $tags = (string) ($row->available_tags ?? '');

            if (! str_contains($tags, '$LoginId')) {
                $tags = trim($tags . (filled($tags) ? ', ' : '') . '$LoginId, $Username, $Shortcode', ' ,');
            }

            if (! str_contains($body, '$LoginId') && ! str_contains($body, '$Username')) {
                $body = $this->injectLoginId($body);
            }

            DB::table('sms_templates')->where('id', $row->id)->update([
                'body' => $body,
                'available_tags' => $tags,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Non-destructive; leave updated templates in place.
    }

    private function injectLoginId(string $body): string
    {
        $replacements = [
            // French custom templates often use email as Digitex ID
            'ID Digitex : $Email' => 'ID Digitex : $LoginId | Email: $Email',
            'ID Digitex: $Email' => 'ID Digitex: $LoginId | Email: $Email',
            'votre ID Digitex : $Email' => 'votre ID Digitex : $LoginId | Email: $Email',
            'votre ID Digitex: $Email' => 'votre ID Digitex: $LoginId | Email: $Email',
            // English defaults from older seeders
            'Login ID: $Email' => 'Login ID: $LoginId | Email: $Email',
            'Login: $Email' => 'Login ID: $LoginId | Email: $Email',
            'ID: $Email' => 'Login ID: $LoginId | Email: $Email',
            'login credentials are ID: $Email' => 'login credentials are Login ID: $LoginId, Email: $Email',
        ];

        foreach ($replacements as $from => $to) {
            if (stripos($body, $from) !== false) {
                return str_ireplace($from, $to, $body);
            }
        }

        // Fallback: append credentials if message has password but no login id yet
        if (str_contains($body, '$Password') && ! str_contains($body, '$LoginId')) {
            return rtrim($body) . ' Login ID: $LoginId | Email: $Email';
        }

        return $body;
    }
};
