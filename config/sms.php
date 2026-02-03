<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default SMS Driver
    |--------------------------------------------------------------------------
    | Options: 'infobip', 'mobishastra', 'log'
    */
    'default' => env('SMS_DRIVER', 'mobishastra'),

    /*
    |--------------------------------------------------------------------------
    | Mobishastra Configuration
    |--------------------------------------------------------------------------
    */
    'mobishastra' => [
        'user'       => env('MOBISHASTRA_USER', 'INTEGRALE'),
        'password'   => env('MOBISHASTRA_PASSWORD', 'yhy6h_6_'),
        'sender_id'  => env('MOBISHASTRA_SENDER_ID', 'ARCHIDIOKIN'),
        // Optional headers from your file
        'app_id'     => env('MOBISHASTRA_APP_ID', 'huidu_liang'),
        'app_secret' => env('MOBISHASTRA_APP_SECRET', '07431b10-58ca-4546-8f09-08e24be729da'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Infobip Configuration (Alternative)
    |--------------------------------------------------------------------------
    */
    'infobip' => [
        'base_url' => env('INFOBIP_BASE_URL', 'https://xkglel.api.infobip.com'),
        'api_key' => env('INFOBIP_API_KEY'),
        'whatsapp_from' => env('INFOBIP_WHATSAPP_FROM', '447860099299'), // Default test number
    ],
];