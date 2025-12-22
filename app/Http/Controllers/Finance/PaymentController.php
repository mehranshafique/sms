<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\BaseController;
use App\Models\Payment;
use App\Models\Invoice;
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
            $invoice = Invoice::with(['student', 'items'])->findOrFail($request->invoice_id);
            
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

        $invoice = Invoice::findOrFail($request->invoice_id);
        
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $invoice->institution_id != $institutionId) {
            abort(403);
        }

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