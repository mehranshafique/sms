<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default package for new institutions
    |--------------------------------------------------------------------------
    |
    | Used when Super Admin does not pick a plan on the institute form.
    | Resolved by ID first, then by name, then cheapest active package.
    |
    */
    'default_package_id' => env('SUBSCRIPTION_DEFAULT_PACKAGE_ID'),
    'default_package_name' => env('SUBSCRIPTION_DEFAULT_PACKAGE_NAME', 'Basic Plan'),

    /** Days for the initial subscription when a school is created. */
    'initial_duration_days' => (int) env('SUBSCRIPTION_INITIAL_DAYS', 365),

    /** Status assigned on institute creation (active = immediate module access). */
    'initial_status' => env('SUBSCRIPTION_INITIAL_STATUS', 'active'),
];
