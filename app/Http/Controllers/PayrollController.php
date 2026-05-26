<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\Payroll;
use App\Models\SalaryStructure;
use App\Models\StaffAttendance;
use App\Models\Staff;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PDF;

class PayrollController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->authorizeResource(Payroll::class, 'payroll');
        $this->setPageTitle(__('payroll.page_title'));
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        
        $payrolls = Payroll::with(['staff.user'])
            ->where('institution_id', $institutionId)
            ->latest('month_year')
            ->paginate(15);

        return view('payroll.index', compact('payrolls'));
    }

    /**
     * Generate Payroll for a specific Month
     */
    public function generate(Request $request)
    {
        $this->authorize('create', Payroll::class);

        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:'.(date('Y')+1),
        ]);

        $institutionId = $this->getInstitutionId();
        $startDate = Carbon::createFromDate($request->year, $request->month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        $daysInMonth = $startDate->daysInMonth; 

        $staffMembers = Staff::with('salaryStructure')
            ->where('institution_id', $institutionId)
            ->where('status', 'active')
            ->whereHas('salaryStructure')
            ->get();

        $generatedCount = 0;

        foreach ($staffMembers as $staff) {
            $structure = $staff->salaryStructure;
            if (!$structure) continue;

            $attendance = StaffAttendance::where('staff_id', $staff->id)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->get();

            $present = $attendance->whereIn('status', ['present', 'late', 'half_day'])->count();
            $explicitAbsent = $attendance->where('status', 'absent')->count();

            $basePay = $structure->base_salary;
            
            // FIX: Safely parse and sum Allowances (Bulletproof against JSON objects/arrays/strings)
            $allowancesData = $structure->allowances;
            if (is_string($allowancesData)) {
                $allowancesData = json_decode($allowancesData, true);
                if (is_string($allowancesData)) $allowancesData = json_decode($allowancesData, true); // Catch double-encoding
            }
            $allowances = 0;
            if (is_iterable($allowancesData)) {
                foreach ($allowancesData as $item) {
                    if (is_array($item)) $allowances += (float)($item['amount'] ?? 0);
                    elseif (is_object($item)) $allowances += (float)($item->amount ?? 0);
                    elseif (is_numeric($item)) $allowances += (float)$item;
                }
            }

            // FIX: Safely parse and sum Deductions (Bulletproof)
            $deductionsData = $structure->deductions;
            if (is_string($deductionsData)) {
                $deductionsData = json_decode($deductionsData, true);
                if (is_string($deductionsData)) $deductionsData = json_decode($deductionsData, true);
            }
            $deductions = 0;
            if (is_iterable($deductionsData)) {
                foreach ($deductionsData as $item) {
                    if (is_array($item)) $deductions += (float)($item['amount'] ?? 0);
                    elseif (is_object($item)) $deductions += (float)($item->amount ?? 0);
                    elseif (is_numeric($item)) $deductions += (float)$item;
                }
            }

            // LOP Calculation
            $perDayPay = $basePay / 30; 
            $lopDeduction = $perDayPay * $explicitAbsent;

            $totalDeduction = $deductions + $lopDeduction;
            $netSalary = ($basePay + $allowances) - $totalDeduction;

            // 3. Create/Update Payroll Record with Corrected Math!
            Payroll::updateOrCreate(
                [
                    'institution_id' => $institutionId,
                    'staff_id' => $staff->id,
                    'month_year' => $startDate->format('Y-m-d'),
                ],
                [
                    'total_days' => $daysInMonth,
                    'present_days' => $present,
                    'absent_days' => $explicitAbsent,
                    'basic_pay' => $basePay,
                    'total_allowance' => $allowances,
                    'total_deduction' => $totalDeduction,
                    'net_salary' => max(0, $netSalary),
                    'status' => 'generated'
                ]
            );
            $generatedCount++;
        }

        return redirect()->route('payroll.index')
            ->with('success', __('payroll.success_generated', ['count' => $generatedCount]));
    }

    public function payslip(Request $request, Payroll $payroll)
    {
        $this->authorize('view', $payroll); 
        
        $payroll->load(['staff.user', 'staff.salaryStructure', 'staff.institution']);

        $format = $request->query('format', 'a4'); 
        $view = 'payroll.payslip';
        $paper = 'a4'; 
        
        if ($format === 'pos80') {
            $view = 'payroll.payslip_receipt';
            $paper = [0, 0, 226.77, 800]; 
        } elseif ($format === 'pos58') {
            $view = 'payroll.payslip_receipt';
            $paper = [0, 0, 164.41, 800];
        }

        $pdf = PDF::loadView($view, compact('payroll', 'format'));
        
        if (is_array($paper)) {
            $pdf->setPaper($paper);
        } else {
            $pdf->setPaper($paper, 'portrait');
        }

        $pdf->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);
        
        $filename = 'Payslip_'.$payroll->staff->id.'_'.$format.'.pdf';
        
        return $pdf->stream($filename);
    }
}