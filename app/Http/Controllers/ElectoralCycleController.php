<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\ElectoralCycle;
use App\Services\Voting\ElectoralCycleService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ElectoralCycleController extends BaseController
{
    public function __construct(protected ElectoralCycleService $cycles)
    {
        $this->middleware('auth');
        $this->setPageTitle(__('voting.cycles_title'));
    }

    public function index(Request $request)
    {
        $this->authorizeAdminOrPermission('election.view');
        $institutionId = $this->requireInstitutionId();

        if ($request->ajax()) {
            $query = ElectoralCycle::query()
                ->where('institution_id', $institutionId)
                ->withCount('elections')
                ->latest('id');

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('title', function ($row) {
                    return '<a href="'.route('electoral-cycles.show', $row).'">'.e($row->title).'</a>';
                })
                ->editColumn('status', fn ($row) => '<span class="badge badge-info">'.$row->statusLabel().'</span>')
                ->addColumn('action', function ($row) {
                    return '<a class="btn btn-info btn-xs sharp" href="'.route('electoral-cycles.show', $row).'"><i class="fa fa-cogs"></i></a>';
                })
                ->rawColumns(['title', 'status', 'action'])
                ->make(true);
        }

        return view('elections.cycles.index');
    }

    public function create()
    {
        $this->authorizeAdminOrPermission('election.create');
        $institutionId = $this->requireInstitutionId();
        $sessions = AcademicSession::where('institution_id', $institutionId)->orderByDesc('id')->get();

        return view('elections.cycles.create', compact('sessions'));
    }

    public function store(Request $request)
    {
        $this->authorizeAdminOrPermission('election.create');
        $institutionId = $this->requireInstitutionId();

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'academic_session_id' => 'nullable|exists:academic_sessions,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $cycle = $this->cycles->create($data, (int) $institutionId);

        return response()->json([
            'message' => __('voting.cycle_created'),
            'redirect' => route('electoral-cycles.show', $cycle),
        ]);
    }

    public function show(ElectoralCycle $electoralCycle)
    {
        $this->authorizeAdminOrPermission('election.view');
        $institutionId = $this->requireInstitutionId();
        abort_unless((int) $electoralCycle->institution_id === (int) $institutionId, 403);

        $electoralCycle->load(['elections' => fn ($q) => $q->withCount(['candidates', 'voters', 'participations'])]);
        $sessions = AcademicSession::where('institution_id', $institutionId)->orderByDesc('id')->get();

        return view('elections.cycles.show', [
            'cycle' => $electoralCycle,
            'sessions' => $sessions,
        ]);
    }

    public function storeElection(Request $request, ElectoralCycle $electoralCycle)
    {
        $this->authorizeAdminOrPermission('election.create');
        $institutionId = $this->requireInstitutionId();
        abort_unless((int) $electoralCycle->institution_id === (int) $institutionId, 403);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'academic_session_id' => 'nullable|exists:academic_sessions,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'eligibility_type' => 'required|in:all_students,grade_levels,class_sections,departments,manual_list',
            'eligibility_ids' => 'nullable|array',
            'eligibility_ids.*' => 'integer',
        ]);

        $election = $this->cycles->createElection($electoralCycle, $data);

        return response()->json([
            'message' => __('voting.election_created'),
            'redirect' => route('elections.show', $election),
        ]);
    }

    private function requireInstitutionId(): int
    {
        $id = $this->getInstitutionId();
        if (! $id) {
            abort(403, __('voting.select_institution'));
        }

        return (int) $id;
    }
}
