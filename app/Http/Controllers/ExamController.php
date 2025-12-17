<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\AcademicSession;
use App\Models\Institution;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ExamController extends BaseController
{
    public function __construct()
    {
        $this->authorizeResource(Exam::class, 'exam');
        $this->setPageTitle(__('exam.page_title'));
    }

    private function getAllowedInstitutionIds()
    {
        $user = Auth::user();
        if ($user->institute_id) {
            return [$user->institute_id];
        }
        if ($user->institutes->isNotEmpty()) {
            return $user->institutes->pluck('id')->toArray();
        }
        return Institution::pluck('id')->toArray();
    }

    public function index(Request $request)
    {
        $allowedInstitutionIds = $this->getAllowedInstitutionIds();

        if ($request->ajax()) {
            $data = Exam::with(['academicSession', 'institution'])
                ->whereIn('institution_id', $allowedInstitutionIds)
                ->select('exams.*');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('checkbox', function($row){
                    // Check delete permission (specific or global) to show checkbox
                    if(auth()->user()->can('delete', $row) || auth()->user()->can('deleteAny', Exam::class)){
                        return '<div class="form-check custom-checkbox checkbox-primary check-lg me-3">
                                    <input type="checkbox" class="form-check-input single-checkbox" value="'.$row->id.'">
                                    <label class="form-check-label"></label>
                                </div>';
                    }
                    return '';
                })
                ->addColumn('institution', function($row){
                    return $row->institution->name ?? 'N/A';
                })
                ->addColumn('session', function($row){
                    return $row->academicSession->name ?? 'N/A';
                })
                ->editColumn('start_date', function($row){
                    return $row->start_date->format('d M, Y');
                })
                ->editColumn('status', function($row){
                    $badges = [
                        'scheduled' => 'badge-info',
                        'ongoing' => 'badge-warning',
                        'completed' => 'badge-primary',
                        'published' => 'badge-success',
                    ];
                    $class = $badges[$row->status] ?? 'badge-secondary';
                    return '<span class="badge '.$class.'">'.ucfirst($row->status).'</span>';
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
                    
                    if(auth()->user()->can('view', $row)){
                        $btn .= '<a href="'.route('exams.show', $row->id).'" class="btn btn-info shadow btn-xs sharp me-1"><i class="fa fa-eye"></i></a>';
                    }

                    if(auth()->user()->can('update', $row)){
                        $btn .= '<a href="'.route('exams.edit', $row->id).'" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fa fa-pencil"></i></a>';
                    }
                    if(auth()->user()->can('delete', $row)){
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'"><i class="fa fa-trash"></i></button>';
                    }
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'status', 'action'])
                ->make(true);
        }

        return view('exams.index');
    }

    public function create()
    {
        $allowedInstitutionIds = $this->getAllowedInstitutionIds();
        
        $sessions = AcademicSession::with('institution')
            ->whereIn('institution_id', $allowedInstitutionIds)
            ->whereIn('status', ['active', 'planned'])
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->id => $item->name . ' (' . $item->institution->name . ')'];
            });

        $institutions = Institution::whereIn('id', $allowedInstitutionIds)->pluck('name', 'id');

        return view('exams.create', compact('sessions', 'institutions'));
    }

    public function store(Request $request)
    {
        $allowedInstitutionIds = $this->getAllowedInstitutionIds();

        // 1. Fetch Session First to Validate Institution Context
        $session = AcademicSession::find($request->academic_session_id);
        if (!$session || !in_array($session->institution_id, $allowedInstitutionIds)) {
            abort(403, 'Unauthorized action for this institution.');
        }

        $request->validate([
            'academic_session_id' => 'required|exists:academic_sessions,id',
            'name' => [
                'required', 
                'string', 
                'max:100',
                // Rule: Name must be unique within this specific Session and Institution
                Rule::unique('exams')->where(function ($query) use ($session) {
                    return $query->where('academic_session_id', $session->id)
                                 ->where('institution_id', $session->institution_id);
                })
            ],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:scheduled,ongoing,completed,published',
            'description' => 'nullable|string',
        ]);

        $exam = new Exam($request->all());
        $exam->institution_id = $session->institution_id;
        $exam->save();

        return response()->json(['message' => __('exam.messages.success_create'), 'redirect' => route('exams.index')]);
    }

    public function show(Exam $exam)
    {
        $allowedInstitutionIds = $this->getAllowedInstitutionIds();
        
        if (!in_array($exam->institution_id, $allowedInstitutionIds)) {
            abort(403);
        }

        $exam->load(['academicSession', 'institution']);
        
        return view('exams.show', compact('exam'));
    }

    public function edit(Exam $exam)
    {
        $allowedInstitutionIds = $this->getAllowedInstitutionIds();
        
        if (!in_array($exam->institution_id, $allowedInstitutionIds)) {
            abort(403);
        }

        $sessions = AcademicSession::with('institution')
            ->whereIn('institution_id', $allowedInstitutionIds)
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->id => $item->name . ' (' . $item->institution->name . ')'];
            });

        return view('exams.edit', compact('exam', 'sessions'));
    }

    public function update(Request $request, Exam $exam)
    {
        $request->validate([
            'academic_session_id' => 'required|exists:academic_sessions,id',
            'name' => [
                'required', 
                'string', 
                'max:100',
                // Rule: Ignore current exam ID, ensure unique name in this session
                Rule::unique('exams')->ignore($exam->id)->where(function ($query) use ($exam) {
                    return $query->where('academic_session_id', $exam->academic_session_id)
                                 ->where('institution_id', $exam->institution_id);
                })
            ],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:scheduled,ongoing,completed,published',
            'description' => 'nullable|string',
        ]);

        $exam->update($request->all());

        return response()->json(['message' => __('exam.messages.success_update'), 'redirect' => route('exams.index')]);
    }

    public function destroy(Exam $exam)
    {
        $exam->delete();
        return response()->json(['message' => __('exam.messages.success_delete')]);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('deleteAny', Exam::class); 
        $ids = $request->ids;
        if (!empty($ids)) {
            Exam::whereIn('id', $ids)->delete();
            return response()->json(['success' => __('exam.messages.success_delete')]);
        }
        return response()->json(['error' => __('exam.something_went_wrong')]);
    }
}