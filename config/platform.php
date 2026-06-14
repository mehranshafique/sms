<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public self-registration
    |--------------------------------------------------------------------------
    |
    | Disabled by default. Set REGISTRATION_ENABLED=true only for dev/demo.
    |
    */
    'registration_enabled' => (bool) env('REGISTRATION_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Platform super admin (seed / recovery)
    |--------------------------------------------------------------------------
    */
    'platform_admin_email' => env('PLATFORM_ADMIN_EMAIL', 'digitex-admin@yopmail.com'),
    'platform_admin_username' => env('PLATFORM_ADMIN_USERNAME', 'digitex-admin'),
    'platform_admin_password' => env('PLATFORM_ADMIN_PASSWORD'),

];
