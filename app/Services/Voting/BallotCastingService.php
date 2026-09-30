<?php

namespace App\Services\Voting;

use App\Models\Ballot;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\ElectionParticipation;
use App\Models\ElectionVoter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class BallotCastingService
{
    public function __construct(
        protected ElectionEligibilityService $eligibility
    ) {}

    /**
     * Cast one or more position choices for a voter. Records participation once
     * and anonymous ballots with no voter link.
     *
     * @param  array<int, int>  $choices  position_id => candidate_id
     */
    public function cast(Election $election, ElectionVoter $voter, array $choices, ?string $deviceId = null): void
    {
        if (! $election->isOpenForVoting()) {
            throw new RuntimeException(__('voting.election_not_open'));
        }

        if ((int) $voter->election_id !== (int) $election->id) {
            throw new RuntimeException(__('voting.unauthorized_election'));
        }

        DB::transaction(function () use ($election, $voter, $choices, $deviceId) {
            $lockedVoter = ElectionVoter::where('id', $voter->id)->lockForUpdate()->firstOrFail();

            $already = ElectionParticipation::where('election_id', $election->id)
                ->where('election_voter_id', $lockedVoter->id)
                ->lockForUpdate()
                ->exists();

            if ($already) {
                throw new RuntimeException(__('voting.already_voted_election'));
            }

            if ($choices === []) {
                throw new RuntimeException(__('voting.no_choices'));
            }

            foreach ($choices as $positionId => $candidateId) {
                $positionId = (int) $positionId;
                $candidateId = (int) $candidateId;

                $belongs = $election->positions()->where('id', $positionId)->exists();
                if (! $belongs) {
                    throw new RuntimeException(__('voting.invalid_position'));
                }

                $candidate = Candidate::where('id', $candidateId)
                    ->where('election_id', $election->id)
                    ->where('election_position_id', $positionId)
                    ->where('status', 'approved')
                    ->first();

                if (! $candidate) {
                    throw new RuntimeException(__('voting.invalid_candidate'));
                }

                Ballot::create([
                    'ballot_uuid' => (string) Str::uuid(),
                    'election_id' => $election->id,
                    'election_position_id' => $positionId,
                    'candidate_id' => $candidateId,
                    'cast_at' => now(),
                ]);
            }

            ElectionParticipation::create([
                'election_id' => $election->id,
                'election_voter_id' => $lockedVoter->id,
                'participated_at' => now(),
                'device_id' => $deviceId,
            ]);
        });
    }

    public function castForStudent(Election $election, int $studentId, array $choices, ?string $deviceId = null): void
    {
        $voter = $this->eligibility->findVoterForStudent($election, $studentId);
        if (! $voter) {
            // Lazy-register if eligibility is all_students / synced filters include them
            if ($election->eligibility_type !== Election::ELIGIBILITY_MANUAL) {
                $this->eligibility->syncFromRules($election);
                $voter = $this->eligibility->findVoterForStudent($election, $studentId);
            }
        }

        if (! $voter) {
            throw new RuntimeException(__('voting.not_eligible'));
        }

        $this->cast($election, $voter, $choices, $deviceId);
    }

    public function hasParticipated(Election $election, ElectionVoter $voter): bool
    {
        return ElectionParticipation::where('election_id', $election->id)
            ->where('election_voter_id', $voter->id)
            ->exists();
    }
}
