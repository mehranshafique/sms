<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Models\Vote;
use App\Models\Student;
use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VotingController extends Controller
{
    /**
     * Step 1: Scan QR/NFC to identify Voter
     * Returns available elections for this student
     */
    public function identifyVoter(Request $request)
    {
        $request->validate([
            'identity_token' => 'required', // The QR code content or NFC UID
            'type' => 'required|in:qr,nfc'
        ]);

        // 1. Find Student
        $column = $request->type === 'nfc' ? 'nfc_tag_uid' : 'qr_code_token';
        $student = Student::where($column, $request->identity_token)->firstOrFail();

        // 2. Eligibility Checks (PDF: School, Class/Level, Academic Year, Fee Paid)
        
        // Fee Check (Placeholder logic - implement actual fee service check)
        // if (!$student->hasPaidFees()) {
        //    return response()->json(['error' => __('voting.fees_not_paid')], 403);
        // }

        // 3. Find Active Elections for Student's Institution & Session
        $activeElections = Election::where('institution_id', $student->institution_id)
            ->where('status', 'ongoing')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->with(['positions.candidates.student']) // Eager load ballot
            ->get();

        if ($activeElections->isEmpty()) {
            return response()->json(['message' => __('voting.no_active_elections')], 404);
        }

        // 4. Filter Candidates based on Student's Class (if positions are class-specific)
        // Note: The PDF implies the system filters candidates. 
        // For simple logic, we return the whole ballot, frontend filters or we filter here.
        
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

        // Transaction ensures atomicity
        try {
            DB::beginTransaction();

            // 1. Check if already voted
            $exists = Vote::where('voter_id', $validated['voter_id'])
                          ->where('election_position_id', $validated['election_position_id'])
                          ->exists();

            if ($exists) {
                return response()->json(['error' => __('voting.already_voted_for_position')], 409);
            }

            // 2. Record Vote
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