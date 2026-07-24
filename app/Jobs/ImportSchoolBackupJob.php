<?php

namespace App\Jobs;

use App\Services\AuditLogger;
use App\Services\Backup\SchoolBackupImporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class ImportSchoolBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 900;

    public function __construct(
        public int $institutionId,
        public string $uploadedZipPath,
        public bool $replace,
        public ?int $userId = null
    ) {}

    public function handle(SchoolBackupImporter $importer): void
    {
        $lockKey = 'school-backup-import:' . $this->institutionId;
        $lock = Cache::lock($lockKey, 900);
        if (!$lock->get()) {
            throw new \RuntimeException('An import is already running for this institution.');
        }

        try {
            $result = $importer->import($this->uploadedZipPath, $this->institutionId, $this->replace);

            AuditLogger::log(
                'Import',
                'School Backups',
                "Imported school backup into institution #{$this->institutionId}",
                null,
                [
                    'institution_id' => $this->institutionId,
                    'imported' => $result['imported'],
                    'warnings' => $result['warnings'],
                    'user_id' => $this->userId,
                ]
            );

            Cache::put('school-backup-import-result:' . $this->institutionId, [
                'ok' => true,
                'imported' => $result['imported'],
                'warnings' => $result['warnings'],
                'at' => now()->toIso8601String(),
            ], now()->addHour());
        } catch (\Throwable $e) {
            Cache::put('school-backup-import-result:' . $this->institutionId, [
                'ok' => false,
                'error' => $e->getMessage(),
                'at' => now()->toIso8601String(),
            ], now()->addHour());
            throw $e;
        } finally {
            $lock->release();
            if (is_file($this->uploadedZipPath)) {
                @unlink($this->uploadedZipPath);
            }
        }
    }
}
