<?php

namespace App\Enums;

use App\Services\CurrencyService;

enum CurrencySymbol: string
{
    case USD = '$';
    case CDF = 'FC';
    case EUR = '€';
    case GBP = '£';

    public static function default(): string
    {
        return (string) config('app.currency_symbol', self::USD->value);
    }

    public static function code(): string
    {
        return (string) config('app.currency_code', 'USD');
    }

    public static function format(float|int|string $amount): string
    {
        return app(CurrencyService::class)->format($amount);
    }
}
