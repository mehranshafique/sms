<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\GradeLevel;
use App\Models\Institution;
use App\Models\Department; 
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SubjectController extends BaseController
{
    public function __construct()
    {
        $this->authorizeResource(Subject::class, 'subject');
        $this->setPageTitle(__('subject.page_title'));
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        if ($request->ajax()) {
            $data = Subject::with(['gradeLevel', 'institution', 'department']) 
                ->select('subjects.*');

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
                ->addColumn('department', function($row){
                    return $row->department->name ?? '-';
                })
                ->addColumn('credits', function($row){
                    return $row->credit_hours > 0 ? $row->credit_hours : '-';
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
        
        $grades = $gradeLevelsQuery->orderBy('order_index')->get()->map(function($item) use ($institutionId) {
            return [
                'id' => $item->id,
                'name' => $item->name . ($institutionId ? '' : ' (' . ($item->institution->name ?? '') . ')'),
                'cycle' => $item->education_cycle 
            ];
        });

        $departments = [];
        if ($institutionId) {
            $departments = Department::where('institution_id', $institutionId)->pluck('name', 'id');
        }

        $prerequisites = [];
        if ($institutionId) {
            $prerequisites = Subject::where('institution_id', $institutionId)->where('is_active', true)->pluck('name', 'id');
        }
            
        return view('subjects.create', compact('grades', 'institutions', 'institutionId', 'departments', 'prerequisites'));
    }

    public function store(Request $request)
    {
        $institutionId = $this->getInstitutionId() ?? $request->institution_id;

        $validated = $request->validate([
            'institution_id' => $institutionId ? 'nullable' : 'required|exists:institutions,id',
            'grade_level_id' => 'required|exists:grade_levels,id',
            'department_id'  => 'nullable|exists:departments,id', 
            'prerequisite_id'=> 'nullable|exists:subjects,id', 
            'name'           => ['required', 'string', 'max:100', 
                Rule::unique('subjects')
                    ->where('grade_level_id', $request->grade_level_id)
            ],
            'code'           => 'nullable|string|max:30',
            'semester'       => 'nullable|string|max:20', 
            'type'           => 'required|in:theory,practical,both',
            'credit_hours'   => 'nullable|numeric|min:0', 
            'total_marks'    => 'required|integer|min:0',
            'passing_marks'  => 'required|integer|min:0|lte:total_marks',
            'is_active'      => 'boolean'
        ]);

        $validated['institution_id'] = $institutionId;

        // Auto-generate code if empty
        if (empty($validated['code'])) {
            $baseCode = Str::upper(Str::slug($validated['name'], ''));
            // Take first 3-4 chars for brevity if needed, but full slug is safer for uniqueness base
            // Let's keep full slug to avoid collision initially
            $code = $baseCode;
            $counter = 1;

            while (Subject::where('institution_id', $institutionId)
                          ->where('code', $code)
                          ->exists()) {
                $code = $baseCode . $counter;
                $counter++;
            }
            $validated['code'] = $code;
        }

        Subject::create($validated);

        return response()->json(['message' => __('subject.messages.success_create'), 'redirect' => route('subjects.index')]);
    }

    public function show(Subject $subject)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $subject->institution_id != $institutionId) abort(403);
        
        $subject->load('gradeLevel', 'institution', 'department', 'prerequisite');
        return view('subjects.show', compact('subject'));
    }

    public function edit(Subject $subject)
    {
        $institutionId = $this->getInstitutionId();

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
        
        $grades = $gradeLevelsQuery->orderBy('order_index')->get()->map(function($item) use ($institutionId) {
            return [
                'id' => $item->id,
                'name' => $item->name . ($institutionId ? '' : ' (' . ($item->institution->name ?? '') . ')'),
                'cycle' => $item->education_cycle
            ];
        });

        $departments = Department::where('institution_id', $subject->institution_id)->pluck('name', 'id');
        
        $prerequisites = Subject::where('institution_id', $subject->institution_id)
            ->where('id', '!=', $subject->id) 
            ->where('is_active', true)
            ->pluck('name', 'id');

        return view('subjects.edit', compact('subject', 'grades', 'institutions', 'institutionId', 'departments', 'prerequisites'));
    }

    public function update(Request $request, Subject $subject)
    {
        $institutionId = $this->getInstitutionId();

        if ($institutionId && $subject->institution_id != $institutionId) {
            abort(403);
        }
        
        $validated = $request->validate([
            'institution_id' => $institutionId ? 'nullable' : 'required|exists:institutions,id',
            'grade_level_id' => 'required|exists:grade_levels,id',
            'department_id'  => 'nullable|exists:departments,id',
            'prerequisite_id'=> 'nullable|exists:subjects,id|different:id', 
            'name'           => ['required', 'string', 'max:100', 
                Rule::unique('subjects')
                    ->ignore($subject->id)
                    ->where('grade_level_id', $request->grade_level_id)
            ],
            'code'           => 'nullable|string|max:30',
            'semester'       => 'nullable|string|max:20',
            'type'           => 'required|in:theory,practical,both',
            'credit_hours'   => 'nullable|numeric|min:0',
            'total_marks'    => 'required|integer|min:0',
            'passing_marks'  => 'required|integer|min:0|lte:total_marks',
            'is_active'      => 'boolean'
        ]);

        if ($institutionId) {
            $validated['institution_id'] = $institutionId;
        }

        // Auto-generate code if empty
        if (empty($validated['code'])) {
            $baseCode = Str::upper(Str::slug($validated['name'], ''));
            $code = $baseCode;
            $counter = 1;

            // Check uniqueness against other subjects (excluding self)
            while (Subject::where('institution_id', $institutionId ?? $subject->institution_id)
                          ->where('code', $code)
                          ->where('id', '!=', $subject->id)
                          ->exists()) {
                $code = $baseCode . $counter;
                $counter++;
            }
            $validated['code'] = $code;
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
            if ($institutionId) {
                $query->where('institution_id', $institutionId);
            }
            $query->delete();
            return response()->json(['success' => __('subject.messages.success_delete')]);
        }
        return response()->json(['error' => __('subject.something_went_wrong')]);
    }
}