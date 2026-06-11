<?php

return [
    'default_environment' => env('PAYMENT_GATEWAY_ENV', 'sandbox'),

    'providers' => [
        'none' => ['label' => 'None (manual only)'],
        'pawapay' => [
            'label' => 'PawaPay',
            'description' => 'Orange, Airtel & M-Pesa/Vodacom mobile money in DRC (recommended).',
            'sandbox_url' => 'https://api.sandbox.pawapay.io',
            'production_url' => 'https://api.pawapay.io',
        ],
        'cinetpay' => [
            'label' => 'CinetPay',
            'description' => 'Popular Francophone Africa gateway — Orange, M-Pesa, Airtel CD (CDF/USD).',
            'checkout_url' => 'https://api-checkout.cinetpay.com/v2/payment',
            'check_url' => 'https://api-checkout.cinetpay.com/v2/payment/check',
        ],
        'flutterwave' => [
            'label' => 'Flutterwave',
            'description' => 'Pan-African gateway with DRC mobile money & cards.',
            'api_url' => 'https://api.flutterwave.com/v3',
        ],
    ],

    /** Map internal method keys → PawaPay provider codes (DRC) */
    'pawapay_providers' => [
        'orange_money' => 'ORANGE_COD',
        'airtel_money' => 'AIRTEL_COD',
        'mpesa' => 'VODACOM_MPESA_COD',
        'vodacom' => 'VODACOM_MPESA_COD',
    ],

    /** Map internal method keys → CinetPay channel codes (DRC) */
    'cinetpay_channels' => [
        'orange_money' => 'OMCD',
        'mpesa' => 'MPESACD',
        'airtel_money' => 'AIRTELCD',
    ],

    'drc_country_code' => '243',
];
