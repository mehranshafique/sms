<?php

namespace App\Jobs;

use App\Models\SchoolBackup;
use App\Services\Backup\GoogleDriveBackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UploadSchoolBackupToDriveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 600;

    public function __construct(public int $backupId) {}

    public function handle(GoogleDriveBackupService $drive): void
    {
        $backup = SchoolBackup::findOrFail($this->backupId);
        $drive->uploadBackup($backup);
    }
}
