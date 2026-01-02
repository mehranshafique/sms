<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\BaseController;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\FeeStructure; // Added
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        ]);

        $invoice = Invoice::with(['items.feeStructure', 'student'])->findOrFail($request->invoice_id);
        
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $invoice->institution_id != $institutionId) {
            abort(403);
        }

        // --- INSTALLMENT LOGIC ENFORCEMENT ---
        // Check if this invoice is part of an installment plan (linked via FeeStructure)
        // We assume an invoice contains items from similar installment orders or mixed.
        // Logic: Find the Lowest Installment Order in this invoice. Check if any *lower* order exists unpaid.
        
        $studentId = $invoice->student_id;
        $academicSessionId = $invoice->academic_session_id;

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
        // -------------------------------------

        $remaining = $invoice->total_amount - $invoice->paid_amount;
        
        if($request->amount > $remaining){
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
            // Send Notification (SMS/Email) via Service
            $this->notificationService->sendPaymentNotification($payment);
        }

        return response()->json([
            'message' => __('payment.success_recorded'), 
            'redirect' => route('invoices.show', $invoice->id)
        ]);
    }
}