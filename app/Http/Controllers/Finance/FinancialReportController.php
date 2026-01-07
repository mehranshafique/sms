<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\BaseController;
use App\Models\ClassSection;
use App\Models\StudentEnrollment;
use App\Models\AcademicSession;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\StudentDebt;
use App\Models\FeeStructure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Student;

class FinancialReportController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->setPageTitle(__('finance.class_financial_report'));
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        
        // Load Classes for Filter
        $classes = ClassSection::where('institution_id', $institutionId)
            ->with('gradeLevel')
            ->get()
            ->mapWithKeys(function ($item) {
                // Fixed: Format as "Grade Section" (e.g. "1er A")
                $gradeName = $item->gradeLevel->name ?? '';
                $name = ($gradeName ? $gradeName . ' ' : '') . $item->name;
                
                return [$item->id => $name];
            });

        $reportData = [];
        $totals = [
            'today_payment' => 0,
            'cumulative_paid' => 0,
            'remaining' => 0,
            'annual_fee' => 0,
            'previous_debt' => 0,
        ];

        if ($request->filled('class_section_id')) {
            $currentSession = AcademicSession::where('institution_id', $institutionId)
                ->where('is_current', true)
                ->firstOrFail();

            // 1. Get Students in Class
            // FIX: Removed 'student.parent' as parent details are on the student table directly
            $students = StudentEnrollment::where('class_section_id', $request->class_section_id)
                ->where('academic_session_id', $currentSession->id)
                ->where('status', 'active')
                ->with(['student']) 
                ->get();

            // 2. Get Fee Structures for this Class
            $class = ClassSection::find($request->class_section_id);
            $gradeId = $class->grade_level_id;
            
            $gradeFees = FeeStructure::where('institution_id', $institutionId)
                ->where('academic_session_id', $currentSession->id)
                ->where('grade_level_id', $gradeId)
                ->sum('amount');

            // 3. Process Each Student
            foreach ($students as $enrollment) {
                $student = $enrollment->student;
                if (!$student) continue;

                $studentId = $student->id;

                // A. Today's Payment
                // FIX: Changed 'paid_at' to 'created_at' as 'paid_at' does not exist in payments table
                $todayPaid = Payment::whereHas('invoice', function ($q) use ($studentId) {
                        $q->where('student_id', $studentId);
                    })
                    ->whereDate('created_at', now()) 
                    ->sum('amount');

                // B. Cumulative Paid (Current Session)
                $cumulativePaid = Payment::whereHas('invoice', function ($q) use ($studentId, $currentSession) {
                        $q->where('student_id', $studentId)
                          ->where('academic_session_id', $currentSession->id);
                    })
                    ->sum('amount');

                // C. Annual Fee Overview
                $annualFee = $gradeFees;

                // D. Remaining Fees
                $remaining = $annualFee - $cumulativePaid;
                if ($remaining < 0) $remaining = 0; 

                // E. Previous Debt (Ensure StudentDebt model exists)
                $prevDebt = 0;
                if (class_exists(StudentDebt::class)) {
                    $prevDebt = StudentDebt::where('student_id', $studentId)
                        ->where('status', '!=', 'paid')
                        ->sum('amount');
                }

                // F. Parent Info (Direct access)
                $parentName = $student->father_name ?? $student->mother_name ?? 'N/A';
                $parentPhone = $student->father_phone ?? $student->mother_phone ?? 'N/A';

                $reportData[] = [
                    'student_id' => $student->admission_number,
                    'name' => $student->full_name,
                    'parent_name' => $parentName,
                    'parent_phone' => $parentPhone,
                    'today_payment' => $todayPaid,
                    'cumulative_paid' => $cumulativePaid,
                    'remaining' => $remaining,
                    'annual_fee' => $annualFee,
                    'previous_debt' => $prevDebt,
                    'payment_mode' => $student->payment_mode ?? 'installment'
                ];

                // Accumulate Totals
                $totals['today_payment'] += $todayPaid;
                $totals['cumulative_paid'] += $cumulativePaid;
                $totals['remaining'] += $remaining;
                $totals['annual_fee'] += $annualFee;
                $totals['previous_debt'] += $prevDebt;
            }
        }

        return view('finance.reports.class_summary', compact('classes', 'reportData', 'totals'));
    }
}