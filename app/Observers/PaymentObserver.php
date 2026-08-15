<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\Finance\FeeAllocationService;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentObserver
{
    public function __construct(protected FeeAllocationService $allocations) {}

    public function created(Payment $payment): void
    {
        try {
            $this->allocations->allocate($payment);
        } catch (Throwable $e) {
            // Never let the component breakdown block a payment; `finance:backfill-allocations`
            // rebuilds anything that was missed.
            Log::warning('Fee allocation failed for payment ' . $payment->id . ': ' . $e->getMessage());
        }
    }
}
