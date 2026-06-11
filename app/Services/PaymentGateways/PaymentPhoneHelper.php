<?php

namespace App\Services\PaymentGateways;

class PaymentPhoneHelper
{
    public static function toMsisdn(string $phone, string $countryCode = '243'): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '0')) {
            $digits = $countryCode . substr($digits, 1);
        }

        if (!str_starts_with($digits, $countryCode) && strlen($digits) <= 10) {
            $digits = $countryCode . $digits;
        }

        return $digits;
    }

    public static function gatewayAmount(float $amount, string $currency, string $gateway): string|int
    {
        if ($gateway === 'cinetpay' && $currency === 'CDF') {
            return (int) round($amount);
        }

        if ($gateway === 'pawapay' && $currency === 'CDF') {
            return (string) (int) round($amount);
        }

        return number_format($amount, 2, '.', '');
    }
}
