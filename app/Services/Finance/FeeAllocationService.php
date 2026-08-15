<?php

namespace App\Services\Finance;

use App\Models\FeeStructure;
use App\Models\InstitutionSetting;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Illuminate\Support\Facades\DB;

/**
 * Splits payments across fee components for fee structures configured with the
 * proportional allocation model.
 *
 * A payment is first spread across the invoice lines in proportion to their
 * amounts, then each line's share is spread across that fee's components in
 * proportion to the component amounts. All maths is done in integer cents and
 * the largest-remainder method guarantees the allocations sum exactly to the
 * payment amount.
 */
class FeeAllocationService
{
    public const SETTING_KEY = 'proportional_fee_allocation_enabled';

    /** Minimum number of components a proportional fee must define. */
    public const MIN_COMPONENTS = 2;

    /** @var array<int, array{components: \Illuminate\Support\Collection, denominator: float, fee_name: string}|null> */
    protected array $resolvedComponents = [];

    /**
     * Whether the school has opted into the proportional fee model. Fees that
     * were already broken down keep working even if the toggle is switched off;
     * the toggle only controls whether new breakdowns can be configured.
     */
    public function isEnabledFor(?int $institutionId): bool
    {
        if (! $institutionId) {
            return false;
        }

        return (string) InstitutionSetting::get($institutionId, self::SETTING_KEY, '0') === '1';
    }

    public function setEnabled(?int $institutionId, bool $enabled): void
    {
        if (! $institutionId) {
            return;
        }

        InstitutionSetting::set($institutionId, self::SETTING_KEY, $enabled ? '1' : '0', 'finance');
    }

    /**
     * Persist the component breakdown for a payment. Idempotent: a payment that
     * already has allocations is left untouched.
     */
    public function allocate(Payment $payment): int
    {
        if ($payment->allocations()->exists()) {
            return 0;
        }

        $invoice = Invoice::with('items.feeStructure.components')->find($payment->invoice_id);

        if (! $invoice) {
            return 0;
        }

        $rows = $this->buildRows($payment, $invoice);

        if ($rows === []) {
            return 0;
        }

        PaymentAllocation::insert($rows);

        return count($rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildRows(Payment $payment, Invoice $invoice): array
    {
        $amountCents = $this->toCents($payment->amount);

        if ($amountCents <= 0) {
            return [];
        }

        $now = now();
        $base = [
            'institution_id' => $invoice->institution_id,
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        // Discount lines are negative and must not attract a share of the payment.
        $items = $invoice->items->filter(fn ($item) => (float) $item->amount > 0)->values();

        if ($items->isEmpty()) {
            return [$base + [
                'invoice_item_id' => null,
                'fee_structure_id' => null,
                'fee_component_id' => null,
                'label' => __('finance.unallocated_payment'),
                'amount' => $this->fromCents($amountCents),
            ]];
        }

        $itemShares = $this->distribute(
            $amountCents,
            $items->map(fn ($item) => $this->toCents($item->amount))->all()
        );

        $rows = [];

        foreach ($items as $index => $item) {
            $share = $itemShares[$index];

            if ($share <= 0) {
                continue;
            }

            $resolved = $item->feeStructure ? $this->resolveComponents($item->feeStructure) : null;
            $components = $resolved ? $resolved['components'] : collect();

            $weights = $components->map(fn ($component) => $this->toCents($component->amount))->all();

            if ($components->isEmpty() || array_sum($weights) <= 0) {
                $rows[] = $base + [
                    'invoice_item_id' => $item->id,
                    'fee_structure_id' => $item->fee_structure_id,
                    'fee_component_id' => null,
                    'label' => (string) $item->description,
                    'amount' => $this->fromCents($share),
                ];

                continue;
            }

            $componentShares = $this->distribute($share, $weights);

            foreach ($components as $componentIndex => $component) {
                if ($componentShares[$componentIndex] <= 0) {
                    continue;
                }

                $rows[] = $base + [
                    'invoice_item_id' => $item->id,
                    'fee_structure_id' => $item->fee_structure_id,
                    'fee_component_id' => $component->id,
                    'label' => (string) $component->name,
                    'amount' => $this->fromCents($componentShares[$componentIndex]),
                ];
            }
        }

        return $rows;
    }

    /**
     * Components that drive the split for a fee structure.
     *
     * A fee can carry its own breakdown, or — when it is one installment of a
     * proportional global fee — inherit the global fee's components so that each
     * tranche is split with the same percentages.
     *
     * @return array{components: \Illuminate\Support\Collection, denominator: float, fee_name: string}|null
     */
    public function resolveComponents(FeeStructure $structure): ?array
    {
        if (array_key_exists($structure->id, $this->resolvedComponents)) {
            return $this->resolvedComponents[$structure->id];
        }

        $resolved = null;

        if ($structure->isProportional() && $structure->components->isNotEmpty()) {
            $resolved = [
                'components' => $structure->components,
                'denominator' => (float) $structure->amount,
                'fee_name' => (string) $structure->name,
            ];
        } elseif ($structure->payment_mode === 'installment') {
            $parent = FeeStructure::with('components')
                ->where('institution_id', $structure->institution_id)
                ->where('academic_session_id', $structure->academic_session_id)
                ->where('fee_type_id', $structure->fee_type_id)
                ->where('payment_mode', 'global')
                ->where('allocation_mode', 'proportional')
                ->where(function ($query) use ($structure) {
                    $query->where('class_section_id', $structure->class_section_id)
                        ->orWhere('grade_level_id', $structure->grade_level_id);
                })
                ->first();

            if ($parent && $parent->components->isNotEmpty() && (float) $parent->amount > 0) {
                $resolved = [
                    'components' => $parent->components,
                    'denominator' => (float) $parent->amount,
                    'fee_name' => (string) $parent->name,
                ];
            }
        }

        return $this->resolvedComponents[$structure->id] = $resolved;
    }

    /**
     * Per-component expected / collected figures. Filters are optional and
     * narrow the underlying invoices.
     *
     * @param  array{academic_session_id?: ?int, class_section_id?: ?int, student_id?: ?int, student_ids?: array<int, int>}  $filters
     * @return array<int, array{component_id: ?int, label: string, fee_name: ?string, share: float, expected: float, collected: float, outstanding: float}>
     */
    public function componentSummary(int $institutionId, array $filters = []): array
    {
        $expected = $this->expectedByComponent($institutionId, $filters);
        $collected = $this->collectedByComponent($institutionId, $filters);

        $rows = [];

        foreach ($expected as $componentId => $row) {
            $paid = (float) ($collected[$componentId]['amount'] ?? 0);

            $rows[] = [
                'component_id' => $componentId,
                'label' => $row['label'],
                'fee_name' => $row['fee_name'],
                'share' => $row['share'],
                'expected' => round($row['amount'], 2),
                'collected' => round($paid, 2),
                'outstanding' => round(max(0, $row['amount'] - $paid), 2),
            ];
        }

        // Components that were paid but are no longer invoiced (renamed/removed fee).
        foreach ($collected as $componentId => $row) {
            if ($componentId !== null && isset($expected[$componentId])) {
                continue;
            }

            $rows[] = [
                'component_id' => $componentId,
                'label' => $row['label'],
                'fee_name' => null,
                'share' => 0.0,
                'expected' => 0.0,
                'collected' => round((float) $row['amount'], 2),
                'outstanding' => 0.0,
            ];
        }

        usort($rows, fn ($a, $b) => $b['expected'] <=> $a['expected'] ?: strcmp($a['label'], $b['label']));

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array{label: string, fee_name: string, share: float, amount: float}>
     */
    protected function expectedByComponent(int $institutionId, array $filters): array
    {
        $query = DB::table('invoice_items')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->where('invoices.institution_id', $institutionId)
            ->whereNotNull('invoice_items.fee_structure_id')
            ->where('invoice_items.amount', '>', 0)
            ->groupBy('invoice_items.fee_structure_id')
            ->select([
                'invoice_items.fee_structure_id as fee_structure_id',
                DB::raw('SUM(invoice_items.amount) as invoiced'),
            ]);

        $this->applyInvoiceFilters($query, $filters);

        $invoiced = $query->get();

        if ($invoiced->isEmpty()) {
            return [];
        }

        $structures = FeeStructure::with('components')
            ->whereIn('id', $invoiced->pluck('fee_structure_id')->all())
            ->get()
            ->keyBy('id');

        $rows = [];

        foreach ($invoiced as $row) {
            $structure = $structures->get((int) $row->fee_structure_id);
            $resolved = $structure ? $this->resolveComponents($structure) : null;

            if (! $resolved || $resolved['denominator'] <= 0) {
                continue;
            }

            foreach ($resolved['components'] as $component) {
                $key = (int) $component->id;
                $share = (float) $component->amount / $resolved['denominator'];

                $rows[$key] ??= [
                    'label' => (string) $component->name,
                    'fee_name' => (string) ($resolved['fee_name'] ?? $structure->name),
                    'share' => round($share * 100, 2),
                    'amount' => 0.0,
                ];

                $rows[$key]['amount'] += ((float) $row->invoiced) * $share;
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int|string, array{label: string, amount: float}>
     */
    protected function collectedByComponent(int $institutionId, array $filters): array
    {
        $query = DB::table('payment_allocations')
            ->join('invoices', 'invoices.id', '=', 'payment_allocations.invoice_id')
            ->where('payment_allocations.institution_id', $institutionId)
            ->whereNotNull('payment_allocations.fee_component_id')
            ->groupBy('payment_allocations.fee_component_id', 'payment_allocations.label')
            ->select([
                'payment_allocations.fee_component_id as component_id',
                'payment_allocations.label as label',
                DB::raw('SUM(payment_allocations.amount) as collected'),
            ]);

        $this->applyInvoiceFilters($query, $filters);

        $rows = [];

        foreach ($query->get() as $row) {
            $key = (int) $row->component_id;
            $rows[$key] = [
                'label' => (string) $row->label,
                'amount' => (float) ($rows[$key]['amount'] ?? 0) + (float) $row->collected,
            ];
        }

        return $rows;
    }

    /**
     * Payments that were not tied to any component (fees without a breakdown).
     *
     * @param  array<string, mixed>  $filters
     */
    public function collectedOutsideComponents(int $institutionId, array $filters = []): float
    {
        $query = DB::table('payment_allocations')
            ->join('invoices', 'invoices.id', '=', 'payment_allocations.invoice_id')
            ->where('payment_allocations.institution_id', $institutionId)
            ->whereNull('payment_allocations.fee_component_id');

        $this->applyInvoiceFilters($query, $filters);

        return (float) $query->sum('payment_allocations.amount');
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array<string, mixed>  $filters
     */
    protected function applyInvoiceFilters($query, array $filters): void
    {
        if (! empty($filters['academic_session_id'])) {
            $query->where('invoices.academic_session_id', $filters['academic_session_id']);
        }

        if (! empty($filters['student_id'])) {
            $query->where('invoices.student_id', $filters['student_id']);
        }

        if (! empty($filters['student_ids'])) {
            $query->whereIn('invoices.student_id', $filters['student_ids']);
        }

        if (! empty($filters['class_section_id'])) {
            $query->whereIn('invoices.student_id', function ($sub) use ($filters) {
                $sub->select('student_id')
                    ->from('student_enrollments')
                    ->where('class_section_id', $filters['class_section_id']);

                if (! empty($filters['academic_session_id'])) {
                    $sub->where('academic_session_id', $filters['academic_session_id']);
                }
            });
        }
    }

    /**
     * Largest-remainder split of $total across $weights.
     *
     * @param  array<int, int>  $weights
     * @return array<int, int>
     */
    public function distribute(int $total, array $weights): array
    {
        $count = count($weights);
        $shares = array_fill(0, $count, 0);

        if ($count === 0 || $total <= 0) {
            return $shares;
        }

        $weightSum = array_sum($weights);

        if ($weightSum <= 0) {
            $shares[0] = $total;

            return $shares;
        }

        $remainders = [];
        $assigned = 0;

        foreach ($weights as $index => $weight) {
            $exact = ($total * $weight) / $weightSum;
            $shares[$index] = (int) floor($exact);
            $remainders[$index] = $exact - $shares[$index];
            $assigned += $shares[$index];
        }

        $leftover = $total - $assigned;
        arsort($remainders);

        foreach (array_keys($remainders) as $index) {
            if ($leftover <= 0) {
                break;
            }

            $shares[$index]++;
            $leftover--;
        }

        return $shares;
    }

    protected function toCents(float|int|string|null $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    protected function fromCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
