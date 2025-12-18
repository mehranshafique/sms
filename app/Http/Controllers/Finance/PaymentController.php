<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\BaseController;
use App\Models\Payment;
use App\Models\Invoice;
use App\Mail\PaymentReceived; // Added
use Illuminate\Support\Facades\Mail; // Added
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Middleware\PermissionMiddleware;

class PaymentController extends BaseController
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':payment.create')->only(['create', 'store']);
        $this->setPageTitle('Payments');
    }

    public function create(Request $request)
    {
        $invoice = null;
        if($request->has('invoice_id')){
            $invoice = Invoice::with(['student', 'items'])->findOrFail($request->invoice_id);
        }
        return view('finance.payments.create', compact('invoice'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'method' => 'required|in:cash,bank_transfer,card,online',
        ]);

        $invoice = Invoice::findOrFail($request->invoice_id);
        
        $remaining = $invoice->total_amount - $invoice->paid_amount;
        
        if($request->amount > $remaining){
            return response()->json(['message' => __('payment.exceeds_balance') . ' (' . number_format($remaining, 2) . ')'], 422);
        }

        $institutionId = Auth::user()->institute_id ?? $invoice->institution_id;

        $payment = null;

        DB::transaction(function () use ($request, $invoice, $institutionId, &$payment) {
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'institution_id' => $institutionId,
                'payment_date' => $request->payment_date,
                'amount' => $request->amount,
                'method' => $request->method,
                'notes' => $request->notes,
                'received_by' => Auth::id(),
                'transaction_id' => 'TRX-' . time() . rand(100,999),
            ]);

            $newPaid = $invoice->paid_amount + $request->amount;
            $total = (float)$invoice->total_amount;
            $paid = (float)$newPaid;
            $status = ($paid >= $total) ? 'paid' : 'partial';

            $invoice->update([
                'paid_amount' => $newPaid,
                'status' => $status
            ]);
        });

        // Send Email (Queue it to prevent lag)
        if ($payment && $invoice->student->email) {
            // Ensure Mail configuration exists in .env
            try {
                Mail::to($invoice->student->email)->queue(new PaymentReceived($payment));
            } catch (\Exception $e) {
                // Log error but don't fail the transaction
                \Log::error('Payment email failed: ' . $e->getMessage());
            }
        }

        return response()->json(['message' => __('payment.success_recorded'), 'redirect' => route('invoices.index')]);
    }
}