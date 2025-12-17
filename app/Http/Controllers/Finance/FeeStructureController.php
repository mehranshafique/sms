<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\BaseController;
use App\Models\FeeStructure;
use App\Models\FeeType;
use App\Models\GradeLevel;
use App\Models\AcademicSession;
use App\Models\Institution;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Middleware\PermissionMiddleware;

class FeeStructureController extends BaseController
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':fee_structure.view')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':fee_structure.create')->only(['create', 'store']);
        $this->setPageTitle(__('finance.fee_structure_title'));
    }

    public function index(Request $request)
    {
        $institutionId = Auth::user()->institute_id;

        if ($request->ajax()) {
            $data = FeeStructure::with(['feeType', 'gradeLevel'])
                ->select('fee_structures.*');

            if ($institutionId) {
                $data->where('institution_id', $institutionId);
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('fee_type', function($row){
                    return $row->feeType->name ?? 'N/A';
                })
                ->addColumn('grade', function($row){
                    return $row->gradeLevel->name ?? 'All Grades';
                })
                ->editColumn('amount', function($row){
                    return number_format($row->amount, 2);
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
                    if(auth()->user()->can('fee_structure.update')){
                        $btn .= '<a href="'.route('fees.edit', $row->id).'" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fa fa-pencil"></i></a>';
                    }
                    if(auth()->user()->can('fee_structure.delete')){
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'"><i class="fa fa-trash"></i></button>';
                    }
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('finance.fees.index');
    }

    public function create()
    {
        $institutionId = Auth::user()->institute_id;
        
        // 1. Fetch Fee Types
        $feeTypesQuery = FeeType::where('is_active', true);
        if ($institutionId) {
            $feeTypesQuery->where('institution_id', $institutionId);
        }
        $feeTypes = $feeTypesQuery->pluck('name', 'id');

        // 2. Fetch Grade Levels
        $gradeLevelsQuery = GradeLevel::query();
        if ($institutionId) {
            $gradeLevelsQuery->where('institution_id', $institutionId);
        }
        $gradeLevels = $gradeLevelsQuery->pluck('name', 'id');
        
        // 3. Get Active Session (Check Institution specific or global)
        $sessionQuery = AcademicSession::where('is_current', true);
        if($institutionId) {
            $sessionQuery->where('institution_id', $institutionId);
        }
        $session = $sessionQuery->first();

        // Warning if no types exist
        if($feeTypes->isEmpty()){
            // Ideally, flash a message or handle in view, but we'll proceed
        }

        return view('finance.fees.create', compact('feeTypes', 'gradeLevels', 'session'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'fee_type_id' => 'required|exists:fee_types,id',
            'amount' => 'required|numeric|min:0',
            'frequency' => 'required|in:one_time,monthly,termly,yearly',
            'grade_level_id' => 'nullable|exists:grade_levels,id',
        ]);

        $institutionId = Auth::user()->institute_id;
        
        // Handle Super Admin creating for specific institute (advanced scenario)
        // For now, assume Super Admin creates for first institute or context is set
        if(!$institutionId) {
             // Try to infer from FeeType
             $feeType = FeeType::find($request->fee_type_id);
             $institutionId = $feeType->institution_id;
        }

        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();

        if(!$session) {
            return response()->json(['message' =>(__('finance.no_active_session'))], 422);
        }

        FeeStructure::create([
            'institution_id' => $institutionId,
            'academic_session_id' => $session->id,
            'name' => $request->name,
            'fee_type_id' => $request->fee_type_id,
            'amount' => $request->amount,
            'frequency' => $request->frequency,
            'grade_level_id' => $request->grade_level_id,
        ]);

        return response()->json(['message' => __('finance.success_create'), 'redirect' => route('fees.index')]);
    }

    public function edit($id)
    {
        $feeStructure = FeeStructure::findOrFail($id);
        $institutionId = Auth::user()->institute_id ?? $feeStructure->institution_id;

        $feeTypes = FeeType::where('institution_id', $institutionId)->where('is_active', true)->pluck('name', 'id');
        $gradeLevels = GradeLevel::where('institution_id', $institutionId)->pluck('name', 'id');

        return view('finance.fees.edit', compact('feeStructure', 'feeTypes', 'gradeLevels'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'fee_type_id' => 'required|exists:fee_types,id',
            'amount' => 'required|numeric|min:0',
            'frequency' => 'required|in:one_time,monthly,termly,yearly',
            'grade_level_id' => 'nullable|exists:grade_levels,id',
        ]);

        $feeStructure = FeeStructure::findOrFail($id);
        
        $feeStructure->update([
            'name' => $request->name,
            'fee_type_id' => $request->fee_type_id,
            'amount' => $request->amount,
            'frequency' => $request->frequency,
            'grade_level_id' => $request->grade_level_id,
        ]);

        return response()->json(['message' => 'Fee structure updated successfully.', 'redirect' => route('fees.index')]);
    }

    public function destroy($id)
    {
        FeeStructure::destroy($id);
        return response()->json(['message' => 'Fee structure deleted successfully.']);
    }
}