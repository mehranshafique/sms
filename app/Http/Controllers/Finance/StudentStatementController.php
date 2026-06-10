<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\BaseController;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\InvoiceItem;
use App\Enums\CurrencySymbol;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use PDF;

class StudentStatementController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->setPageTitle(__('finance.student_statement'));
    }

    /**
     * Authorize access to a student's financial statement.
     */
    private function authorizeStudentStatement(Student $student): void
    {
        $user = Auth::user();
        $institutionId = $this->getInstitutionId();

        if ($user->hasRole('Student')) {
            if ($user->id !== $student->user_id) {
                abort(403);
            }
            return;
        }

        if ($user->hasRole('Guardian')) {
            if (!$student->parent || $student->parent->user_id !== $user->id) {
                abort(403);
            }
            return;
        }

        if ($institutionId && (int) $student->institution_id !== (int) $institutionId) {
            abort(403);
        }

        if (!$user->can('invoice.view') && !$user->hasRole(['Super Admin', 'School Admin', 'Head Officer', 'Accountant', 'accountant'])) {
            abort(403);
        }
    }

    /**
     * Display the financial statement for a specific student.
     * Accessible by Admin (for any student) and Student/Parent (for themselves).
     */
    public function show(Request $request, $studentId)
    {
        $student = Student::with(['gradeLevel', 'classSection'])->findOrFail($studentId);

        $this->authorizeStudentStatement($student);

        if ($request->ajax()) {
            // Fetch Payments (Credits) and Invoices (Debits)
            // We'll treat this as a ledger: Invoices increase debt, Payments decrease it.
            
            $invoices = Invoice::where('student_id', $studentId)
                ->with(['items', 'academicSession'])
                ->get()
                ->map(function($inv) {
                    return [
                        'date' => $inv->issue_date,
                        'type' => 'invoice',
                        'ref' => $inv->invoice_number,
                        'description' => $inv->items->pluck('description')->join(', '),
                        'debit' => $inv->total_amount,
                        'credit' => 0,
                        'session' => $inv->academicSession->name ?? '-',
                        'status' => $inv->status
                    ];
                });

            $payments = Payment::whereHas('invoice', fn($q) => $q->where('student_id', $studentId))
                ->with(['invoice'])
                ->get()
                ->map(function($pay) {
                    return [
                        'date' => $pay->payment_date,
                        'type' => 'payment',
                        'ref' => $pay->transaction_id,
                        'description' => 'Payment for ' . ($pay->invoice->invoice_number ?? 'Invoice'),
                        'debit' => 0,
                        'credit' => $pay->amount,
                        'session' => '-', // Could fetch via invoice relation if needed
                        'status' => 'completed'
                    ];
                });

            // Merge and Sort by Date
            $transactions = $invoices->concat($payments)->sortByDesc('date')->values();

            return DataTables::of($transactions)
                ->addIndexColumn()
                ->editColumn('date', fn($row) => $row['date']->format('d M, Y'))
                ->editColumn('type', fn($row) => ucfirst($row['type']))
                ->editColumn('debit', fn($row) => $row['debit'] > 0 ? CurrencySymbol::default() . ' ' . number_format($row['debit'], 2) : '-')
                ->editColumn('credit', fn($row) => $row['credit'] > 0 ? '<span class="text-success">' . CurrencySymbol::default() . ' ' . number_format($row['credit'], 2) . '</span>' : '-')
                ->addColumn('balance', function($row) {
                    // Running balance is tricky in server-side datatables without full fetch. 
                    // For now, we leave it or calculate on frontend if pagination is small.
                    return '-'; 
                })
                ->rawColumns(['credit'])
                ->make(true);
        }

        // Calculate Summary Stats
        $totalInvoiced = Invoice::where('student_id', $studentId)->sum('total_amount');
        $totalPaid = Payment::whereHas('invoice', fn($q) => $q->where('student_id', $studentId))->sum('amount');
        $balance = $totalInvoiced - $totalPaid;

        return view('finance.statements.show', compact('student', 'totalInvoiced', 'totalPaid', 'balance'));
    }

    /**
     * Download Statement PDF
     */
    public function downloadPdf($studentId)
    {
        $student = Student::findOrFail($studentId);
        $this->authorizeStudentStatement($student);
        $invoices = Invoice::where('student_id', $studentId)->get();
        $payments = Payment::whereHas('invoice', fn($q) => $q->where('student_id', $studentId))->get();
        
        // Logic to build chronological ledger
        $ledger = [];
        foreach($invoices as $inv) {
            $ledger[] = [
                'date' => $inv->issue_date,
                'desc' => "Invoice #{$inv->invoice_number}",
                'amount' => -$inv->total_amount // Debit
            ];
        }
        foreach($payments as $pay) {
            $ledger[] = [
                'date' => $pay->payment_date,
                'desc' => "Payment #{$pay->transaction_id}",
                'amount' => $pay->amount // Credit
            ];
        }
        
        // Sort
        usort($ledger, fn($a, $b) => $a['date'] <=> $b['date']);

        $pdf = \PDF::loadView('finance.statements.pdf', compact('student', 'ledger'));
        return $pdf->download('Statement_'.$student->admission_number.'.pdf');
    }
}