<?php

namespace App\Jobs;

use App\Models\SchoolBackup;
use App\Services\AuditLogger;
use App\Services\Backup\GoogleDriveBackupService;
use App\Services\Backup\SchoolBackupExporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateSchoolBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600;

    public function __construct(
        public int $backupId,
        public bool $uploadToDrive = false
    ) {}

    public function handle(SchoolBackupExporter $exporter, GoogleDriveBackupService $drive): void
    {
        $backup = SchoolBackup::findOrFail($this->backupId);
        $exporter->export($backup);

        if ($this->uploadToDrive) {
            try {
                $drive->uploadBackup($backup->fresh());
            } catch (\Throwable $e) {
                // Keep local backup even if Drive upload fails
                $backup->fresh()->update([
                    'error_message' => trim(($backup->fresh()->error_message ? $backup->fresh()->error_message . ' | ' : '') . 'Drive: ' . $e->getMessage()),
                ]);
            }
        }

        AuditLogger::log(
            'Export',
            'School Backups',
            "Created school backup #{$backup->id} for institution #{$backup->institution_id}",
            null,
            ['backup_id' => $backup->id, 'uuid' => $backup->uuid, 'status' => $backup->fresh()->status]
        );
    }
}
