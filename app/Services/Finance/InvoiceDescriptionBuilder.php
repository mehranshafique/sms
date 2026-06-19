<?php

namespace App\Services\Finance;

use App\Models\FeeStructure;

class InvoiceDescriptionBuilder
{
    public function forFeeStructure(FeeStructure $fee, ?FeeStructure $parentGlobal = null): string
    {
        if ($fee->payment_mode === 'installment') {
            $globalName = $parentGlobal?->name ?? __('invoice.global_fee_fallback');

            return __('invoice.installment_of_global', [
                'installment' => $fee->name,
                'global' => $globalName,
            ]);
        }

        $key = 'invoice.payment_mode_' . $fee->payment_mode;
        if (trans()->has($key)) {
            return __($key, ['name' => $fee->name]);
        }

        return "{$fee->name} (" . ucfirst($fee->payment_mode) . ')';
    }
}
