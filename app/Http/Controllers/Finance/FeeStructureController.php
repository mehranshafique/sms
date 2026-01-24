<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\BaseController;
use App\Models\FeeStructure;
use App\Models\FeeType;
use App\Models\GradeLevel;
use App\Models\ClassSection;
use App\Models\AcademicSession;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Illuminate\Support\Facades\DB;

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
        $institutionId = $this->getInstitutionId();

        if ($request->ajax()) {
            $data = FeeStructure::with(['feeType', 'gradeLevel', 'classSection'])
                ->select('fee_structures.*');

            if ($institutionId) {
                $data->where('fee_structures.institution_id', $institutionId);
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('fee_type', function($row){
                    return $row->feeType->name ?? 'N/A';
                })
                ->addColumn('parent_fee_name', function($row) use ($institutionId) {
                    if ($row->payment_mode === 'installment') {
                        // Attempt to find the parent Global fee
                        $global = FeeStructure::where('institution_id', $row->institution_id)
                            ->where('grade_level_id', $row->grade_level_id)
                            ->where('academic_session_id', $row->academic_session_id)
                            ->where('fee_type_id', $row->fee_type_id)
                            ->where('payment_mode', 'global')
                            ->first();
                        return $global ? $global->name : '-';
                    }
                    return '-'; 
                })
                ->addColumn('grade', function($row){
                    $grade = $row->gradeLevel->name ?? 'All Grades';
                    if($row->classSection) {
                        $grade .= ' (' . $row->classSection->name . ')';
                    } elseif($row->grade_level_id && is_null($row->class_section_id)) {
                        $grade .= ' (' . __('finance.all_sections') . ')';
                    }
                    return $grade;
                })
                ->editColumn('amount', function($row){
                    return number_format($row->amount, 2);
                })
                ->addColumn('mode', function($row){
                    $mode = ucfirst($row->payment_mode);
                    if($row->payment_mode == 'installment') {
                        $mode .= ' (' . ($row->installment_order ?? '-') . ')';
                    }
                    return $mode;
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
        $institutionId = $this->getInstitutionId();
        
        $feeTypesQuery = FeeType::where('is_active', true);
        if ($institutionId) {
            $feeTypesQuery->where('institution_id', $institutionId);
        }
        $feeTypes = $feeTypesQuery->pluck('name', 'id');

        $gradeLevelsQuery = GradeLevel::query();
        if ($institutionId) {
            $gradeLevelsQuery->where('institution_id', $institutionId);
        }
        $gradeLevels = $gradeLevelsQuery->pluck('name', 'id');
        
        $sessionQuery = AcademicSession::where('is_current', true);
        if($institutionId) {
            $sessionQuery->where('institution_id', $institutionId);
        }
        $session = $sessionQuery->first();

        return view('finance.fees.create', compact('feeTypes', 'gradeLevels', 'session'));
    }

    public function getClassSections(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        $gradeId = $request->grade_id;

        if (!$gradeId) {
            return response()->json([]);
        }

        $sections = ClassSection::where('grade_level_id', $gradeId);
        if ($institutionId) {
            $sections->where('institution_id', $institutionId);
        }

        return response()->json($sections->pluck('name', 'id'));
    }

    public function store(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        $request->validate([
            'name' => 'required|string|max:100',
            'fee_type_id' => 'required|exists:fee_types,id',
            'amount' => 'required|numeric|min:0',
            'frequency' => 'required|in:one_time,monthly,termly,yearly',
            'grade_level_id' => 'nullable|exists:grade_levels,id',
            'class_section_id' => 'nullable|exists:class_sections,id',
            'payment_mode' => 'required|in:global,installment',
            'installment_order' => 'nullable|required_if:payment_mode,installment|integer|min:1'
        ]);

        if(!$institutionId) {
             $feeType = FeeType::find($request->fee_type_id);
             $institutionId = $feeType->institution_id;
        }

        $session = AcademicSession::where('institution_id', $institutionId)->where('is_current', true)->first();

        if(!$session) {
            return response()->json(['message' => __('finance.no_active_session')], 422);
        }

        // --- VALIDATION: PREVENT DUPLICATE GLOBAL FEE CONFIGURATION ---
        if ($request->payment_mode === 'global') {
            $exists = FeeStructure::where('institution_id', $institutionId)
                ->where('academic_session_id', $session->id)
                ->where('fee_type_id', $request->fee_type_id)
                ->where('payment_mode', 'global')
                // Check scope overlap: Same Grade OR Same Class
                ->where(function($q) use ($request) {
                    if ($request->class_section_id) {
                        $q->where('class_section_id', $request->class_section_id);
                    } elseif ($request->grade_level_id) {
                        $q->where('grade_level_id', $request->grade_level_id)
                          ->whereNull('class_section_id'); // Ensure we target the grade-wide fee
                    } else {
                        // Global for ALL grades? (If your system supports it)
                        // If both are null, it implies institute-wide fee.
                        $q->whereNull('grade_level_id')->whereNull('class_section_id');
                    }
                })
                ->exists();

            if ($exists) {
                // Return a specific error code or message that the frontend can use to prompt user
                // Or simply block it. The prompt logic would require a multi-step confirmation flow
                // which is complex for a standard store method. 
                // Blocking with a clear message is safer and standard for API consistency.
                return response()->json([
                    'message' => __('finance.duplicate_global_config_error')
                ], 422);
            }
        }

        // --- VALIDATION: STRICT DEPENDENCY ON GLOBAL FEE (Existing Logic) ---
        if ($request->payment_mode === 'installment' && $request->grade_level_id) {
            
            // 1. Check for Existing Global Fee for this Grade/Session
            $globalFee = FeeStructure::where('institution_id', $institutionId)
                ->where('grade_level_id', $request->grade_level_id)
                ->where('payment_mode', 'global')
                ->where('academic_session_id', $session->id)
                ->sum('amount'); 

            if ($globalFee <= 0) {
                return response()->json([
                    'message' => __('finance.global_fee_missing_error')
                ], 422);
            }

            // Rule 2: Installments Sum check
            $existingInstallments = FeeStructure::where('institution_id', $institutionId)
                ->where('grade_level_id', $request->grade_level_id)
                ->where('payment_mode', 'installment')
                ->where('academic_session_id', $session->id)
                ->sum('amount');

            $newTotal = $existingInstallments + $request->amount;

            if ($newTotal > $globalFee) {
                return response()->json([
                    'message' => __('finance.installment_cap_error', [
                        'total' => number_format($newTotal, 2), 
                        'limit' => number_format($globalFee, 2)
                    ])
                ], 422);
            }
        }

        FeeStructure::create([
            'institution_id' => $institutionId,
            'academic_session_id' => $session->id,
            'name' => $request->name,
            'fee_type_id' => $request->fee_type_id,
            'amount' => $request->amount,
            'frequency' => $request->frequency,
            'grade_level_id' => $request->grade_level_id,
            'class_section_id' => $request->class_section_id,
            'payment_mode' => $request->payment_mode,
            'installment_order' => $request->installment_order,
        ]);

        return response()->json(['message' => __('finance.success_create'), 'redirect' => route('fees.index')]);
    }

    public function edit($id)
    {
        $feeStructure = FeeStructure::findOrFail($id);
        $institutionId = $this->getInstitutionId();

        if($institutionId && $feeStructure->institution_id != $institutionId) {
            abort(403);
        }
        
        $targetId = $institutionId ?? $feeStructure->institution_id;

        $feeTypes = FeeType::where('institution_id', $targetId)->where('is_active', true)->pluck('name', 'id');
        $gradeLevels = GradeLevel::where('institution_id', $targetId)->pluck('name', 'id');
        
        $classSections = [];
        if($feeStructure->grade_level_id) {
            $classSections = ClassSection::where('grade_level_id', $feeStructure->grade_level_id)
                                         ->where('institution_id', $targetId)
                                         ->pluck('name', 'id');
        }

        return view('finance.fees.edit', compact('feeStructure', 'feeTypes', 'gradeLevels', 'classSections'));
    }

    public function update(Request $request, $id)
    {
        $feeStructure = FeeStructure::findOrFail($id);
        $institutionId = $this->getInstitutionId();

        if($institutionId && $feeStructure->institution_id != $institutionId) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'fee_type_id' => 'required|exists:fee_types,id',
            'amount' => 'required|numeric|min:0',
            'frequency' => 'required|in:one_time,monthly,termly,yearly',
            'grade_level_id' => 'nullable|exists:grade_levels,id',
            'class_section_id' => 'nullable|exists:class_sections,id',
            'payment_mode' => 'required|in:global,installment',
            'installment_order' => 'nullable|required_if:payment_mode,installment|integer|min:1'
        ]);

        $session = AcademicSession::where('institution_id', $feeStructure->institution_id)->where('is_current', true)->first();

        if (!$session) {
            return response()->json(['message' => __('finance.no_active_session')], 422);
        }

        // --- VALIDATION 1: MODIFYING AN INSTALLMENT ---
        if ($request->payment_mode === 'installment' && $request->grade_level_id) {
            $globalFee = FeeStructure::where('institution_id', $feeStructure->institution_id)
                ->where('grade_level_id', $request->grade_level_id)
                ->where('payment_mode', 'global')
                ->where('academic_session_id', $session->id)
                ->sum('amount');

            if ($globalFee <= 0) {
                return response()->json(['message' => __('finance.global_fee_missing_error')], 422);
            }

            // Sum other installments + new amount
            $existingInstallments = FeeStructure::where('institution_id', $feeStructure->institution_id)
                ->where('grade_level_id', $request->grade_level_id)
                ->where('payment_mode', 'installment')
                ->where('academic_session_id', $session->id)
                ->where('id', '!=', $id) // Exclude self
                ->sum('amount');

            $newTotal = $existingInstallments + $request->amount;

            if ($newTotal > $globalFee) {
                return response()->json([
                    'message' => __('finance.installment_cap_error', [
                        'total' => number_format($newTotal, 2), 
                        'limit' => number_format($globalFee, 2)
                    ])
                ], 422);
            }
        }

        // --- VALIDATION 2: MODIFYING A GLOBAL FEE (Reducing Amount) ---
        if ($request->payment_mode === 'global' && $request->grade_level_id) {
            $totalInstallments = FeeStructure::where('institution_id', $feeStructure->institution_id)
                ->where('grade_level_id', $request->grade_level_id)
                ->where('payment_mode', 'installment')
                ->where('academic_session_id', $session->id)
                ->where('id', '!=', $id)
                ->sum('amount');

            if ($request->amount < $totalInstallments) {
                return response()->json([
                    'message' => __('finance.global_amount_too_low', [
                        'total' => number_format($totalInstallments, 2)
                    ])
                ], 422);
            }
        }

        $feeStructure->update([
            'name' => $request->name,
            'fee_type_id' => $request->fee_type_id,
            'amount' => $request->amount,
            'frequency' => $request->frequency,
            'grade_level_id' => $request->grade_level_id,
            'class_section_id' => $request->class_section_id,
            'payment_mode' => $request->payment_mode,
            'installment_order' => $request->installment_order,
        ]);

        return response()->json(['message' => __('finance.success_update'), 'redirect' => route('fees.index')]);
    }

    public function destroy($id)
    {
        $feeStructure = FeeStructure::findOrFail($id);
        $institutionId = $this->getInstitutionId();

        if($institutionId && $feeStructure->institution_id != $institutionId) {
            abort(403);
        }

        // --- VALIDATION: PREVENT DELETING GLOBAL IF INSTALLMENTS EXIST ---
        if ($feeStructure->payment_mode === 'global') {
            $hasInstallments = FeeStructure::where('institution_id', $feeStructure->institution_id)
                ->where('grade_level_id', $feeStructure->grade_level_id)
                ->where('academic_session_id', $feeStructure->academic_session_id)
                ->where('payment_mode', 'installment')
                ->exists();

            if ($hasInstallments) {
                return response()->json([
                    'message' => __('finance.cannot_delete_global_with_installments')
                ], 422);
            }
        }

        $feeStructure->delete();
        return response()->json(['message' => __('finance.success_delete')]);
    }
}