<?php

namespace App\Enums;

enum CurrencySymbol: string
{
    case USD = '$';
    case CDF = 'FC';
    case EUR = '€';
    case GBP = '£';
    
    public static function default(): string
    {
        return self::USD->value;
    }
}