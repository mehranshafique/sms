<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\PaymentGatewayTransaction;
use App\Services\PaymentGateways\PaymentGatewayCompletionService;
use App\Services\PaymentGateways\PaymentGatewayManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentGatewayWebhookController extends Controller
{
    public function __construct(
        protected PaymentGatewayManager $gatewayManager,
        protected PaymentGatewayCompletionService $completionService
    ) {}

    public function pawapay(Request $request)
    {
        return $this->handleWebhook('pawapay', $request->all());
    }

    public function cinetpay(Request $request)
    {
        return $this->handleWebhook('cinetpay', $request->all());
    }

    public function flutterwave(Request $request)
    {
        return $this->handleWebhook('flutterwave', $request->all());
    }

    public function returnUrl(Request $request, string $gateway, string $reference)
    {
        $transaction = PaymentGatewayTransaction::where('external_id', $reference)
            ->where('gateway', $gateway)
            ->firstOrFail();

        $this->completionService->verifyAndComplete($transaction);

        $transaction->refresh();
        $invoice = $transaction->invoice;

        if ($invoice?->payment_token) {
            $flash = $transaction->isCompleted()
                ? ['success' => __('payment_gateway.payment_success')]
                : ['warning' => __('payment_gateway.payment_pending')];

            return redirect()->route('pay.show', $invoice->payment_token)->with($flash);
        }

        return redirect()->route('pay.lookup')->with('success', __('payment_gateway.payment_pending'));
    }

    private function handleWebhook(string $gateway, array $payload)
    {
        try {
            $reference = $payload['depositId']
                ?? $payload['transaction_id']
                ?? $payload['tx_ref']
                ?? $payload['cpm_trans_id']
                ?? null;

            if (!$reference) {
                Log::warning("Payment webhook {$gateway} missing reference", $payload);

                return response()->json(['message' => 'ignored'], 200);
            }

            $transaction = PaymentGatewayTransaction::where('gateway', $gateway)
                ->where(function ($q) use ($reference) {
                    $q->where('external_id', $reference)->orWhere('gateway_reference', $reference);
                })
                ->first();

            if (!$transaction) {
                return response()->json(['message' => 'transaction not found'], 404);
            }

            $driver = $this->gatewayManager->driver($transaction->institution_id, $gateway);
            $result = $driver->parseCallback($payload);
            $this->completionService->complete($transaction, $result);

            return response()->json(['message' => 'ok']);
        } catch (\Throwable $e) {
            Log::error("Payment webhook {$gateway} error: " . $e->getMessage(), ['payload' => $payload]);

            return response()->json(['message' => 'error'], 500);
        }
    }
}
