<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Models\Vote;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // Added for logging

class StudentVotingController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth'); 
    }

    /**
     * List available elections for the logged-in student
     */
    public function index()
    {
        $user = Auth::user();
        
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            return view('students.elections.error', ['message' => __('voting.student_profile_not_found')]);
        }

        // 2. Find Active Elections for Student's Institution
        $elections = Election::where('institution_id', $student->institution_id)
            ->where('status', 'published') // Only published elections
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->withCount(['votes' => function($q) use ($student) {
                $q->where('voter_id', $student->id);
            }])
            ->latest()
            ->get();

        return view('students.elections.index', compact('elections', 'student'));
    }

    /**
     * Show the Ballot Paper
     */
    public function show(Election $election)
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->firstOrFail();

        // 1. Eligibility Check
        if ($election->institution_id !== $student->institution_id) {
            abort(403, __('voting.unauthorized_election'));
        }

        // 2. Load Ballot Data
        $election->load(['positions.candidates.student']);

        // 3. Check existing votes
        $myVotes = Vote::where('election_id', $election->id)
            ->where('voter_id', $student->id)
            ->pluck('candidate_id', 'election_position_id')
            ->toArray();

        return view('students.elections.show', compact('election', 'student', 'myVotes'));
    }

    /**
     * Submit a Vote
     */
    public function vote(Request $request, Election $election)
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->firstOrFail();

        $request->validate([
            'position_id' => 'required|exists:election_positions,id',
            'candidate_id' => 'required|exists:candidates,id',
        ]);

        // Transaction for safety
        try {
            DB::beginTransaction();

            // 1. Check Double Voting (Race Condition Safe Lock)
            // We use lockForUpdate to prevent simultaneous submissions
            $exists = Vote::where('voter_id', $student->id)
                ->where('election_position_id', $request->position_id)
                ->lockForUpdate() 
                ->exists();

            if ($exists) {
                DB::rollBack();
                return response()->json(['message' => __('voting.already_voted_position')], 422);
            }

            // 2. Cast Vote
            // FIX: Use DB::table directly to avoid Eloquent assuming 'updated_at' exists
            // The error "Unknown column 'updated_at'" indicates the table doesn't have timestamps.
            DB::table('votes')->insert([
                'election_id' => $election->id,
                'election_position_id' => $request->position_id,
                'candidate_id' => $request->candidate_id,
                'voter_id' => $student->id,
                'voted_at' => now(),
                'device_id' => $request->ip() // Simple tracking
            ]);

            DB::commit();

            return response()->json(['message' => __('voting.vote_success'), 'status' => 'success']);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log the actual error for debugging
            Log::error("Voting Error: " . $e->getMessage());

            // Return specific error message if available, else generic
            // Check for specific SQL duplicate entry error (Code 23000)
            if ($e instanceof \Illuminate\Database\QueryException && $e->getCode() == 23000) {
                 return response()->json(['message' => __('voting.already_voted_position')], 422);
            }

            return response()->json([
                'message' => __('voting.system_error') . ' (Debug: ' . $e->getMessage() . ')', 
                'error_detail' => $e->getMessage() // For easier debugging in browser console
            ], 500);
        }
    }
}