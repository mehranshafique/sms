<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\BaseController;
use App\Models\Student;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\AcademicSession;
use Illuminate\Http\Request;

class StudentFinanceController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->setPageTitle(__('finance.student_finance_dashboard'));
    }

    public function index(Student $student)
    {
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $student->institution_id != $institutionId) abort(403);

        $student->load([
            'enrollments' => function($q) {
                $q->latest(); 
            },
            'enrollments.classSection.gradeLevel', 
            'invoices.payments', 
            'invoices.items.feeStructure'
        ]);

        $session = AcademicSession::where('institution_id', $student->institution_id)
            ->where('is_current', true)
            ->first();

        // --- 1. Calculate Gross Annual Fee (Before Discount) ---
        $grossAnnualFee = 0;
        $enrollment = $student->enrollments->first(); // Latest enrollment

        if ($enrollment && $session) {
            // Find Grade ID via Enrollment OR Class Section
            $gradeId = $enrollment->grade_level_id ?? ($enrollment->classSection->grade_level_id ?? null);

            if ($gradeId) {
                $grossAnnualFee = FeeStructure::where('institution_id', $student->institution_id)
                    ->where('academic_session_id', $session->id)
                    ->where('payment_mode', 'global')
                    ->where(function($q) use ($enrollment, $gradeId) {
                        if ($enrollment->class_section_id) {
                            $q->where('class_section_id', $enrollment->class_section_id);
                        }
                        $q->orWhere('grade_level_id', $gradeId);
                    })
                    ->sum('amount');
            }
        }

        // --- 2. Calculate Discount ---
        $discountAmount = 0;
        $discountLabel = '';
        
        if ($enrollment && $enrollment->discount_amount > 0 && $grossAnnualFee > 0) {
            if ($enrollment->discount_type === 'percentage') {
                $discountAmount = ($grossAnnualFee * $enrollment->discount_amount) / 100;
                $discountLabel = number_format($enrollment->discount_amount) . '%';
            } else {
                $discountAmount = $enrollment->discount_amount;
                $discountLabel = __('finance.fixed') ?? 'Fixed';
            }
        }

        // --- 3. Net Annual Fee ---
        $netAnnualFee = max(0, $grossAnnualFee - $discountAmount);

        // --- 4. Installments & Payments ---
        $invoicesQuery = Invoice::where('student_id', $student->id)
            ->with(['items.feeStructure', 'payments']);
            
        if($session) {
            $invoicesQuery->where('academic_session_id', $session->id);
        }
        
        $invoices = $invoicesQuery->orderBy('due_date')->get();

        $totalPaidGlobal = Payment::whereHas('invoice', function($q) use ($student, $session) {
            $q->where('student_id', $student->id);
            if($session) $q->where('academic_session_id', $session->id);
        })->sum('amount');

        // Effective Total: If invoices created > Net Fee (e.g. extras), use Invoiced. Else use Net Fee.
        $totalInvoiced = $invoices->sum('total_amount');
        $effectiveTotal = max($netAnnualFee, $totalInvoiced); 

        $totalDueGlobal = max(0, $effectiveTotal - $totalPaidGlobal);

        // --- 5. Prepare Tabs ---
        $tabs = [];
        $previousCleared = true;

        foreach ($invoices as $index => $invoice) {
            $paid = $invoice->payments->sum('amount');
            $due = $invoice->total_amount - $paid;
            
            $status = 'Pending';
            if ($due <= 0.01) {
                $status = 'Paid';
            } elseif ($paid > 0) {
                $status = 'Partial';
            }
            
            $canPay = $previousCleared; 
            
            $order = 999;
            foreach($invoice->items as $item) {
                if($item->feeStructure && $item->feeStructure->installment_order) {
                    $order = $item->feeStructure->installment_order;
                    break;
                }
            }

            if ($due > 0.01) $previousCleared = false;

            $label = $invoice->items->first()->description ?? __('finance.installment_prefix') . ' ' . ($index + 1);
            if ($invoice->items->count() > 1) {
                $label .= ' + ' . ($invoice->items->count() - 1) . ' others';
            }

            $tabs[] = [
                'id' => $invoice->id,
                'label' => $label, 
                'amount' => $invoice->total_amount,
                'paid' => $paid,
                'remaining' => $due,
                'status' => $status,
                'is_locked' => !$canPay,
                'invoice' => $invoice,
                'order' => $order
            ];
        }

        // Sort tabs logic
        usort($tabs, function($a, $b) {
            if ($a['order'] == 999 && $b['order'] == 999) return $a['id'] <=> $b['id'];
            return $a['order'] <=> $b['order'];
        });

        // Use 'displayAnnualFee' for compatibility with view if it uses it, 
        // but prefer specific variables passed below.
        $displayAnnualFee = $netAnnualFee;

        return view('finance.student_dashboard', compact(
            'student', 
            'grossAnnualFee',
            'netAnnualFee',
            'discountAmount',
            'discountLabel',
            'totalPaidGlobal', 
            'totalDueGlobal', 
            'tabs',
            'displayAnnualFee'
        ));
    }
}