<?php

namespace App\Services;

use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentRecordingService
{
    public function __construct(
        protected PaymentMethodService $paymentMethodService
    ) {}

    /**
     * @param  array{amount: float|int|string, method: string, payment_date?: string, mobile_reference?: ?string, notes?: ?string, payer_name?: ?string, payer_phone?: ?string, source?: string}  $data
     */
    public function record(Invoice $invoice, array $data, ?int $receivedBy = null): Payment
    {
        $invoice->loadMissing(['items.feeStructure', 'student']);

        $method = (string) $data['method'];
        if (!$this->paymentMethodService->isMethodEnabled($invoice->institution_id, $method)) {
            throw ValidationException::withMessages([
                'method' => __('payment.method_not_enabled'),
            ]);
        }

        $amount = (float) $data['amount'];
        $this->assertAmountAllowed($invoice, $amount);

        $mobileMethods = ['orange_money', 'airtel_money', 'mpesa', 'vodacom'];
        $mobileReference = $data['mobile_reference'] ?? null;

        if ($this->paymentMethodService->isMobileMethod($method) && empty($mobileReference)) {
            throw ValidationException::withMessages([
                'mobile_reference' => __('payment.mobile_reference_required'),
            ]);
        }

        $payment = null;

        DB::transaction(function () use ($invoice, $data, $receivedBy, $method, $amount, $mobileMethods, $mobileReference, &$payment) {
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'institution_id' => $invoice->institution_id,
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'amount' => $amount,
                'method' => $method,
                'source' => $data['source'] ?? 'admin',
                'mobile_money_provider' => in_array($method, $mobileMethods, true) ? $method : null,
                'mobile_reference' => $mobileReference,
                'notes' => $data['notes'] ?? null,
                'payer_name' => $data['payer_name'] ?? null,
                'payer_phone' => $data['payer_phone'] ?? null,
                'received_by' => $receivedBy,
                'transaction_id' => 'TRX-' . strtoupper(Str::random(10)),
                'receipt_number' => self::generateReceiptNumber($invoice->institution_id),
                'receipt_verify_token' => Str::random(40),
            ]);

            $newPaid = (float) $invoice->paid_amount + $amount;
            $total = (float) $invoice->total_amount;
            $status = ($newPaid >= $total - 0.01) ? 'paid' : 'partial';

            $invoice->update([
                'paid_amount' => $newPaid,
                'status' => $status,
            ]);
        });

        return $payment->fresh(['invoice.student']);
    }

    public static function generateReceiptNumber(int $institutionId): string
    {
        $year = now()->format('y');
        $prefix = "RCP-{$year}";

        $last = Payment::where('institution_id', $institutionId)
            ->where('receipt_number', 'like', $prefix . '%')
            ->orderByDesc('receipt_number')
            ->value('receipt_number');

        $sequence = 1;
        if ($last && preg_match('/(\d{5})$/', $last, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return $prefix . str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    public function assertAmountAllowedPublic(Invoice $invoice, float $amount): void
    {
        $invoice->loadMissing(['items.feeStructure']);
        $this->assertAmountAllowed($invoice, $amount);
    }

    private function assertAmountAllowed(Invoice $invoice, float $amount): void
    {
        $studentId = $invoice->student_id;
        $academicSessionId = $invoice->academic_session_id;

        $enrollment = StudentEnrollment::where('student_id', $studentId)
            ->where('academic_session_id', $academicSessionId)
            ->first();

        if ($enrollment) {
            $globalFeeStructure = FeeStructure::where('institution_id', $invoice->institution_id)
                ->where('academic_session_id', $academicSessionId)
                ->where('payment_mode', 'global')
                ->where(function ($q) use ($enrollment) {
                    $q->where('class_section_id', $enrollment->class_section_id)
                        ->orWhere('grade_level_id', $enrollment->grade_level_id);
                })
                ->first();

            if ($globalFeeStructure) {
                $annualLimit = (float) $globalFeeStructure->amount;
                $totalPaidSoFar = Payment::whereHas('invoice', function ($q) use ($studentId, $academicSessionId) {
                    $q->where('student_id', $studentId)
                        ->where('academic_session_id', $academicSessionId);
                })->sum('amount');

                if (($totalPaidSoFar + $amount) > ($annualLimit + 0.01)) {
                    $remainingGlobal = max(0, $annualLimit - $totalPaidSoFar);
                    throw ValidationException::withMessages([
                        'amount' => __('payment.global_cap_exceeded', [
                            'limit' => number_format($annualLimit, 2),
                            'remaining' => number_format($remainingGlobal, 2),
                        ]),
                    ]);
                }
            }
        }

        foreach ($invoice->items as $item) {
            if ($item->feeStructure && $item->feeStructure->payment_mode === 'installment') {
                $currentOrder = $item->feeStructure->installment_order;

                if ($currentOrder > 1) {
                    $previousPending = Invoice::where('student_id', $studentId)
                        ->where('academic_session_id', $academicSessionId)
                        ->where('status', '!=', 'paid')
                        ->whereHas('items.feeStructure', function ($q) use ($currentOrder) {
                            $q->where('installment_order', '<', $currentOrder)
                                ->where('payment_mode', 'installment');
                        })
                        ->exists();

                    if ($previousPending) {
                        throw ValidationException::withMessages([
                            'amount' => __('payment.previous_installment_pending_error'),
                        ]);
                    }
                }
            }
        }

        $remaining = (float) $invoice->total_amount - (float) $invoice->paid_amount;

        if ($amount > ($remaining + 0.01)) {
            throw ValidationException::withMessages([
                'amount' => __('payment.exceeds_balance') . ' (' . number_format($remaining, 2) . ')',
            ]);
        }
    }
}
