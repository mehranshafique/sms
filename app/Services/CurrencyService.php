<?php

namespace App\Services;

use App\Models\InstitutionSetting;

class CurrencyService
{
    public function supported(): array
    {
        return config('currencies', []);
    }

    public function getSettings(?int $institutionId): array
    {
        $code = InstitutionSetting::get($institutionId, 'currency_code', 'USD');
        $catalog = $this->supported();

        if (!isset($catalog[$code])) {
            $code = 'USD';
        }

        $symbol = InstitutionSetting::get($institutionId, 'currency_symbol');
        if ($symbol === null || $symbol === '') {
            $symbol = $catalog[$code]['symbol'];
        }

        $position = InstitutionSetting::get($institutionId, 'currency_position', 'before');
        $decimals = (int) InstitutionSetting::get($institutionId, 'currency_decimals', 2);

        return [
            'code' => $code,
            'symbol' => $symbol,
            'name' => $catalog[$code]['name'],
            'flag' => $catalog[$code]['flag'],
            'position' => in_array($position, ['before', 'after'], true) ? $position : 'before',
            'decimals' => max(0, min(4, $decimals)),
        ];
    }

    public function applyToConfig(?int $institutionId): void
    {
        $settings = $this->getSettings($institutionId);

        config([
            'app.currency_code' => $settings['code'],
            'app.currency_symbol' => $settings['symbol'],
            'app.currency_position' => $settings['position'],
            'app.currency_decimals' => $settings['decimals'],
        ]);
    }

    public function format(float|int|string $amount, ?int $institutionId = null): string
    {
        if ($institutionId !== null) {
            $settings = $this->getSettings($institutionId);
        } else {
            $settings = [
                'symbol' => config('app.currency_symbol', '$'),
                'position' => config('app.currency_position', 'before'),
                'decimals' => (int) config('app.currency_decimals', 2),
            ];
        }

        $formatted = number_format((float) $amount, $settings['decimals']);

        return $settings['position'] === 'after'
            ? $formatted . ' ' . $settings['symbol']
            : $settings['symbol'] . ' ' . $formatted;
    }

    public function save(?int $institutionId, string $code, string $position, ?string $customSymbol = null, int $decimals = 2): void
    {
        $catalog = $this->supported();

        if (!isset($catalog[$code])) {
            $code = 'USD';
        }

        $symbol = ($customSymbol !== null && $customSymbol !== '')
            ? $customSymbol
            : $catalog[$code]['symbol'];

        InstitutionSetting::set($institutionId, 'currency_code', $code, 'currency');
        InstitutionSetting::set($institutionId, 'currency_symbol', $symbol, 'currency');
        InstitutionSetting::set($institutionId, 'currency_position', $position, 'currency');
        InstitutionSetting::set($institutionId, 'currency_decimals', (string) max(0, min(4, $decimals)), 'currency');

        if ($institutionId === null || $institutionId === (int) session('active_institution_id')) {
            $this->applyToConfig($institutionId);
        }
    }
}
