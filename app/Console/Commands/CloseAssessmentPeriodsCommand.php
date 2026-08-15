<?php

namespace App\Console\Commands;

use App\Services\AssessmentPeriodService;
use Illuminate\Console\Command;
use Throwable;

class CloseAssessmentPeriodsCommand extends Command
{
    protected $signature = 'assessment-periods:auto-close';

    protected $description = 'Close assessment stages whose scheduled close time has passed';

    public function handle(AssessmentPeriodService $periods): int
    {
        $due = $periods->dueAutoClose();
        $closed = 0;

        foreach ($due as $state) {
            try {
                $periods->close(
                    (int) $state->institution_id,
                    (int) $state->academic_session_id,
                    (string) $state->period_key,
                    null,
                    true
                );
                $closed++;
            } catch (Throwable $e) {
                $this->error("Failed to auto-close {$state->period_key}: {$e->getMessage()}");
            }
        }

        $this->info("Auto-closed {$closed} assessment stage(s).");

        return self::SUCCESS;
    }
}
