<?php

namespace App\Services\PaymentGateways;

use App\Models\PaymentGatewayTransaction;
use App\Services\InAppNotificationService;
use App\Services\NotificationService;
use App\Services\PaymentRecordingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentGatewayCompletionService
{
    public function __construct(
        protected PaymentGatewayManager $gatewayManager,
        protected PaymentRecordingService $paymentRecordingService,
        protected NotificationService $notificationService
    ) {}

    public function complete(PaymentGatewayTransaction $transaction, array $callbackResult): bool
    {
        if ($transaction->isCompleted()) {
            return true;
        }

        $status = $callbackResult['status'] ?? 'processing';

        if ($status === 'failed') {
            $transaction->update([
                'status' => 'failed',
                'gateway_reference' => $callbackResult['gateway_reference'] ?? $transaction->gateway_reference,
                'meta' => array_merge($transaction->meta ?? [], ['last_callback' => $callbackResult['raw'] ?? []]),
            ]);

            return false;
        }

        if ($status !== 'completed') {
            $transaction->update([
                'status' => 'processing',
                'gateway_reference' => $callbackResult['gateway_reference'] ?? $transaction->gateway_reference,
                'meta' => array_merge($transaction->meta ?? [], ['last_callback' => $callbackResult['raw'] ?? []]),
            ]);

            return false;
        }

        return DB::transaction(function () use ($transaction, $callbackResult) {
            $transaction->refresh();
            if ($transaction->isCompleted()) {
                return true;
            }

            $invoice = $transaction->invoice()->lockForUpdate()->first();

            $payment = $this->paymentRecordingService->record($invoice, [
                'amount' => $transaction->amount,
                'method' => $transaction->method ?? 'online',
                'payment_date' => now()->toDateString(),
                'mobile_reference' => $callbackResult['gateway_reference'] ?? $transaction->gateway_reference,
                'notes' => 'Gateway: ' . $transaction->gateway,
                'payer_name' => $transaction->payer_name,
                'payer_phone' => $transaction->payer_phone,
                'source' => 'gateway',
            ]);

            $transaction->update([
                'status' => 'completed',
                'payment_id' => $payment->id,
                'gateway_reference' => $callbackResult['gateway_reference'] ?? $transaction->gateway_reference,
                'meta' => array_merge($transaction->meta ?? [], ['completed_callback' => $callbackResult['raw'] ?? []]),
            ]);

            try {
                $this->notificationService->sendPaymentNotification($payment);
                app(InAppNotificationService::class)->notifyPaymentReceived($payment);
            } catch (\Throwable $e) {
                Log::warning('Gateway payment notification failed: ' . $e->getMessage());
            }

            return true;
        });
    }

    public function verifyAndComplete(PaymentGatewayTransaction $transaction): bool
    {
        $driver = $this->gatewayManager->driver($transaction->institution_id, $transaction->gateway);
        $result = $driver->verifyTransaction($transaction);

        return $this->complete($transaction, $result);
    }
}
