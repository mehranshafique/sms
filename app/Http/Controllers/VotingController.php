<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Models\Student;
use App\Services\Voting\BallotCastingService;
use App\Services\Voting\ElectionEligibilityService;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;

class VotingController extends BaseController
{
    public function __construct(
        protected BallotCastingService $casting,
        protected ElectionEligibilityService $eligibility
    ) {
        $this->middleware('auth');
        $this->middleware(PermissionMiddleware::class . ':election.view|voting.create')->only(['identifyVoter', 'castVote']);
    }

    public function identifyVoter(Request $request)
    {
        $request->validate([
            'identity_token' => 'required',
            'type' => 'required|in:qr,nfc',
        ]);

        $column = $request->type === 'nfc' ? 'nfc_tag_uid' : 'qr_code_token';
        $student = Student::where($column, $request->identity_token)->firstOrFail();

        $institutionId = $this->getInstitutionId();
        if ($institutionId && (int) $student->institution_id !== (int) $institutionId) {
            abort(403);
        }

        $activeElections = Election::where('institution_id', $student->institution_id)
            ->where('status', Election::STATUS_OPEN)
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->whereHas('voters', fn ($q) => $q->where('student_id', $student->id))
            ->with(['positions.candidates.student'])
            ->get();

        if ($activeElections->isEmpty()) {
            // Attempt sync for open filter-based elections
            Election::where('institution_id', $student->institution_id)
                ->where('status', Election::STATUS_OPEN)
                ->where('eligibility_type', '!=', Election::ELIGIBILITY_MANUAL)
                ->get()
                ->each(fn ($e) => $this->eligibility->syncFromRules($e));

            $activeElections = Election::where('institution_id', $student->institution_id)
                ->where('status', Election::STATUS_OPEN)
                ->whereHas('voters', fn ($q) => $q->where('student_id', $student->id))
                ->with(['positions.candidates.student'])
                ->get()
                ->filter(fn (Election $e) => $e->isOpenForVoting())
                ->values();
        }

        if ($activeElections->isEmpty()) {
            return response()->json(['message' => __('voting.no_active_elections')], 404);
        }

        return response()->json([
            'student' => $student->only(['id', 'first_name', 'last_name', 'class_section_id']),
            'elections' => $activeElections,
        ]);
    }

    public function castVote(Request $request)
    {
        $validated = $request->validate([
            'election_id' => 'required|exists:elections,id',
            'voter_id' => 'required|exists:students,id',
            'choices' => 'required|array|min:1',
            'choices.*.election_position_id' => 'required|exists:election_positions,id',
            'choices.*.candidate_id' => 'required|exists:candidates,id',
            'device_id' => 'nullable|string',
            // Legacy single-choice payload still accepted
            'election_position_id' => 'nullable|exists:election_positions,id',
            'candidate_id' => 'nullable|exists:candidates,id',
        ]);

        $student = Student::findOrFail($validated['voter_id']);
        $institutionId = $this->getInstitutionId();
        if ($institutionId && (int) $student->institution_id !== (int) $institutionId) {
            abort(403);
        }

        $election = Election::findOrFail($validated['election_id']);

        $choices = [];
        if (! empty($validated['choices'])) {
            foreach ($validated['choices'] as $row) {
                $choices[(int) $row['election_position_id']] = (int) $row['candidate_id'];
            }
        } elseif (! empty($validated['election_position_id']) && ! empty($validated['candidate_id'])) {
            $choices[(int) $validated['election_position_id']] = (int) $validated['candidate_id'];
        }

        try {
            $this->casting->castForStudent($election, $student->id, $choices, $request->input('device_id'));
        } catch (\Throwable $e) {
            $code = str_contains($e->getMessage(), __('voting.already_voted_election')) ? 409 : 422;

            return response()->json(['error' => $e->getMessage()], $code);
        }

        return response()->json(['message' => __('voting.vote_cast_success')]);
    }
}
