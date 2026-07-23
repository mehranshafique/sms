<?php

namespace App\Services\PaymentGateways;

class PaymentPhoneHelper
{
    /**
     * Common country calling codes (longest first) so we do not double-prefix
     * an already-international number with the default (DRC 243).
     *
     * @var list<string>
     */
    private const COUNTRY_CODES = [
        '243', // DR Congo
        '92',  // Pakistan
        '33',  // France
        '32',  // Belgium
        '27',  // South Africa
        '20',  // Egypt
        '1',   // US/Canada
        '44',  // UK
        '91',  // India
        '971', // UAE
        '966', // Saudi
        '254', // Kenya
        '250', // Rwanda
        '256', // Uganda
        '255', // Tanzania
        '237', // Cameroon
        '225', // Côte d'Ivoire
        '221', // Senegal
        '212', // Morocco
        '216', // Tunisia
        '213', // Algeria
    ];

    public static function toMsisdn(string $phone, string $countryCode = '243'): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return '';
        }

        // Already looks international (has a known country code or is long enough)
        if (self::startsWithCountryCode($digits) || strlen($digits) >= 11) {
            return $digits;
        }

        // Local form: leading 0 → replace with default country code
        if (str_starts_with($digits, '0')) {
            return $countryCode . substr($digits, 1);
        }

        // Short national number → prepend default country
        return $countryCode . $digits;
    }

    private static function startsWithCountryCode(string $digits): bool
    {
        $codes = self::COUNTRY_CODES;
        usort($codes, fn ($a, $b) => strlen($b) <=> strlen($a));

        foreach ($codes as $code) {
            if (str_starts_with($digits, $code)) {
                return true;
            }
        }

        return false;
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
