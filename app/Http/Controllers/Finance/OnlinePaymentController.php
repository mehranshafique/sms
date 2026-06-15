<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PaymentGatewayTransaction;
use App\Services\CurrencyService;
use App\Services\PaymentGateways\PaymentGatewayCompletionService;
use App\Services\PaymentGateways\PaymentGatewayConfigService;
use App\Services\PaymentGateways\PaymentGatewayManager;
use App\Services\PaymentMethodService;
use App\Services\PaymentProofService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OnlinePaymentController extends Controller
{
    public function __construct(
        protected PaymentMethodService $paymentMethodService,
        protected PaymentGatewayConfigService $gatewayConfigService,
        protected PaymentGatewayManager $gatewayManager,
        protected PaymentGatewayCompletionService $gatewayCompletionService,
        protected PaymentProofService $paymentProofService,
        protected CurrencyService $currencyService
    ) {}

    public function lookup()
    {
        return view('finance.payments.online_lookup');
    }

    public function find(Request $request)
    {
        $request->validate([
            'invoice_number' => 'required|string|max:80',
            'admission_number' => 'required|string|max:50',
        ]);

        $invoice = Invoice::with(['student', 'institution'])
            ->where('invoice_number', trim($request->invoice_number))
            ->whereHas('student', fn ($q) => $q->where('admission_number', trim($request->admission_number)))
            ->first();

        if (!$invoice) {
            return back()->withInput()->with('error', __('online_pay.invoice_not_found'));
        }

        if (!$this->paymentMethodService->isOnlineEnabled($invoice->institution_id)) {
            return back()->with('error', __('online_pay.online_disabled'));
        }

        if (!$invoice->payment_token) {
            $invoice->update(['payment_token' => Str::random(48)]);
        }

        return redirect()->route('pay.show', $invoice->payment_token);
    }

    public function show(string $token)
    {
        $invoice = $this->resolveInvoice($token);
        $methods = $this->paymentMethodService->getEnabledMethods($invoice->institution_id);
        $currencySettings = $this->currencyService->getSettings($invoice->institution_id);
        $currency = $currencySettings['symbol'];
        $currencyCode = $currencySettings['code'];
        $gatewayActive = $this->gatewayConfigService->isGatewayActive($invoice->institution_id);
        $gatewayProvider = $this->gatewayConfigService->activeProvider($invoice->institution_id);
        $gatewayEnvironment = $this->gatewayConfigService->environment($invoice->institution_id);
        $manualProofEnabled = $this->gatewayConfigService->isManualProofEnabled($invoice->institution_id);

        $gatewayMethods = [];
        if ($gatewayActive) {
            foreach ($methods as $key => $method) {
                if ($this->gatewayManager->supportsMethod($invoice->institution_id, $key)) {
                    $gatewayMethods[$key] = $method;
                }
            }
        }

        return view('finance.payments.online_pay', compact(
            'invoice', 'methods', 'currency', 'currencyCode',
            'gatewayActive', 'gatewayProvider', 'gatewayMethods', 'manualProofEnabled', 'gatewayEnvironment'
        ));
    }

    public function initiateGateway(Request $request, string $token)
    {
        $invoice = $this->resolveInvoice($token);

        if (!$this->gatewayConfigService->isGatewayActive($invoice->institution_id)) {
            return back()->with('error', __('payment_gateway.not_configured'));
        }

        $enabledKeys = array_keys($this->paymentMethodService->getEnabledMethods($invoice->institution_id));
        $provider = $this->gatewayConfigService->activeProvider($invoice->institution_id);

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => ['required', 'string', Rule::in($enabledKeys)],
            'payer_name' => 'required|string|max:100',
            'payer_phone' => 'required|string|max:30',
        ]);

        if (!$this->gatewayManager->supportsMethod($invoice->institution_id, $request->method)) {
            return back()->with('error', __('payment_gateway.method_not_supported'));
        }

        $currencySettings = $this->currencyService->getSettings($invoice->institution_id);
        $externalId = (string) Str::uuid();

        $transaction = PaymentGatewayTransaction::create([
            'institution_id' => $invoice->institution_id,
            'invoice_id' => $invoice->id,
            'gateway' => $provider,
            'external_id' => $externalId,
            'amount' => $request->amount,
            'currency' => $currencySettings['code'],
            'method' => $request->method,
            'payer_name' => $request->payer_name,
            'payer_phone' => $request->payer_phone,
            'status' => 'pending',
        ]);

        try {
            $driver = $this->gatewayManager->driver($invoice->institution_id, $provider);
            $result = $driver->initiate($invoice, $transaction, [
                'payer_name' => $request->payer_name,
                'payer_phone' => $request->payer_phone,
                'method' => $request->method,
                'amount' => (float) $request->amount,
                'currency' => $currencySettings['code'],
            ]);

            $transaction->update([
                'status' => $result['status'] ?? 'processing',
                'gateway_reference' => $result['gateway_reference'] ?? null,
                'checkout_url' => $result['checkout_url'] ?? null,
                'meta' => ['init' => $result['raw'] ?? []],
            ]);

            if (!empty($result['checkout_url'])) {
                return redirect()->away($result['checkout_url']);
            }

            return redirect()
                ->route('pay.gateway.status', ['token' => $token, 'reference' => $externalId])
                ->with('info', $result['message'] ?? __('payment_gateway.processing'));
        } catch (\Throwable $e) {
            $transaction->update(['status' => 'failed', 'meta' => ['error' => $e->getMessage()]]);

            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function gatewayStatus(string $token, string $reference)
    {
        $invoice = $this->resolveInvoice($token);
        $transaction = PaymentGatewayTransaction::where('external_id', $reference)
            ->where('invoice_id', $invoice->id)
            ->firstOrFail();

        $this->gatewayCompletionService->verifyAndComplete($transaction);
        $transaction->refresh();

        $gatewayEnvironment = $this->gatewayConfigService->environment($invoice->institution_id);
        $gatewayProvider = $transaction->gateway;

        return view('finance.payments.gateway_status', compact('invoice', 'transaction', 'token', 'gatewayEnvironment', 'gatewayProvider'));
    }

    public function submitProof(Request $request, string $token)
    {
        $invoice = $this->resolveInvoice($token);

        if (!$this->gatewayConfigService->isManualProofEnabled($invoice->institution_id)) {
            return back()->with('error', __('payment_proof.disabled'));
        }

        $enabledKeys = $this->paymentMethodService->enabledMethodKeys($invoice->institution_id);

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => ['required', 'string', Rule::in($enabledKeys)],
            'payer_name' => 'required|string|max:100',
            'payer_phone' => 'required|string|max:30',
            'paid_at' => 'required|date',
            'transaction_reference' => 'required|string|max:120',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->paymentProofService->submit($invoice, [
                'payer_name' => $request->payer_name,
                'payer_phone' => $request->payer_phone,
                'method' => $request->method,
                'amount' => $request->amount,
                'paid_at' => $request->paid_at,
                'transaction_reference' => $request->transaction_reference,
                'notes' => $request->notes,
            ], $request->file('receipt'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()
            ->route('pay.show', $token)
            ->with('success', __('payment_proof.submitted'));
    }

    private function resolveInvoice(string $token): Invoice
    {
        $invoice = Invoice::with(['student', 'institution', 'items', 'academicSession', 'payments'])
            ->where('payment_token', $token)
            ->firstOrFail();

        if (!$this->paymentMethodService->isOnlineEnabled($invoice->institution_id)) {
            abort(403, __('online_pay.online_disabled'));
        }

        return $invoice;
    }
}

