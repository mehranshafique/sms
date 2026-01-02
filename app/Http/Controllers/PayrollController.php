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
        $this->setPageTitle('Payroll Management');
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        
        $payrolls = Payroll::with('staff')
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
            $absent = $daysInMonth - $present; // Simplified logic (ignoring weekends/holidays for now)
            
            // Refined Logic: Absent is only marked days? Or total days minus present? 
            // Usually, payroll assumes paid unless marked absent or LOP (Loss of Pay).
            // For this basic version, we'll calculate deduction based on strict 'absent' marks if any.
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
            ->with('success', "Payroll generated for {$generatedCount} staff members.");
    }

    public function payslip(Payroll $payroll)
    {
        $this->authorize('view', $payroll); // Ensure policy exists or check ID
        
        $pdf = PDF::loadView('payroll.payslip', compact('payroll'));
        return $pdf->stream('Payslip_'.$payroll->staff->id.'.pdf');
    }
}