<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\Finance\FeeAllocationService;
use Illuminate\Console\Command;

class BackfillPaymentAllocationsCommand extends Command
{
    protected $signature = 'finance:backfill-allocations
                            {--institution= : Only backfill payments of this institution id}
                            {--rebuild : Delete and recompute allocations that already exist}';

    protected $description = 'Build the fee component breakdown for payments recorded before proportional allocation existed';

    public function handle(FeeAllocationService $allocations): int
    {
        $query = Payment::query()->orderBy('id');

        if ($institutionId = $this->option('institution')) {
            $query->where('institution_id', (int) $institutionId);
        }

        $rebuild = (bool) $this->option('rebuild');

        if (! $rebuild) {
            $query->whereDoesntHave('allocations');
        }

        $total = $query->clone()->count();

        if ($total === 0) {
            $this->info('Nothing to backfill.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $rows = 0;

        $query->chunkById(200, function ($payments) use ($allocations, $rebuild, $bar, &$rows) {
            foreach ($payments as $payment) {
                if ($rebuild) {
                    $payment->allocations()->delete();
                }

                $rows += $allocations->allocate($payment);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Processed {$total} payment(s), wrote {$rows} allocation row(s).");

        return self::SUCCESS;
    }
}
