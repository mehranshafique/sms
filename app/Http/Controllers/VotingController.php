<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Models\Vote;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Middleware\PermissionMiddleware;

class VotingController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(PermissionMiddleware::class . ':election.view|voting.create')->only(['identifyVoter', 'castVote']);
    }

    /**
     * Step 1: Scan QR/NFC to identify Voter
     * Returns available elections for this student
     */
    public function identifyVoter(Request $request)
    {
        $request->validate([
            'identity_token' => 'required',
            'type' => 'required|in:qr,nfc'
        ]);

        $column = $request->type === 'nfc' ? 'nfc_tag_uid' : 'qr_code_token';
        $student = Student::where($column, $request->identity_token)->firstOrFail();

        $institutionId = $this->getInstitutionId();
        if ($institutionId && (int) $student->institution_id !== (int) $institutionId) {
            abort(403);
        }

        $activeElections = Election::where('institution_id', $student->institution_id)
            ->where('status', 'ongoing')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->with(['positions.candidates.student'])
            ->get();

        if ($activeElections->isEmpty()) {
            return response()->json(['message' => __('voting.no_active_elections')], 404);
        }

        return response()->json([
            'student' => $student->only(['id', 'first_name', 'last_name', 'class_section_id']),
            'elections' => $activeElections
        ]);
    }

    /**
     * Step 2: Submit a Vote
     */
    public function castVote(Request $request)
    {
        $validated = $request->validate([
            'election_id' => 'required|exists:elections,id',
            'election_position_id' => 'required|exists:election_positions,id',
            'candidate_id' => 'required|exists:candidates,id',
            'voter_id' => 'required|exists:students,id',
            'device_id' => 'nullable|string'
        ]);

        $student = Student::findOrFail($validated['voter_id']);
        $institutionId = $this->getInstitutionId();
        if ($institutionId && (int) $student->institution_id !== (int) $institutionId) {
            abort(403);
        }

        try {
            DB::beginTransaction();

            $exists = Vote::where('voter_id', $validated['voter_id'])
                          ->where('election_position_id', $validated['election_position_id'])
                          ->exists();

            if ($exists) {
                return response()->json(['error' => __('voting.already_voted_for_position')], 409);
            }

            Vote::create([
                'election_id' => $validated['election_id'],
                'election_position_id' => $validated['election_position_id'],
                'candidate_id' => $validated['candidate_id'],
                'voter_id' => $validated['voter_id'],
                'device_id' => $request->input('device_id'),
                'voted_at' => now(),
            ]);

            DB::commit();

            return response()->json(['message' => __('voting.vote_cast_success')]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => __('voting.system_error')], 500);
        }
    }
}
