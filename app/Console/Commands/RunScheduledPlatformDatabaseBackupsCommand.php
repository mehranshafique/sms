<?php

namespace App\Console\Commands;

use App\Jobs\CreatePlatformDatabaseBackupJob;
use App\Models\PlatformDatabaseBackup;
use App\Services\Backup\PlatformDatabaseBackupService;
use Illuminate\Console\Command;

class RunScheduledPlatformDatabaseBackupsCommand extends Command
{
    protected $signature = 'platform-backups:run-scheduled {--force : Run even if schedule says not due}';

    protected $description = 'Create a platform MySQL dump when Super Admin auto-backup schedule is due';

    public function handle(PlatformDatabaseBackupService $service): int
    {
        if (! $this->option('force') && ! $service->shouldRunScheduledNow()) {
            $this->info('No scheduled platform DB backup due.');

            return self::SUCCESS;
        }

        if (PlatformDatabaseBackup::whereIn('status', ['pending', 'running'])->exists()) {
            $this->warn('A platform DB backup is already running.');

            return self::SUCCESS;
        }

        $backup = PlatformDatabaseBackup::create([
            'type' => 'scheduled',
            'status' => 'pending',
            'triggered_by' => null,
        ]);

        CreatePlatformDatabaseBackupJob::dispatch($backup->id);
        $this->info('Scheduled platform DB backup queued #'.$backup->id);

        return self::SUCCESS;
    }
}
