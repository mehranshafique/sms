<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'hardware' => [
        'secret' => env('HARDWARE_SECRET'),
        /** Comma-separated institution IDs this gate device may access. Empty = disabled cross-tenant header. */
        'allowed_institution_ids' => array_values(array_filter(array_map(
            'intval',
            explode(',', (string) env('HARDWARE_ALLOWED_INSTITUTION_IDS', ''))
        ))),
    ],

    'chatbot' => [
        'twilio_auth_token' => env('TWILIO_AUTH_TOKEN'),
        'meta_app_secret' => env('META_WHATSAPP_APP_SECRET'),
        'meta_verify_token' => env('CHATBOT_META_VERIFY_TOKEN'),
        'infobip_api_key' => env('INFOBIP_API_KEY'),
        'infobip_skip_verify' => env('INFOBIP_WEBHOOK_SKIP_VERIFY', false),
        'telegram_bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'telegram_skip_verify' => env('TELEGRAM_WEBHOOK_SKIP_VERIFY', false),
        'webhook_secret' => env('CHATBOT_WEBHOOK_SECRET'),
    ],

];
