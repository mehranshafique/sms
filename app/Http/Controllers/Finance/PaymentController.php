<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\BaseController;
use App\Models\Invoice;
use App\Services\NotificationService;
use App\Services\PaymentMethodService;
use App\Services\PaymentRecordingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Middleware\PermissionMiddleware;

class PaymentController extends BaseController
{
    public function __construct(
        protected NotificationService $notificationService,
        protected PaymentMethodService $paymentMethodService,
        protected PaymentRecordingService $paymentRecordingService
    ) {
        $this->middleware(PermissionMiddleware::class . ':payment.create')->only(['create', 'store']);
        $this->setPageTitle(__('payment.page_title'));
    }

    public function create(Request $request)
    {
        $invoice = null;
        $methods = [];

        if ($request->has('invoice_id')) {
            $invoice = Invoice::with(['student', 'items.feeStructure'])->findOrFail($request->invoice_id);

            $institutionId = $this->getInstitutionId();
            if ($institutionId && $invoice->institution_id != $institutionId) {
                abort(403);
            }

            $methods = $this->paymentMethodService->getEnabledMethods($invoice->institution_id);
        }

        return view('finance.payments.create', compact('invoice', 'methods'));
    }

    public function store(Request $request)
    {
        $invoice = Invoice::with(['items.feeStructure', 'student'])->findOrFail($request->invoice_id);
        $institutionId = $this->getInstitutionId();

        if ($institutionId && $invoice->institution_id != $institutionId) {
            abort(403);
        }

        $enabledKeys = $this->paymentMethodService->enabledMethodKeys($invoice->institution_id);

        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'method' => ['required', 'string', Rule::in($enabledKeys)],
            'mobile_reference' => 'nullable|string|max:80',
            'notes' => 'nullable|string',
            'password' => 'required|string',
        ]);

        if (!Hash::check($request->password, Auth::user()->password)) {
            return response()->json([
                'message' => __('auth.password') ?? 'Incorrect password. Please try again.',
            ], 422);
        }

        try {
            $payment = $this->paymentRecordingService->record($invoice, [
                'amount' => $request->amount,
                'method' => $request->method,
                'payment_date' => $request->payment_date,
                'mobile_reference' => $request->mobile_reference,
                'notes' => $request->notes,
                'source' => 'admin',
            ], Auth::id());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }

        $this->notificationService->sendPaymentNotification($payment);
        app(\App\Services\InAppNotificationService::class)->notifyPaymentReceived($payment);

        return response()->json([
            'message' => "Payment successfully completed for {$invoice->student->full_name}. Thank you.",
            'redirect' => route('invoices.show', $invoice->id),
        ]);
    }
}
