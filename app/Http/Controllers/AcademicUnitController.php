<?php

namespace App\Http\Controllers;

use App\Models\AcademicUnit;
use App\Models\Program;
use App\Models\GradeLevel;
use App\Models\Subject;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AcademicUnitController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->setPageTitle(__('lmd.units_page_title'));
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        
        if ($request->ajax()) {
            $data = AcademicUnit::with(['gradeLevel', 'program'])
                ->where('institution_id', $institutionId)
                ->select('academic_units.*');

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('type', fn($row) => __('lmd.' . $row->type))
                ->addColumn('link', function($row) {
                    if($row->program) return $row->program->code . ' (Sem '.$row->semester.')';
                    return $row->gradeLevel->name ?? '-';
                })
                ->addColumn('subjects_count', fn($row) => $row->subjects()->count())
                ->addColumn('action', function($row){
                    return '<div class="d-flex">
                                <button class="btn btn-primary btn-xs edit-unit me-1" data-json=\''.json_encode($row).'\'>'.__('finance.edit_fee').'</button>
                                <button class="btn btn-danger btn-xs delete-unit" data-id="'.$row->id.'">'.__('finance.yes_delete').'</button>
                            </div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $grades = GradeLevel::where('institution_id', $institutionId)->pluck('name', 'id');
        $programs = Program::where('institution_id', $institutionId)->get();

        return view('academics.units.index', compact('grades', 'programs'));
    }

    public function store(Request $request)
    {
        // Validation: Must select EITHER a Program OR a Grade Level
        $request->validate([
            'name' => 'required|string',
            'type' => 'required|in:fundamental,transversal,optional',
            'program_id' => 'nullable|required_without:grade_level_id|exists:programs,id',
            'grade_level_id' => 'nullable|required_without:program_id|exists:grade_levels,id',
            'semester' => 'required|integer',
            'code' => 'nullable|string'
        ]);

        $institutionId = $this->getInstitutionId();

        // Auto-Generate Code if missing
        $code = $request->code ?? strtoupper(substr($request->name, 0, 3)) . '-' . $request->semester;

        // If Program Selected, we can optionally find the Grade Level it corresponds to
        // For simplicity, we just save what the user provided. The Migration now allows nullable grade_level_id.
        
        AcademicUnit::updateOrCreate(
            ['id' => $request->id],
            [
                'institution_id' => $institutionId,
                'program_id' => $request->program_id,
                'grade_level_id' => $request->grade_level_id,
                'name' => $request->name,
                'code' => $code,
                'type' => $request->type,
                'semester' => $request->semester,
            ]
        );

        return response()->json(['message' => __('lmd.unit_saved')]);
    }
    
    public function destroy($id)
    {
        $unit = AcademicUnit::where('institution_id', $this->getInstitutionId())->findOrFail($id);
        $unit->delete();
        return response()->json(['message' => __('lmd.unit_deleted')]);
    }
    /**
     * Assign Subjects to a Unit
     */
    public function assignSubjects(Request $request)
    {
        $request->validate([
            'unit_id' => 'required|exists:academic_units,id',
            'subject_ids' => 'required|array'
        ]);

        Subject::whereIn('id', $request->subject_ids)->update([
            'academic_unit_id' => $request->unit_id
        ]);

        return response()->json(['message' => __('lmd.subjects_assigned')]);
    }
}