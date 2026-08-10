<?php

namespace App\Console\Commands;

use App\Services\StudentAbsenceNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class NotifyAbsentStudents extends Command
{
    protected $signature = 'attendance:notify-absent {--date= : Date Y-m-d (default: today)}';

    protected $description = 'Send SMS/WhatsApp notifications to parents of students marked absent (deduped)';

    public function handle(StudentAbsenceNotificationService $service): int
    {
        $today = $this->option('date')
            ? Carbon::parse($this->option('date'))->toDateString()
            : Carbon::today()->toDateString();

        $this->info("Starting absence notifications for {$today}...");

        $stats = $service->notifyPendingForDate($today);

        $this->info("Sent: {$stats['sent']} | Skipped: {$stats['skipped']} | Failed: {$stats['failed']}");

        return self::SUCCESS;
    }
}
