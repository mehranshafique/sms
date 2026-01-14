<?php

namespace App\Http\Controllers;

use App\Models\GradeLevel;
use App\Models\Institution;
use App\Enums\AcademicType;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

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
                    // Ensure we get the string value even if cast to Enum
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

        // --- FETCH STATISTICS FOR CARDS ---
        $query = GradeLevel::query();
        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        // FIX: Match these values to your Database Enum ('primary','secondary','university')
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
        if (auth()->user()->hasRole('Super Admin') && !$institutionId) {
            $institutes = Institution::pluck('name', 'id');
        }

        return view('grade_levels.create', compact('institutionId', 'institutes'));
    }

    public function store(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        $validated = $request->validate([
            'institution_id'  => $institutionId ? 'nullable' : 'required|exists:institutions,id',
            'name'            => 'required|string|max:100',
            'code'            => 'nullable|string|max:30',
            'order_index'     => 'required|integer|min:0',
            // FIX: Validate against DB expected values
            'education_cycle' => 'required|in:primary,secondary,university,vocational',
        ]);

        if ($institutionId) {
            $validated['institution_id'] = $institutionId;
        }

        GradeLevel::create($validated);

        return response()->json(['message' => __('grade_level.messages.success_create'), 'redirect' => route('grade-levels.index')]);
    }

    public function edit(GradeLevel $grade_level)
    {
        $institutionId = $this->getInstitutionId();
        
        if ($institutionId && $grade_level->institution_id != $institutionId) {
            abort(403);
        }

        $institutes = [];
        if (auth()->user()->hasRole('Super Admin') && !$institutionId) {
            $institutes = Institution::pluck('name', 'id');
        }

        return view('grade_levels.edit', compact('grade_level', 'institutionId', 'institutes'));
    }

    public function update(Request $request, GradeLevel $grade_level)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $grade_level->institution_id != $institutionId) abort(403);

        $validated = $request->validate([
            'institution_id'  => $institutionId ? 'nullable' : 'required|exists:institutions,id',
            'name'            => 'required|string|max:100',
            'code'            => 'nullable|string|max:30',
            'order_index'     => 'required|integer|min:0',
            'education_cycle' => 'required|in:primary,secondary,university,vocational',
        ]);

        if ($institutionId) {
            $validated['institution_id'] = $institutionId;
        }

        $grade_level->update($validated);

        return response()->json(['message' => __('grade_level.messages.success_update'), 'redirect' => route('grade-levels.index')]);
    }

    public function destroy(GradeLevel $grade_level)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $grade_level->institution_id != $institutionId) abort(403);

        $grade_level->delete();
        return response()->json(['message' => __('grade_level.messages.success_delete')]);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('deleteAny', GradeLevel::class); 

        $ids = $request->ids;
        if (!empty($ids)) {
            $institutionId = $this->getInstitutionId();
            
            $query = GradeLevel::whereIn('id', $ids);
            
            if ($institutionId) {
                $query->where('institution_id', $institutionId);
            }
            
            $query->delete();
            return response()->json(['success' => __('grade_level.messages.success_delete')]);
        }
        return response()->json(['error' => __('grade_level.something_went_wrong')]);
    }
}