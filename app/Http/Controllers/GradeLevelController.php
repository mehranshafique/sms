<?php

namespace App\Http\Controllers;

use App\Models\GradeLevel;
use App\Models\Institution;
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
        $institutionId = Auth::user()->institute_id;

        if ($request->ajax()) {
            $data = GradeLevel::query()->select('grade_levels.*');

            // Filter by Institution if user is restricted
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
                    return ucfirst($row->education_cycle);
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
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

        // Stats (Adjusted for Super Admin view)
        $query = GradeLevel::query();
        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }
        
        $totalGrades = (clone $query)->count();
        $primaryGrades = (clone $query)->where('education_cycle', 'primary')->count();
        $secondaryGrades = (clone $query)->where('education_cycle', 'secondary')->count();
        $universityGrades = (clone $query)->where('education_cycle', 'university')->count();

        return view('grade_levels.index', compact('totalGrades', 'primaryGrades', 'secondaryGrades', 'universityGrades'));
    }

    public function create()
    {
        $institutionId = Auth::user()->institute_id;
        $institutions = Institution::where('is_active', true)->pluck('name', 'id');
        
        // Auto-suggest next order index
        $query = GradeLevel::query();
        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }
        $lastOrder = $query->max('order_index') ?? 0;
        $nextOrder = $lastOrder + 1;
        
        return view('grade_levels.create', compact('nextOrder', 'institutions'));
    }

    public function store(Request $request)
    {
        $userInstituteId = Auth::user()->institute_id;

        $validated = $request->validate([
            // If user has no institute (Super Admin), require selection. Else, verify it matches auth user.
            'institution_id' => $userInstituteId ? 'nullable' : 'required|exists:institutions,id',
            'name'           => [
                'required', 'string', 'max:100', 
                Rule::unique('grade_levels')->where('institution_id', $request->institution_id ?? $userInstituteId)
            ],
            'code'            => 'nullable|string|max:30',
            'order_index'     => 'required|integer|min:0',
            'education_cycle' => 'required|in:primary,secondary,university,vocational',
        ]);

        // Force Institution ID if user is not Super Admin
        if ($userInstituteId) {
            $validated['institution_id'] = $userInstituteId;
        }

        GradeLevel::create($validated);

        return response()->json(['message' => __('grade_level.messages.success_create'), 'redirect' => route('grade-levels.index')]);
    }

    public function edit(GradeLevel $grade_level)
    {
        $institutions = Institution::where('is_active', true)->pluck('name', 'id');
        return view('grade_levels.edit', compact('grade_level', 'institutions'));
    }

    public function update(Request $request, GradeLevel $grade_level)
    {
        $userInstituteId = Auth::user()->institute_id;

        $validated = $request->validate([
            'institution_id' => $userInstituteId ? 'nullable' : 'required|exists:institutions,id',
            'name'           => [
                'required', 'string', 'max:100', 
                Rule::unique('grade_levels')
                    ->ignore($grade_level->id)
                    ->where('institution_id', $request->institution_id ?? $userInstituteId)
            ],
            'code'            => 'nullable|string|max:30',
            'order_index'     => 'required|integer|min:0',
            'education_cycle' => 'required|in:primary,secondary,university,vocational',
        ]);

        if ($userInstituteId) {
            $validated['institution_id'] = $userInstituteId;
        }

        $grade_level->update($validated);

        return response()->json(['message' => __('grade_level.messages.success_update'), 'redirect' => route('grade-levels.index')]);
    }

    public function destroy(GradeLevel $grade_level)
    {
        $grade_level->delete();
        return response()->json(['message' => __('grade_level.messages.success_delete')]);
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('deleteAny', GradeLevel::class); 
        $ids = $request->ids;
        if (!empty($ids)) {
            GradeLevel::whereIn('id', $ids)->delete();
            return response()->json(['success' => __('grade_level.messages.success_delete')]);
        }
        return response()->json(['error' => __('grade_level.something_went_wrong')]);
    }
}