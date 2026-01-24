<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\BaseController;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\FeeStructure;
use App\Models\StudentEnrollment;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash; // Added for password check
use Illuminate\Support\Str;
use Spatie\Permission\Middleware\PermissionMiddleware;

class PaymentController extends BaseController
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->middleware(PermissionMiddleware::class . ':payment.create')->only(['create', 'store']);
        $this->setPageTitle(__('payment.page_title'));
        $this->notificationService = $notificationService;
    }

    public function create(Request $request)
    {
        $invoice = null;
        if($request->has('invoice_id')){
            $invoice = Invoice::with(['student', 'items.feeStructure'])->findOrFail($request->invoice_id);
            
            // Context Check
            $institutionId = $this->getInstitutionId();
            if ($institutionId && $invoice->institution_id != $institutionId) {
                abort(403);
            }
        }
        return view('finance.payments.create', compact('invoice'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'method' => 'required|in:cash,bank_transfer,card,online',
            'notes' => 'nullable|string',
            'password' => 'required|string', // Added Password Validation
        ]);

        // 1. Security Check: Validate Password
        if (!Hash::check($request->password, Auth::user()->password)) {
            return response()->json([
                'message' => __('auth.password') ?? 'Incorrect password. Please try again.'
            ], 422);
        }

        $invoice = Invoice::with(['items.feeStructure', 'student'])->findOrFail($request->invoice_id);
        
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $invoice->institution_id != $institutionId) {
            abort(403);
        }

        $studentId = $invoice->student_id;
        $academicSessionId = $invoice->academic_session_id;

        // --- 2. GLOBAL FEE CAP LOGIC ---
        $enrollment = StudentEnrollment::where('student_id', $studentId)
            ->where('academic_session_id', $academicSessionId)
            ->first();

        if ($enrollment) {
            // Find "Global" Fee Structure Amount for this Class/Grade
            $globalFeeStructure = FeeStructure::where('institution_id', $invoice->institution_id)
                ->where('academic_session_id', $academicSessionId)
                ->where('payment_mode', 'global')
                ->where(function($q) use ($enrollment) {
                    $q->where('class_section_id', $enrollment->class_section_id)
                      ->orWhere('grade_level_id', $enrollment->grade_level_id);
                })
                ->first();

            if ($globalFeeStructure) {
                $annualLimit = $globalFeeStructure->amount;

                // Calculate Total Paid So Far (Sum of ALL payments for this student in this session)
                $totalPaidSoFar = Payment::whereHas('invoice', function($q) use ($studentId, $academicSessionId) {
                    $q->where('student_id', $studentId)
                      ->where('academic_session_id', $academicSessionId);
                })->sum('amount');

                // Check Limit
                $newTotal = $totalPaidSoFar + $request->amount;
                
                // Allow a tiny margin for float precision errors (0.01)
                if ($newTotal > ($annualLimit + 0.01)) {
                    $remainingGlobal = max(0, $annualLimit - $totalPaidSoFar);
                    return response()->json([
                        'message' => "Payment rejected. Total annual fee is " . number_format($annualLimit, 2) . ". You can only pay up to " . number_format($remainingGlobal, 2) . " more for this academic year."
                    ], 422);
                }
            }
        }

        // --- 3. INSTALLMENT LOGIC ENFORCEMENT ---
        foreach ($invoice->items as $item) {
            if ($item->feeStructure && $item->feeStructure->payment_mode === 'installment') {
                $currentOrder = $item->feeStructure->installment_order;

                if ($currentOrder > 1) {
                    // Check for previous unpaid installments
                    $previousPending = Invoice::where('student_id', $studentId)
                        ->where('academic_session_id', $academicSessionId)
                        ->where('status', '!=', 'paid') // Unpaid or Partial
                        ->whereHas('items.feeStructure', function ($q) use ($currentOrder) {
                            $q->where('installment_order', '<', $currentOrder)
                              ->where('payment_mode', 'installment');
                        })
                        ->exists();

                    if ($previousPending) {
                        return response()->json([
                            'message' => __('payment.previous_installment_pending_error')
                        ], 422);
                    }
                }
            }
        }

        $remaining = $invoice->total_amount - $invoice->paid_amount;
        
        if($request->amount > ($remaining + 0.01)){ 
            return response()->json(['message' => __('payment.exceeds_balance') . ' (' . number_format($remaining, 2) . ')'], 422);
        }

        $targetInstitutionId = $institutionId ?? $invoice->institution_id;
        $payment = null;

        DB::transaction(function () use ($request, $invoice, $targetInstitutionId, &$payment) {
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'institution_id' => $targetInstitutionId,
                'payment_date' => $request->payment_date,
                'amount' => $request->amount,
                'method' => $request->method,
                'notes' => $request->notes,
                'received_by' => Auth::id(),
                'transaction_id' => 'TRX-' . strtoupper(Str::random(10)),
            ]);

            $newPaid = $invoice->paid_amount + $request->amount;
            $total = (float)$invoice->total_amount;
            $paid = (float)$newPaid;
            
            $status = ($paid >= $total - 0.01) ? 'paid' : 'partial';

            $invoice->update([
                'paid_amount' => $newPaid,
                'status' => $status
            ]);
        });

        if ($payment) {
            $this->notificationService->sendPaymentNotification($payment);
        }

        return response()->json([
            'message' => __('payment.success_recorded'), 
            'redirect' => route('invoices.show', $invoice->id)
        ]);
    }
}