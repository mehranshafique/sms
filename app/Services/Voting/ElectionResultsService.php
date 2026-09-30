<?php

namespace App\Services\Voting;

use App\Models\Ballot;
use App\Models\Election;
use App\Models\ElectionParticipation;
use App\Models\ElectionVoter;
use Illuminate\Support\Collection;

class ElectionResultsService
{
    /**
     * @return array{registered:int,voted:int,not_voted:int,turnout_percent:float}
     */
    public function turnout(Election $election): array
    {
        $registered = ElectionVoter::where('election_id', $election->id)->count();
        $voted = ElectionParticipation::where('election_id', $election->id)->count();
        $notVoted = max(0, $registered - $voted);
        $percent = $registered > 0 ? round(($voted / $registered) * 100, 1) : 0.0;

        return [
            'registered' => $registered,
            'voted' => $voted,
            'not_voted' => $notVoted,
            'turnout_percent' => $percent,
        ];
    }

    /**
     * Who voted / who did not — names only, never linked to choices.
     *
     * @return array{voted:Collection,not_voted:Collection}
     */
    public function participationLists(Election $election): array
    {
        $votedIds = ElectionParticipation::where('election_id', $election->id)
            ->pluck('election_voter_id');

        $voted = ElectionVoter::where('election_id', $election->id)
            ->whereIn('id', $votedIds)
            ->orderBy('display_name')
            ->get();

        $notVoted = ElectionVoter::where('election_id', $election->id)
            ->whereNotIn('id', $votedIds)
            ->orderBy('display_name')
            ->get();

        return compact('voted', 'notVoted');
    }

    /**
     * Results by position/candidate from anonymous ballots only.
     *
     * @return list<array{position_id:int,position_name:string,candidates:list<array{candidate_id:int,name:string,votes:int,percent:float}>}>
     */
    public function resultsByPosition(Election $election): array
    {
        $election->load(['positions.candidates.student']);
        $counts = Ballot::where('election_id', $election->id)
            ->selectRaw('election_position_id, candidate_id, COUNT(*) as votes')
            ->groupBy('election_position_id', 'candidate_id')
            ->get()
            ->groupBy('election_position_id');

        $out = [];
        foreach ($election->positions as $position) {
            $posCounts = $counts->get($position->id, collect());
            $total = (int) $posCounts->sum('votes');
            $candidates = [];
            foreach ($position->candidates as $candidate) {
                $row = $posCounts->firstWhere('candidate_id', $candidate->id);
                $votes = (int) ($row->votes ?? 0);
                $candidates[] = [
                    'candidate_id' => $candidate->id,
                    'name' => $candidate->displayName(),
                    'votes' => $votes,
                    'percent' => $total > 0 ? round(($votes / $total) * 100, 1) : 0.0,
                ];
            }
            usort($candidates, fn ($a, $b) => $b['votes'] <=> $a['votes']);
            $out[] = [
                'position_id' => $position->id,
                'position_name' => $position->name,
                'total_ballots' => $total,
                'candidates' => $candidates,
            ];
        }

        return $out;
    }

    public function canViewResults(Election $election, bool $asAdmin): bool
    {
        if ($asAdmin) {
            return in_array($election->status, [
                Election::STATUS_CLOSED,
                Election::STATUS_RESULTS_PUBLISHED,
            ], true);
        }

        return $election->status === Election::STATUS_RESULTS_PUBLISHED
            && $election->results_visible_to_voters;
    }
}
