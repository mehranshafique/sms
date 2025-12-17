<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\GradeLevel;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class SubjectController extends BaseController
{
    public function __construct()
    {
        $this->authorizeResource(Subject::class, 'subject');
        $this->setPageTitle(__('subject.page_title'));
    }

    public function index(Request $request)
    {
        $institutionId = Auth::user()->institute_id;

        if ($request->ajax()) {
            $data = Subject::with('gradeLevel')
                ->where('institution_id', $institutionId)
                ->select('subjects.*');

            if ($request->has('grade_level_id') && $request->grade_level_id) {
                $data->where('grade_level_id', $request->grade_level_id);
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('checkbox', function($row){
                    if(auth()->user()->can('delete', $row)){
                        return '<div class="form-check custom-checkbox checkbox-primary check-lg me-3">
                                    <input type="checkbox" class="form-check-input single-checkbox" value="'.$row->id.'">
                                    <label class="form-check-label"></label>
                                </div>';
                    }
                    return '';
                })
                ->addColumn('grade', function($row){
                    return $row->gradeLevel->name ?? 'N/A';
                })
                ->editColumn('type', function($row){
                    return ucfirst($row->type);
                })
                ->editColumn('is_active', function($row){
                    return $row->is_active 
                        ? '<span class="badge badge-success">'.__('subject.active').'</span>' 
                        : '<span class="badge badge-danger">'.__('subject.inactive').'</span>';
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
                    
                    if(auth()->user()->can('view', $row)){
                        $btn .= '<a href="'.route('subjects.show', $row->id).'" class="btn btn-info shadow btn-xs sharp me-1"><i class="fa fa-eye"></i></a>';
                    }

                    if(auth()->user()->can('update', $row)){
                        $btn .= '<a href="'.route('subjects.edit', $row->id).'" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fa fa-pencil"></i></a>';
                    }
                    
                    if(auth()->user()->can('delete', $row)){
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'"><i class="fa fa-trash"></i></button>';
                    }
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'is_active', 'action'])
                ->make(true);
        }

        $totalSubjects = Subject::where('institution_id', $institutionId)->count();
        $activeSubjects = Subject::where('institution_id', $institutionId)->where('is_active', true)->count();
        
        return view('subjects.index', compact('totalSubjects', 'activeSubjects'));
    }

    public function create()
    {
        $institutionId = Auth::user()->institute_id;
        // Fix: Ensure gradeLevels is fetched and passed
        $gradeLevels = GradeLevel::where('institution_id', $institutionId)
            ->orderBy('order_index')
            ->pluck('name', 'id');
            
        return view('subjects.create', compact('gradeLevels'));
    }

    public function store(Request $request)
    {
        $institutionId = Auth::user()->institute_id;

        $validated = $request->validate([
            'grade_level_id' => 'required|exists:grade_levels,id',
            'name'           => ['required', 'string', 'max:100', 
                Rule::unique('subjects')->where('grade_level_id', $request->grade_level_id)
            ],
            'code'           => 'nullable|string|max:30',
            'type'           => 'required|in:theory,practical,both',
            'credit_hours'   => 'nullable|integer|min:0',
            'total_marks'    => 'required|integer|min:0',
            'passing_marks'  => 'required|integer|min:0|lte:total_marks',
            'is_active'      => 'boolean'
        ]);

        $validated['institution_id'] = $institutionId;

        Subject::create($validated);

        return response()->json(['message' => __('subject.messages.success_create'), 'redirect' => route('subjects.index')]);
    }

    public function show(Subject $subject)
    {
        $subject->load('gradeLevel');
        return view('subjects.show', compact('subject'));
    }

    public function edit(Subject $subject)
    {
        $institutionId = Auth::user()->institute_id;
        // Fix: Ensure gradeLevels is fetched and passed for edit too
        $gradeLevels = GradeLevel::where('institution_id', $institutionId)
            ->orderBy('order_index')
            ->pluck('name', 'id');

        return view('subjects.edit', compact('subject', 'gradeLevels'));
    }

    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'grade_level_id' => 'required|exists:grade_levels,id',
            'name'           => ['required', 'string', 'max:100', 
                Rule::unique('subjects')
                    ->ignore($subject->id)
                    ->where('grade_level_id', $request->grade_level_id)
            ],
            'code'           => 'nullable|string|max:30',
            'type'           => 'required|in:theory,practical,both',
            'credit_hours'   => 'nullable|integer|min:0',
            'total_marks'    => 'required|integer|min:0',
            'passing_marks'  => 'required|integer|min:0|lte:total_marks',
            'is_active'      => 'boolean'
        ]);

        $subject->update($validated);

        return response()->json(['message' => __('subject.messages.success_update'), 'redirect' => route('subjects.index')]);
    }

    public function destroy(Subject $subject)
    {
        $subject->delete();
        return response()->json(['message' => __('subject.messages.success_delete')]);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('deleteAny', Subject::class); 
        $ids = $request->ids;
        if (!empty($ids)) {
            Subject::whereIn('id', $ids)->delete();
            return response()->json(['success' => __('subject.messages.success_delete')]);
        }
        return response()->json(['error' => __('subject.something_went_wrong')]);
    }
}