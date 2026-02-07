<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\Department;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ProgramController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->setPageTitle(__('lmd.programs_page_title'));
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        
        if ($request->ajax()) {
            $data = Program::with('department')->where('institution_id', $institutionId);

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('department_name', fn($row) => $row->department->name ?? '-')
                ->addColumn('action', function($row){
                    return '<div class="d-flex">
                                <button class="btn btn-primary btn-xs edit-program me-1" data-json=\''.json_encode($row).'\'>'.__('finance.edit_fee').'</button>
                                <button class="btn btn-danger btn-xs delete-program" data-id="'.$row->id.'">'.__('finance.yes_delete').'</button>
                            </div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $departments = Department::where('institution_id', $institutionId)->pluck('name', 'id');

        return view('academics.programs.index', compact('departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'code' => 'required|string',
            'total_semesters' => 'required|integer|min:1',
            'duration_years' => 'required|integer|min:1',
        ]);

        Program::updateOrCreate(
            ['id' => $request->id],
            [
                'institution_id' => $this->getInstitutionId(),
                'department_id' => $request->department_id,
                'name' => $request->name,
                'code' => $request->code,
                'total_semesters' => $request->total_semesters,
                'duration_years' => $request->duration_years,
            ]
        );

        return response()->json(['message' => __('lmd.program_saved')]);
    }
    
    public function destroy($id)
    {
        $program = Program::where('institution_id', $this->getInstitutionId())->findOrFail($id);
        $program->delete();
        return response()->json(['message' => __('lmd.program_deleted')]);
    }
}