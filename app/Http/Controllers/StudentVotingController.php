<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Models\ElectionParticipation;
use App\Models\Student;
use App\Services\Voting\BallotCastingService;
use App\Services\Voting\ElectionEligibilityService;
use App\Services\Voting\ElectionResultsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentVotingController extends BaseController
{
    public function __construct(
        protected BallotCastingService $casting,
        protected ElectionEligibilityService $eligibility,
        protected ElectionResultsService $results
    ) {
        $this->middleware('auth');
        $this->setPageTitle(__('voting.my_elections'));
    }

    public function index()
    {
        $student = Student::where('user_id', Auth::id())->first();
        if (! $student) {
            return view('students.elections.error', ['message' => __('voting.student_profile_not_found')]);
        }

        $open = Election::query()
            ->where('institution_id', $student->institution_id)
            ->where('status', Election::STATUS_OPEN)
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->whereHas('voters', fn ($q) => $q->where('student_id', $student->id))
            ->with('cycle')
            ->latest('id')
            ->get();

        // Lazy sync all_students elections so newly published rolls include this student
        $maybe = Election::query()
            ->where('institution_id', $student->institution_id)
            ->where('status', Election::STATUS_OPEN)
            ->where('eligibility_type', '!=', Election::ELIGIBILITY_MANUAL)
            ->whereDoesntHave('voters', fn ($q) => $q->where('student_id', $student->id))
            ->get();
        foreach ($maybe as $election) {
            $this->eligibility->syncFromRules($election);
        }

        if ($maybe->isNotEmpty()) {
            $open = Election::query()
                ->where('institution_id', $student->institution_id)
                ->where('status', Election::STATUS_OPEN)
                ->where(function ($q) {
                    $q->whereNull('start_date')->orWhere('start_date', '<=', now());
                })
                ->where(function ($q) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', now());
                })
                ->whereHas('voters', fn ($q) => $q->where('student_id', $student->id))
                ->with('cycle')
                ->latest('id')
                ->get();
        }

        $voterIds = \App\Models\ElectionVoter::where('student_id', $student->id)->pluck('id');
        $participatedIds = ElectionParticipation::whereIn('election_voter_id', $voterIds)
            ->whereIn('election_id', $open->pluck('id'))
            ->pluck('election_id')
            ->all();

        $publishedResults = Election::query()
            ->where('institution_id', $student->institution_id)
            ->where('status', Election::STATUS_RESULTS_PUBLISHED)
            ->where('results_visible_to_voters', true)
            ->whereHas('voters', fn ($q) => $q->where('student_id', $student->id))
            ->latest('id')
            ->limit(10)
            ->get();

        return view('students.elections.index', compact('open', 'student', 'participatedIds', 'publishedResults'));
    }

    public function show(Election $election)
    {
        $student = Student::where('user_id', Auth::id())->firstOrFail();
        if ((int) $election->institution_id !== (int) $student->institution_id) {
            abort(403, __('voting.unauthorized_election'));
        }

        $voter = $this->eligibility->findVoterForStudent($election, $student->id);
        if (! $voter && $election->eligibility_type !== Election::ELIGIBILITY_MANUAL) {
            $this->eligibility->syncFromRules($election);
            $voter = $this->eligibility->findVoterForStudent($election, $student->id);
        }
        if (! $voter) {
            abort(403, __('voting.not_eligible'));
        }

        $hasVoted = $this->casting->hasParticipated($election, $voter);
        $election->load(['positions.candidates.student']);

        return view('students.elections.show', compact('election', 'student', 'hasVoted'));
    }

    public function vote(Request $request, Election $election)
    {
        $student = Student::where('user_id', Auth::id())->firstOrFail();

        $data = $request->validate([
            'choices' => 'required|array|min:1',
            'choices.*' => 'required|integer|exists:candidates,id',
        ]);

        // choices keyed by position_id
        $choices = [];
        foreach ($data['choices'] as $positionId => $candidateId) {
            $choices[(int) $positionId] = (int) $candidateId;
        }

        try {
            $this->casting->castForStudent($election, $student->id, $choices, $request->ip());
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => __('voting.vote_success'), 'status' => 'success']);
    }

    public function results(Election $election)
    {
        $student = Student::where('user_id', Auth::id())->firstOrFail();
        if (! $this->results->canViewResults($election, false)) {
            abort(403, __('voting.results_not_available'));
        }
        if (! $this->eligibility->findVoterForStudent($election, $student->id)) {
            abort(403, __('voting.not_eligible'));
        }

        $blocks = $this->results->resultsByPosition($election);

        return view('students.elections.results', compact('election', 'blocks'));
    }
}
