<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\GradeLevel;
use App\Models\Institution;
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
        // 1. Get Context
        $institutionId = $this->getInstitutionId();

        if ($request->ajax()) {
            $data = Subject::with(['gradeLevel', 'institution'])
                ->select('subjects.*');

            // 2. Strict Scoping
            if ($institutionId) {
                $data->where('institution_id', $institutionId);
            }

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

        // Stats Logic - Scoped
        $query = Subject::query();
        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }
        
        $totalSubjects = (clone $query)->count();
        $activeSubjects = (clone $query)->where('is_active', true)->count();
        
        return view('subjects.index', compact('totalSubjects', 'activeSubjects'));
    }

    public function create()
    {
        $institutionId = $this->getInstitutionId();
        
        $institutions = [];
        if ($institutionId) {
            $institutions = Institution::where('id', $institutionId)->pluck('name', 'id');
        } elseif (Auth::user()->hasRole('Super Admin')) {
            $institutions = Institution::where('is_active', true)->pluck('name', 'id');
        }
        
        $gradeLevelsQuery = GradeLevel::query();
        if ($institutionId) {
            $gradeLevelsQuery->where('institution_id', $institutionId);
        } else {
            $gradeLevelsQuery->with('institution');
        }
        
        $gradeLevelsCollection = $gradeLevelsQuery->orderBy('order_index')->get();
        
        $gradeLevels = $gradeLevelsCollection->mapWithKeys(function($item) use ($institutionId) {
            $label = $item->name;
            if (!$institutionId && $item->institution) {
                $label .= ' (' . $item->institution->name . ')';
            }
            return [$item->id => $label];
        });
            
        return view('subjects.create', compact('gradeLevels', 'institutions', 'institutionId'));
    }

    public function store(Request $request)
    {
        // 1. Resolve ID
        $institutionId = $this->getInstitutionId() ?? $request->institution_id;

        $validated = $request->validate([
            'institution_id' => $institutionId ? 'nullable' : 'required|exists:institutions,id',
            'grade_level_id' => 'required|exists:grade_levels,id',
            'name'           => ['required', 'string', 'max:100', 
                // Rule: Unique name per grade level
                Rule::unique('subjects')
                    ->where('grade_level_id', $request->grade_level_id)
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
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $subject->institution_id != $institutionId) abort(403);
        
        $subject->load('gradeLevel', 'institution');
        return view('subjects.show', compact('subject'));
    }

    public function edit(Subject $subject)
    {
        $institutionId = $this->getInstitutionId();

        // Strict Check
        if ($institutionId && $subject->institution_id != $institutionId) {
            abort(403, 'Unauthorized access.');
        }

        $institutions = Institution::where('id', $subject->institution_id)->pluck('name', 'id');

        $gradeLevelsQuery = GradeLevel::query();
        if ($institutionId) {
            $gradeLevelsQuery->where('institution_id', $institutionId);
        } else {
            $gradeLevelsQuery->with('institution');
        }
        
        $gradeLevelsCollection = $gradeLevelsQuery->orderBy('order_index')->get();
        $gradeLevels = $gradeLevelsCollection->mapWithKeys(function($item) use ($institutionId) {
            $label = $item->name;
            if (!$institutionId && $item->institution) {
                $label .= ' (' . $item->institution->name . ')';
            }
            return [$item->id => $label];
        });

        return view('subjects.edit', compact('subject', 'gradeLevels', 'institutions', 'institutionId'));
    }

    public function update(Request $request, Subject $subject)
    {
        $institutionId = $this->getInstitutionId();

        if ($institutionId && $subject->institution_id != $institutionId) {
            abort(403);
        }
        
        $targetId = $institutionId ?? $subject->institution_id;

        $validated = $request->validate([
            'institution_id' => $institutionId ? 'nullable' : 'required|exists:institutions,id',
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

        if ($institutionId) {
            $validated['institution_id'] = $institutionId;
        }

        $subject->update($validated);

        return response()->json(['message' => __('subject.messages.success_update'), 'redirect' => route('subjects.index')]);
    }

    public function destroy(Subject $subject)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $subject->institution_id != $institutionId) abort(403);

        $subject->delete();
        return response()->json(['message' => __('subject.messages.success_delete')]);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('deleteAny', Subject::class); 

        $ids = $request->ids;
        if (!empty($ids)) {
            $institutionId = $this->getInstitutionId();
            
            $query = Subject::whereIn('id', $ids);
            
            // Secure Bulk Delete
            if ($institutionId) {
                $query->where('institution_id', $institutionId);
            }
            
            $query->delete();
            return response()->json(['success' => __('subject.messages.success_delete')]);
        }
        return response()->json(['error' => __('subject.something_went_wrong')]);
    }
}