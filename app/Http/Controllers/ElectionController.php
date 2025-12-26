<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Models\AcademicSession;
use App\Models\Student;
use App\Models\Candidate;
use App\Models\ClassSection;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ElectionController extends BaseController
{
    public function __construct()
    {
        $this->authorizeResource(Election::class, 'election');
        $this->setPageTitle(__('voting.election_management'));
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        if ($request->ajax()) {
            $query = Election::with('institution')
                ->select('elections.*')
                ->withCount('candidates');

            if ($institutionId) {
                $query->where('institution_id', $institutionId);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('status', function($row){
                    $badges = [
                        'draft' => 'badge-secondary',
                        'scheduled' => 'badge-info',
                        'ongoing' => 'badge-success blink', 
                        'completed' => 'badge-dark',
                        'published' => 'badge-primary'
                    ];
                    $class = $badges[$row->status] ?? 'badge-light';
                    return '<span class="badge '.$class.'">'.ucfirst($row->status).'</span>';
                })
                ->editColumn('start_date', function($row){
                    return $row->start_date ? $row->start_date->format('Y-m-d H:i') : '';
                })
                ->editColumn('end_date', function($row){
                    return $row->end_date ? $row->end_date->format('Y-m-d H:i') : '';
                })
                ->orderColumn('candidates_count', function ($query, $order) {
                    $query->orderByRaw("(SELECT count(*) FROM candidates WHERE candidates.election_id = elections.id) $order");
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
                    
                    if(auth()->user()->can('view', $row)){
                        $btn .= '<a href="'.route('elections.show', $row->id).'" class="btn btn-info shadow btn-xs sharp me-1" title="'.__('voting.manage').'"><i class="fa fa-cogs"></i></a>';
                    }
                    if(auth()->user()->can('delete', $row)){
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'"><i class="fa fa-trash"></i></button>';
                    }
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        $totalElections = Election::where('institution_id', $institutionId)->count();
        $activeElections = Election::where('institution_id', $institutionId)->where('status', 'ongoing')->count();

        return view('elections.index', compact('totalElections', 'activeElections'));
    }

    public function create()
    {
        $institutionId = $this->getInstitutionId();
        $sessions = AcademicSession::where('institution_id', $institutionId)->get();
        return view('elections.create', compact('sessions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'academic_session_id' => 'required|exists:academic_sessions,id',
            'description' => 'nullable|string'
        ]);

        Election::create([
            ...$validated,
            'institution_id' => $this->getInstitutionId() ?? Auth::user()->institute_id,
            'status' => 'draft',
        ]);

        return response()->json(['message' => __('voting.election_created'), 'redirect' => route('elections.index')]);
    }

    public function show(Election $election)
    {
        $this->authorize('view', $election);
        if ($election->institution_id !== $this->getInstitutionId()) {
             abort(403);
        }

        $election->load(['positions.candidates.student', 'positions.candidates.student.classSection']);
        
        return view('elections.show', compact('election'));
    }

    public function addPosition(Request $request, Election $election)
    {
        $this->authorize('update', $election);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sequence' => 'integer|min:0'
        ]);

        $election->positions()->create($validated);

        return response()->json(['message' => __('voting.position_added'), 'redirect' => route('elections.show', $election->id)]);
    }

    public function addCandidate(Request $request, Election $election)
    {
        $this->authorize('update', $election);

        $validated = $request->validate([
            'admission_number' => 'required|string', 
            'election_position_id' => 'required|exists:election_positions,id',
        ]);

        $student = Student::where('admission_number', $validated['admission_number'])
            ->where('institution_id', $election->institution_id)
            ->first();

        if (!$student) {
            return response()->json(['message' => __('voting.student_not_found')], 422);
        }

        $exists = Candidate::where('election_position_id', $validated['election_position_id'])
            ->where('student_id', $student->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => __('voting.candidate_exists')], 422);
        }

        Candidate::create([
            'election_id' => $election->id,
            'election_position_id' => $validated['election_position_id'],
            'student_id' => $student->id,
            'status' => 'approved'
        ]);

        return response()->json(['message' => __('voting.candidate_added'), 'redirect' => route('elections.show', $election->id)]);
    }

    public function destroy(Election $election)
    {
        $this->authorize('delete', $election);
        $election->delete();
        return response()->json(['message' => __('voting.success_delete')]);
    }

    // --- NEW METHOD FOR DELETING CANDIDATES ---
    public function destroyCandidate(Candidate $candidate)
    {
        // Ensure user has rights to the parent election
        $this->authorize('update', $candidate->election);
        
        $candidate->delete();
        
        return response()->json(['message' => __('voting.success_delete')]);
    }

    /**
     * Publish the election to make it visible/active
     */
    public function publish(Election $election)
    {
        $this->authorize('update', $election);
        
        $election->update(['status' => 'published']);
        
        return response()->json(['message' => __('voting.election_published')]);
    }

    /**
     * Close the election to stop voting
     */
    public function close(Election $election)
    {
        $this->authorize('update', $election);
        
        $election->update(['status' => 'completed']);
        
        return response()->json(['message' => __('voting.election_closed')]);
    }
}