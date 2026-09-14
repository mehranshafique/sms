<?php

namespace App\Jobs;

use App\Models\PlatformDatabaseBackup;
use App\Services\Backup\PlatformDatabaseBackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreatePlatformDatabaseBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 1800;

    public function __construct(public int $backupId) {}

    public function handle(PlatformDatabaseBackupService $service): void
    {
        $backup = PlatformDatabaseBackup::find($this->backupId);
        if (! $backup) {
            return;
        }

        $service->create($backup);
    }
}
