<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\Staff;
use App\Models\SalaryStructure;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class SalaryStructureController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->setPageTitle(__('payroll.salary_structure'));
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        if ($request->ajax()) {
            $data = Staff::with(['salaryStructure', 'user'])
                ->where('institution_id', $institutionId)
                ->where('status', 'active')
                ->select('staff.*'); 

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('name', function($row){
                    return $row->full_name ?? $row->user->name ?? 'N/A';
                })
                ->addColumn('base_salary', function($row){
                    if($row->salaryStructure) {
                        return number_format($row->salaryStructure->base_salary, 2) . 
                               ($row->salaryStructure->payment_basis === 'hourly' ? ' /hr' : '');
                    }
                    return '<span class="badge badge-warning text-white">Not Set</span>';
                })
                ->addColumn('allowances', function($row){
                    $data = $row->salaryStructure ? $row->salaryStructure->allowances : null;
                    
                    // Safety check: Force decode if string, fallback to empty array
                    if (is_string($data)) {
                        $data = json_decode($data, true);
                    }
                    
                    if (!is_array($data) || empty($data)) return '-';
                    
                    return count($data) . ' items';
                })
                ->addColumn('net_estimate', function($row){
                    if (!$row->salaryStructure) return '-';
                    $s = $row->salaryStructure;
                    
                    // Ensure we work with arrays
                    $allowances = $s->allowances;
                    if(is_string($allowances)) $allowances = json_decode($allowances, true);
                    if(!is_array($allowances)) $allowances = [];

                    $deductions = $s->deductions;
                    if(is_string($deductions)) $deductions = json_decode($deductions, true);
                    if(!is_array($deductions)) $deductions = [];

                    // Safely sum up amounts. Supports both [['amount'=>50]] and ['Transport'=>50] formats
                    $totalA = collect($allowances)->sum(function($item) {
                        return is_array($item) ? ($item['amount'] ?? 0) : (is_numeric($item) ? $item : 0);
                    });
                    
                    $totalD = collect($deductions)->sum(function($item) {
                        return is_array($item) ? ($item['amount'] ?? 0) : (is_numeric($item) ? $item : 0);
                    });

                    return number_format(($s->base_salary + $totalA) - $totalD, 2);
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex justify-content-end">';
                    $btn .= '<a href="'.route('salary-structures.edit', $row->id).'" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fa fa-pencil"></i></a>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['base_salary', 'action'])
                ->make(true);
        }

        return view('payroll.structure.index');
    }

    public function edit($staffId)
    {
        $institutionId = $this->getInstitutionId();
        $staff = Staff::where('id', $staffId)->where('institution_id', $institutionId)->firstOrFail();
        
        $structure = SalaryStructure::firstOrNew(['staff_id' => $staff->id]);

        return view('payroll.structure.edit', compact('staff', 'structure'));
    }

    public function update(Request $request, $staffId)
    {
        $institutionId = $this->getInstitutionId();
        $staff = Staff::where('id', $staffId)->where('institution_id', $institutionId)->firstOrFail();

        $request->validate([
            'base_salary' => 'required|numeric|min:0',
            'payment_basis' => 'required|in:monthly,hourly',
            'allowance_keys.*' => 'nullable|string',
            'allowance_values.*' => 'nullable|numeric|min:0',
            'deduction_keys.*' => 'nullable|string',
            'deduction_values.*' => 'nullable|numeric|min:0',
        ]);

        $allowances = [];
        if ($request->has('allowance_keys')) {
            foreach ($request->allowance_keys as $index => $key) {
                if ($key && isset($request->allowance_values[$index])) {
                    // Standardize storage format: [['name' => 'Transport', 'amount' => 50]]
                    $allowances[] = [
                        'name' => $key,
                        'amount' => $request->allowance_values[$index]
                    ];
                }
            }
        }

        $deductions = [];
        if ($request->has('deduction_keys')) {
            foreach ($request->deduction_keys as $index => $key) {
                if ($key && isset($request->deduction_values[$index])) {
                    $deductions[] = [
                        'name' => $key,
                        'amount' => $request->deduction_values[$index]
                    ];
                }
            }
        }

        SalaryStructure::updateOrCreate(
            ['staff_id' => $staff->id],
            [
                'institution_id' => $institutionId,
                'base_salary' => $request->base_salary,
                'payment_basis' => $request->payment_basis,
                'allowances' => $allowances,
                'deductions' => $deductions,
            ]
        );

        return redirect()->route('salary-structures.index')->with('success', __('payroll.success_updated'));
    }
}