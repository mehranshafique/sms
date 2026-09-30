<?php

namespace App\Services\Voting;

use App\Models\Election;
use App\Services\AuditLogger;
use RuntimeException;

class ElectionStatusService
{
    public function __construct(
        protected ElectionEligibilityService $eligibility
    ) {}

    public function schedule(Election $election): Election
    {
        $this->assertHasBallot($election);
        $election->update(['status' => Election::STATUS_SCHEDULED]);
        AuditLogger::log('Schedule', 'Voting', 'Election scheduled #'.$election->id);

        return $election->fresh();
    }

    public function open(Election $election): Election
    {
        $this->assertHasBallot($election);

        if ($election->eligibility_type !== Election::ELIGIBILITY_MANUAL) {
            $this->eligibility->syncFromRules($election);
        }

        if ($election->voters()->count() === 0) {
            throw new RuntimeException(__('voting.no_voters'));
        }

        $election->update(['status' => Election::STATUS_OPEN]);
        AuditLogger::log('Open', 'Voting', 'Election opened for voting #'.$election->id);

        return $election->fresh();
    }

    public function close(Election $election): Election
    {
        if (! in_array($election->status, [Election::STATUS_OPEN, Election::STATUS_SCHEDULED], true)) {
            throw new RuntimeException(__('voting.cannot_close'));
        }

        $election->update(['status' => Election::STATUS_CLOSED]);
        AuditLogger::log('Close', 'Voting', 'Election closed #'.$election->id);

        return $election->fresh();
    }

    public function publishResults(Election $election): Election
    {
        if ($election->status !== Election::STATUS_CLOSED) {
            throw new RuntimeException(__('voting.must_close_before_publish'));
        }

        $election->update(['status' => Election::STATUS_RESULTS_PUBLISHED]);
        AuditLogger::log('PublishResults', 'Voting', 'Election results published #'.$election->id);

        return $election->fresh();
    }

    protected function assertHasBallot(Election $election): void
    {
        if ($election->positions()->count() === 0) {
            throw new RuntimeException(__('voting.no_positions'));
        }
        if ($election->candidates()->where('status', 'approved')->count() === 0) {
            throw new RuntimeException(__('voting.no_candidates'));
        }
    }
}
