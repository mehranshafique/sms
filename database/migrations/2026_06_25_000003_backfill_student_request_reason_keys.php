<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('student_requests')
            ->whereNull('reason_key')
            ->where('type', 'fee_extension')
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $days = null;
                    if (preg_match('/(\d+)\s+jours/u', (string) $row->reason, $m)) {
                        $days = (int) $m[1];
                    } elseif (preg_match('/(\d+)\s+days/i', (string) $row->reason, $m)) {
                        $days = (int) $m[1];
                    }

                    if ($days === null) {
                        continue;
                    }

                    $locale = str_contains((string) $row->reason, 'jours') ? 'fr' : 'en';

                    DB::table('student_requests')->where('id', $row->id)->update([
                        'reason_key' => 'requests.reason_chatbot_fee_extension',
                        'reason_params' => json_encode(['days' => $days]),
                        'reason_locale' => $locale,
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Non-destructive data backfill — no rollback.
    }
};
