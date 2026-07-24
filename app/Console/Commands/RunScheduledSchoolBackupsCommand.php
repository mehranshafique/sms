<?php

namespace App\Console\Commands;

use App\Jobs\CreateSchoolBackupJob;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Models\SchoolBackup;
use App\Models\SchoolBackupDriveAccount;
use Illuminate\Console\Command;

class RunScheduledSchoolBackupsCommand extends Command
{
    protected $signature = 'school-backups:run-scheduled';

    protected $description = 'Create scheduled school backups (daily/weekly) and upload to Google Drive when connected';

    public function handle(): int
    {
        $settings = InstitutionSetting::query()
            ->where('key', 'backup_schedule')
            ->whereIn('value', ['daily', 'weekly'])
            ->get();

        $dispatched = 0;

        foreach ($settings as $setting) {
            $institutionId = (int) $setting->institution_id;
            $schedule = $setting->value;
            $institution = Institution::find($institutionId);
            if (!$institution) {
                continue;
            }

            $last = SchoolBackup::query()
                ->where('institution_id', $institutionId)
                ->where('type', 'scheduled')
                ->where('status', 'completed')
                ->latest('id')
                ->first();

            if ($schedule === 'daily' && $last && $last->created_at->isToday()) {
                continue;
            }
            if ($schedule === 'weekly' && $last && $last->created_at->isCurrentWeek()) {
                continue;
            }

            $running = SchoolBackup::query()
                ->where('institution_id', $institutionId)
                ->whereIn('status', ['pending', 'running'])
                ->exists();
            if ($running) {
                continue;
            }

            $includeFiles = InstitutionSetting::get($institutionId, 'backup_include_files', '1') !== '0';
            $upload = SchoolBackupDriveAccount::where('institution_id', $institutionId)->exists();

            $backup = SchoolBackup::create([
                'institution_id' => $institutionId,
                'type' => 'scheduled',
                'status' => 'pending',
                'include_files' => $includeFiles,
            ]);

            CreateSchoolBackupJob::dispatch($backup->id, $upload);
            $dispatched++;
            $this->line("Dispatched scheduled backup for {$institution->code} (#{$institutionId})");
        }

        $this->info("Scheduled backups dispatched: {$dispatched}");

        return self::SUCCESS;
    }
}
