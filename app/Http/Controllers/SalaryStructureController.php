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
            // Fetch Staff with their Salary Structure
            $data = Staff::with(['salaryStructure', 'user'])
                ->where('institution_id', $institutionId)
                ->where('status', 'active')
                ->select('staff.*'); // Select * to avoid column collisions

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('name', function($row){
                    return $row->full_name ?? $row->user->name ?? 'N/A';
                })
                ->addColumn('base_salary', function($row){
                    return $row->salaryStructure 
                        ? number_format($row->salaryStructure->base_salary, 2) 
                        : '<span class="badge badge-warning">Not Set</span>';
                })
                ->addColumn('allowances', function($row){
                    if (!$row->salaryStructure || empty($row->salaryStructure->allowances)) return '-';
                    return count($row->salaryStructure->allowances) . ' items';
                })
                ->addColumn('net_estimate', function($row){
                    if (!$row->salaryStructure) return '-';
                    $s = $row->salaryStructure;
                    $totalA = array_sum($s->allowances ?? []);
                    $totalD = array_sum($s->deductions ?? []);
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

        // Process Dynamic Arrays into Key-Value Pairs
        $allowances = [];
        if ($request->has('allowance_keys')) {
            foreach ($request->allowance_keys as $index => $key) {
                if ($key && isset($request->allowance_values[$index])) {
                    $allowances[$key] = $request->allowance_values[$index];
                }
            }
        }

        $deductions = [];
        if ($request->has('deduction_keys')) {
            foreach ($request->deduction_keys as $index => $key) {
                if ($key && isset($request->deduction_values[$index])) {
                    $deductions[$key] = $request->deduction_values[$index];
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