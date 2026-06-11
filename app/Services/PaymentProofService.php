<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\PaymentProofSubmission;
use App\Services\InAppNotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PaymentProofService
{
    public function __construct(
        protected PaymentMethodService $paymentMethodService,
        protected PaymentRecordingService $paymentRecordingService,
        protected NotificationService $notificationService
    ) {}

    /**
     * @param  array{payer_name: string, payer_phone: string, method: string, amount: float|int|string, paid_at: string, transaction_reference: string, notes?: ?string}  $data
     */
    public function submit(Invoice $invoice, array $data, ?UploadedFile $receipt = null): PaymentProofSubmission
    {
        if (!$this->paymentMethodService->isMethodEnabled($invoice->institution_id, $data['method'])) {
            throw ValidationException::withMessages(['method' => __('payment.method_not_enabled')]);
        }

        $amount = (float) $data['amount'];
        $this->paymentRecordingService->assertAmountAllowedPublic($invoice, $amount);

        $path = null;
        if ($receipt) {
            $path = $receipt->store('payment-proofs/' . $invoice->institution_id, 'public');
        }

        $proof = PaymentProofSubmission::create([
            'institution_id' => $invoice->institution_id,
            'invoice_id' => $invoice->id,
            'payer_name' => $data['payer_name'],
            'payer_phone' => $data['payer_phone'],
            'method' => $data['method'],
            'amount' => $amount,
            'paid_at' => $data['paid_at'],
            'transaction_reference' => $data['transaction_reference'],
            'receipt_path' => $path,
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
        ]);

        app(InAppNotificationService::class)->notifyPaymentProofSubmitted($proof);
        $this->notificationService->sendPaymentProofSubmittedNotification($proof);

        return $proof;
    }

    public function approve(PaymentProofSubmission $proof, int $reviewerId): PaymentProofSubmission
    {
        if (!$proof->isPending()) {
            throw ValidationException::withMessages(['status' => __('payment_proof.already_reviewed')]);
        }

        return DB::transaction(function () use ($proof, $reviewerId) {
            $proof->refresh();
            $invoice = $proof->invoice()->lockForUpdate()->first();

            $payment = $this->paymentRecordingService->record($invoice, [
                'amount' => $proof->amount,
                'method' => $proof->method,
                'payment_date' => $proof->paid_at->toDateString(),
                'mobile_reference' => $proof->transaction_reference,
                'notes' => trim(($proof->notes ?? '') . ' [Proof #' . $proof->id . ']'),
                'payer_name' => $proof->payer_name,
                'payer_phone' => $proof->payer_phone,
                'source' => 'proof',
            ], $reviewerId);

            $proof->update([
                'status' => 'approved',
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
                'payment_id' => $payment->id,
            ]);

            $this->notificationService->sendPaymentNotification($payment);
            app(InAppNotificationService::class)->notifyPaymentReceived($payment);

            return $proof->fresh();
        });
    }

    public function reject(PaymentProofSubmission $proof, int $reviewerId, ?string $reason = null): PaymentProofSubmission
    {
        if (!$proof->isPending()) {
            throw ValidationException::withMessages(['status' => __('payment_proof.already_reviewed')]);
        }

        $proof->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $proof = $proof->fresh();
        app(InAppNotificationService::class)->notifyPaymentProofRejected($proof);
        $this->notificationService->sendPaymentProofRejectedNotification($proof);

        return $proof;
    }
}
