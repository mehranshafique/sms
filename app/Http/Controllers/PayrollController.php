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
        // FIX: Secure the controller
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
        // FIX: Explicitly check for create/generate permission
        $this->authorize('create', Payroll::class);

        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:'.(date('Y')+1),
        ]);

        $institutionId = $this->getInstitutionId();
        $startDate = Carbon::createFromDate($request->year, $request->month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        $daysInMonth = $startDate->daysInMonth; // Or working days logic

        // Fetch Eligible Staff with Salary Structure
        $staffMembers = Staff::with('salaryStructure')
            ->where('institution_id', $institutionId)
            ->where('status', 'active')
            ->whereHas('salaryStructure')
            ->get();

        $generatedCount = 0;

        foreach ($staffMembers as $staff) {
            $structure = $staff->salaryStructure;
            if (!$structure) continue;

            // 1. Calculate Attendance Stats
            $attendance = StaffAttendance::where('staff_id', $staff->id)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->get();

            $present = $attendance->whereIn('status', ['present', 'late', 'half_day'])->count();
            // Simplified logic (ignoring weekends/holidays for now)
            $explicitAbsent = $attendance->where('status', 'absent')->count();

            // 2. Calculate Pay
            $basePay = $structure->base_salary;
            $allowances = array_sum($structure->allowances ?? []);
            $deductions = array_sum($structure->deductions ?? []);

            // LOP Calculation (Example: Base / 30 * absent days)
            $perDayPay = $basePay / 30; // Standard 30 days
            $lopDeduction = $perDayPay * $explicitAbsent;

            $totalDeduction = $deductions + $lopDeduction;
            $netSalary = ($basePay + $allowances) - $totalDeduction;

            // 3. Create/Update Payroll Record
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
        
        // Eager load necessary relationships
        $payroll->load(['staff.user', 'staff.salaryStructure', 'staff.institution']);

        $format = $request->query('format', 'a4'); 
        $view = 'payroll.payslip';
        $paper = 'a4'; 
        
        // Define Custom Paper Sizes for Thermal Printers (in points)
        // 1mm = 2.83465 points
        if ($format === 'pos80') {
            $view = 'payroll.payslip_receipt';
            // 80mm width = ~226pt. Height set to 800pt (auto-cut usually handles length)
            $paper = [0, 0, 226.77, 800]; 
        } elseif ($format === 'pos58') {
            $view = 'payroll.payslip_receipt';
            // 58mm width = ~164pt.
            $paper = [0, 0, 164.41, 800];
        }

        $pdf = PDF::loadView($view, compact('payroll', 'format'));
        
        if (is_array($paper)) {
            $pdf->setPaper($paper);
        } else {
            $pdf->setPaper($paper, 'portrait');
        }

        // Use DomPDF options to ensure images/fonts load
        $pdf->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);
        
        $filename = 'Payslip_'.$payroll->staff->id.'_'.$format.'.pdf';
        
        return $pdf->stream($filename);
    }
}