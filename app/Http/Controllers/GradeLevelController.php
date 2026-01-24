<?php

namespace App\Http\Controllers;

use App\Models\GradeLevel;
use App\Models\Institution;
use App\Enums\AcademicType;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str; 

class GradeLevelController extends BaseController
{
    public function __construct()
    {
        $this->authorizeResource(GradeLevel::class, 'grade_level');
        $this->setPageTitle(__('grade_level.page_title'));
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        if ($request->ajax()) {
            $data = GradeLevel::with('institution')->select('grade_levels.*');

            if ($institutionId) {
                $data->where('institution_id', $institutionId);
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
                ->addColumn('institution_name', function($row){
                    return $row->institution->name ?? 'N/A';
                })
                ->editColumn('education_cycle', function($row){
                    $val = is_object($row->education_cycle) ? $row->education_cycle->value : $row->education_cycle;
                    return __('grade_level.cycle_' . $val);
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex">';
                    if(auth()->user()->can('update', $row)){
                        $btn .= '<a href="'.route('grade-levels.edit', $row->id).'" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fa fa-pencil"></i></a>';
                    }
                    if(auth()->user()->can('delete', $row)){
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'"><i class="fa fa-trash"></i></button>';
                    }
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['checkbox', 'action'])
                ->make(true);
        }

        $query = GradeLevel::query();
        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        $stats = $query->selectRaw("
            count(*) as total,
            sum(case when education_cycle = 'primary' then 1 else 0 end) as primary_count,
            sum(case when education_cycle = 'secondary' then 1 else 0 end) as secondary_count,
            sum(case when education_cycle = 'university' then 1 else 0 end) as university_count
        ")->first();

        $totalGrades = $stats->total ?? 0;
        $primaryGrades = $stats->primary_count ?? 0;
        $secondaryGrades = $stats->secondary_count ?? 0;
        $universityGrades = $stats->university_count ?? 0;

        return view('grade_levels.index', compact('totalGrades', 'primaryGrades', 'secondaryGrades', 'universityGrades'));
    }

    public function create()
    {
        $institutionId = $this->getInstitutionId();
        
        $institutes = [];
        $institutionType = 'mixed'; // Default

        if ($institutionId) {
            $inst = Institution::find($institutionId);
            $institutionType = $inst->type ?? 'mixed';
        } elseif (auth()->user()->hasRole('Super Admin')) {
            $institutes = Institution::pluck('name', 'id');
        }

        return view('grade_levels.create', compact('institutionId', 'institutes', 'institutionType'));
    }

    public function store(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        $targetInstituteId = $institutionId ?? $request->institution_id;

        // Force Cycle based on Institution Type if not Mixed/Super Admin context
        if ($institutionId) {
            $inst = Institution::find($institutionId);
            if ($inst && in_array($inst->type, ['primary', 'secondary', 'university'])) {
                $request->merge(['education_cycle' => $inst->type]);
            }
        }

        $request->validate([
            'institution_id'  => $institutionId ? 'nullable' : 'required|exists:institutions,id',
            'code'            => 'nullable|string|max:30',
            'order_index'     => 'required|integer|min:0',
            'education_cycle' => 'required|in:primary,secondary,university,vocational',
            'name'            => [
                'required',
                'string',
                'max:100',
                Rule::unique('grade_levels')->where(function ($query) use ($targetInstituteId, $request) {
                    return $query->where('institution_id', $targetInstituteId)
                                 ->where('education_cycle', $request->education_cycle);
                }),
            ],
        ]);

        try {
            $data = $request->only(['name', 'code', 'order_index', 'education_cycle']);
            $data['institution_id'] = $targetInstituteId;

            if (empty($data['code'])) {
                $data['code'] = Str::upper($data['name']); 
            }

            GradeLevel::create($data);

            return response()->json(['message' => __('grade_level.messages.success_create'), 'redirect' => route('grade-levels.index')]);
        } catch (QueryException $e) {
            $errorCode = $e->errorInfo[1] ?? 0;
            if ($errorCode == 1062) {
                return response()->json(['message' => __('grade_level.messages.duplicate_entry') ?? 'A grade level with this name already exists in this education cycle.'], 422);
            }
            return response()->json(['message' => __('grade_level.messages.error_occurred') . ': ' . $e->getMessage()], 500);
        }
    }

    public function edit(GradeLevel $grade_level)
    {
        $institutionId = $this->getInstitutionId();
        
        if ($institutionId && $grade_level->institution_id != $institutionId) {
            abort(403);
        }

        $institutes = [];
        $institutionType = 'mixed'; 

        if ($institutionId) {
            $inst = Institution::find($institutionId);
            $institutionType = $inst->type ?? 'mixed';
        } elseif (auth()->user()->hasRole('Super Admin') && !$institutionId) {
            $institutes = Institution::pluck('name', 'id');
        }

        return view('grade_levels.edit', compact('grade_level', 'institutionId', 'institutes', 'institutionType'));
    }

    public function update(Request $request, GradeLevel $grade_level)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $grade_level->institution_id != $institutionId) abort(403);

        $targetInstituteId = $institutionId ?? $request->input('institution_id', $grade_level->institution_id);

        // Force Cycle Update Logic
        if ($institutionId) {
            $inst = Institution::find($institutionId);
            if ($inst && in_array($inst->type, ['primary', 'secondary', 'university'])) {
                $request->merge(['education_cycle' => $inst->type]);
            }
        }

        $request->validate([
            'institution_id'  => $institutionId ? 'nullable' : 'required|exists:institutions,id',
            'code'            => 'nullable|string|max:30',
            'order_index'     => 'required|integer|min:0',
            'education_cycle' => 'required|in:primary,secondary,university,vocational',
            'name'            => [
                'required',
                'string',
                'max:100',
                Rule::unique('grade_levels')->where(function ($query) use ($targetInstituteId, $request) {
                    return $query->where('institution_id', $targetInstituteId)
                                 ->where('education_cycle', $request->education_cycle);
                })->ignore($grade_level->id),
            ],
        ]);

        try {
            $data = $request->only(['name', 'code', 'order_index', 'education_cycle']);
            if ($institutionId) {
                $data['institution_id'] = $institutionId;
            } elseif ($request->has('institution_id')) {
                $data['institution_id'] = $request->institution_id;
            }

            if (empty($data['code'])) {
                $data['code'] = Str::upper($data['name']);
            }

            $grade_level->update($data);

            return response()->json(['message' => __('grade_level.messages.success_update'), 'redirect' => route('grade-levels.index')]);
        } catch (QueryException $e) {
            $errorCode = $e->errorInfo[1] ?? 0;
            if ($errorCode == 1062) {
                return response()->json(['message' => __('grade_level.messages.duplicate_entry') ?? 'A grade level with this name already exists in this education cycle.'], 422);
            }
            return response()->json(['message' => __('grade_level.messages.error_occurred') . ': ' . $e->getMessage()], 500);
        }
    }

    public function destroy(GradeLevel $grade_level)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $grade_level->institution_id != $institutionId) abort(403);

        try {
            $grade_level->delete();
            return response()->json(['message' => __('grade_level.messages.success_delete')]);
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? 0) == 1451) {
                return response()->json(['message' => __('grade_level.messages.cannot_delete_linked_data') ?? 'Cannot delete this grade level because it is linked to other data.'], 422);
            }
            return response()->json(['message' => __('grade_level.messages.error_occurred') . ': ' . $e->getMessage()], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('deleteAny', GradeLevel::class); 

        $ids = $request->ids;
        if (!empty($ids)) {
            $institutionId = $this->getInstitutionId();
            
            try {
                $query = GradeLevel::whereIn('id', $ids);
                
                if ($institutionId) {
                    $query->where('institution_id', $institutionId);
                }
                
                $query->delete();
                return response()->json(['success' => __('grade_level.messages.success_delete')]);
            } catch (QueryException $e) {
                if (($e->errorInfo[1] ?? 0) == 1451) {
                    return response()->json(['error' => __('grade_level.messages.cannot_delete_linked_data') ?? 'Cannot delete selected records because they are linked to other data.'], 422);
                }
                return response()->json(['error' => __('grade_level.messages.error_occurred') . ': ' . $e->getMessage()], 500);
            }
        }
        return response()->json(['error' => __('grade_level.something_went_wrong')], 400);
    }
}