<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\GradeLevel;
use App\Models\Institution;
use App\Models\Department; 
use App\Models\AcademicUnit; // Added
use App\Models\Program; // Added
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
            // Added academicUnit relationship
            $data = Subject::with(['gradeLevel', 'institution', 'department', 'academicUnit']) 
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
                // Display UE if available
                ->addColumn('unit', function($row){
                    return $row->academicUnit ? $row->academicUnit->code : '-';
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
        
        // Grades
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
                'cycle' => is_object($item->education_cycle) ? $item->education_cycle->value : $item->education_cycle 
            ];
        });

        // Departments
        $departments = [];
        if ($institutionId) {
            $departments = Department::where('institution_id', $institutionId)->pluck('name', 'id');
        }

        // Programs (NEW: For filtering UEs)
        $programs = [];
        if ($institutionId) {
            $programs = Program::where('institution_id', $institutionId)->where('is_active', true)->pluck('name', 'id');
        }

        // Prerequisites
        $prerequisites = [];
        if ($institutionId) {
            $prerequisites = Subject::where('institution_id', $institutionId)->where('is_active', true)->pluck('name', 'id');
        }
            
        return view('subjects.create', compact('grades', 'institutions', 'institutionId', 'departments', 'prerequisites', 'programs'));
    }

    /**
     * AJAX: Get Academic Units based on Program and/or Grade
     */
    public function getUnits(Request $request)
    {
        $institutionId = $this->getInstitutionId() ?? $request->institution_id;
        
        $query = AcademicUnit::where('institution_id', $institutionId);

        if ($request->program_id) {
            $query->where('program_id', $request->program_id);
        }
        
        if ($request->grade_level_id) {
            $query->where('grade_level_id', $request->grade_level_id);
        }

        // Return formatted list
        $units = $query->get()->mapWithKeys(function($u) {
            return [$u->id => $u->code . ' - ' . $u->name];
        });

        return response()->json($units);
    }

    public function store(Request $request)
    {
        $institutionId = $this->getInstitutionId() ?? $request->institution_id;

        $validated = $request->validate([
            'institution_id' => $institutionId ? 'nullable' : 'required|exists:institutions,id',
            'grade_level_id' => 'required|exists:grade_levels,id',
            'department_id'  => 'nullable|exists:departments,id', 
            'academic_unit_id' => 'nullable|exists:academic_units,id',
            'prerequisite_id'=> 'nullable|exists:subjects,id', 
            'name'           => ['required', 'string', 'max:100', 
                Rule::unique('subjects')
                    ->where('grade_level_id', $request->grade_level_id)
            ],
            'code'           => 'nullable|string|max:30',
            'semester'       => 'nullable|string|max:20', 
            'type'           => 'required|in:theory,practical,both',
            'credit_hours'   => 'nullable|numeric|min:0', 
            'coefficient'    => 'nullable|numeric|min:0',
            'total_marks'    => 'required|integer|min:0',
            'passing_marks'  => 'required|integer|min:0|lte:total_marks',
            'is_active'      => 'boolean'
        ]);

        $validated['institution_id'] = $institutionId;

        // Auto-generate code if empty
        if (empty($validated['code'])) {
            $baseCode = Str::upper(Str::slug($validated['name'], ''));
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

    public function edit(Subject $subject)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $subject->institution_id != $institutionId) abort(403);

        $institutions = Institution::where('id', $subject->institution_id)->pluck('name', 'id');

        $grades = GradeLevel::where('institution_id', $subject->institution_id)
            ->orderBy('order_index')->get()->map(function($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'cycle' => is_object($item->education_cycle) ? $item->education_cycle->value : $item->education_cycle
                ];
            });

        $departments = Department::where('institution_id', $subject->institution_id)->pluck('name', 'id');
        
        // Programs for filter
        $programs = Program::where('institution_id', $subject->institution_id)->where('is_active', true)->pluck('name', 'id');
        
        // Get Pre-selected Program ID from the assigned Unit
        $selectedProgramId = $subject->academicUnit ? $subject->academicUnit->program_id : null;

        // Units (Load all initially, or based on saved program)
        $unitsQuery = AcademicUnit::where('institution_id', $subject->institution_id);
        if($selectedProgramId) $unitsQuery->where('program_id', $selectedProgramId);
        
        $units = $unitsQuery->get()->mapWithKeys(function($u) {
            return [$u->id => $u->code . ' - ' . $u->name];
        });
        
        $prerequisites = Subject::where('institution_id', $subject->institution_id)
            ->where('id', '!=', $subject->id) 
            ->where('is_active', true)
            ->pluck('name', 'id');

        return view('subjects.edit', compact('subject', 'grades', 'institutions', 'institutionId', 'departments', 'prerequisites', 'programs', 'units', 'selectedProgramId'));
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
            'academic_unit_id' => 'nullable|exists:academic_units,id', // Added
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
            'coefficient'    => 'nullable|numeric|min:0', // Added
            'total_marks'    => 'required|integer|min:0',
            'passing_marks'  => 'required|integer|min:0|lte:total_marks',
            'is_active'      => 'boolean'
        ]);

        if ($institutionId) {
            $validated['institution_id'] = $institutionId;
        }

        if (empty($validated['code'])) {
            $baseCode = Str::upper(Str::slug($validated['name'], ''));
            $code = $baseCode;
            $counter = 1;

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
    
    // bulkDelete method remains the same as previously provided...
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