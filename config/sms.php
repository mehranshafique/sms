<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default SMS Driver
    |--------------------------------------------------------------------------
    | Options: 'infobip', 'mobishastra', 'log'
    */
    'default' => env('SMS_DRIVER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Infobip Configuration
    |--------------------------------------------------------------------------
    */
    'infobip' => [
        'base_url' => env('INFOBIP_BASE_URL'),
        'api_key' => env('INFOBIP_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mobishastra Configuration
    |--------------------------------------------------------------------------
    */
    'mobishastra' => [
        'user' => env('MOBISHASTRA_USER'),
        'password' => env('MOBISHASTRA_PASSWORD'),
        'sender_id' => env('MOBISHASTRA_SENDER_ID', 'Digitex'),
    ],
];