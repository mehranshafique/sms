<?php

namespace App\Services;

use App\Models\Institution;

class CurrencyDisplayService
{
    public function formatWithSecondary(?int $institutionId, float $amount, ?string $primarySymbol = null): string
    {
        $primarySymbol = $primarySymbol ?? \App\Enums\CurrencySymbol::default();
        $primary = $primarySymbol . ' ' . number_format($amount, 2);

        if (!$institutionId) {
            return $primary;
        }

        $institution = Institution::find($institutionId);
        if (!$institution?->secondary_currency || !$institution->exchange_rate || $institution->exchange_rate <= 0) {
            return $primary;
        }

        $secondaryCode = $institution->secondary_currency;
        $converted = $amount / (float) $institution->exchange_rate;

        return $primary . ' (' . $secondaryCode . ' ' . number_format($converted, 2) . ')';
    }
}
