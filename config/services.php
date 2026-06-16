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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'onesignal' => [
        'app_id' => env('ONESIGNAL_APP_ID'),
        'api_key' => env('ONESIGNAL_API_KEY'),
    ],

    'shopify' => [
        'client_id' => env('SHOPIFY_CLIENT_ID'),
        'client_secret' => env('SHOPIFY_CLIENT_SECRET'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'subscription_interval' => env('STRIPE_SUBSCRIPTION_INTERVAL', 'month'), // Options: day, week, month, year
        'currency' => env('STRIPE_CURRENCY', 'aed'), // Currency code (ISO 4217)
        'currency_symbol' => env('STRIPE_CURRENCY_SYMBOL', 'AED'), // Display symbol
    ],

];
