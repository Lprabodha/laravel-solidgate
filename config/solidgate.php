<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SolidGate Credentials
    |--------------------------------------------------------------------------
    |
    | Your SolidGate public key and secret key. These can be found in your
    | SolidGate merchant dashboard.
    |
    */

    'public_key' => env('SOLIDGATE_PUBLIC_KEY'),
    'secret_key' => env('SOLIDGATE_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the SolidGate API endpoints and request settings.
    |
    */

    'api' => [
        'base_url' => env('SOLIDGATE_API_BASE_URL', 'https://pay.solidgate.com/api/v1/'),
        'subscriptions_url' => env('SOLIDGATE_SUBSCRIPTIONS_BASE_URL', 'https://subscriptions.solidgate.com/api/v1/'),
        'gate_url' => env('SOLIDGATE_GATE_BASE_URL', 'https://gate.solidgate.com/api/'),
        'reports_url' => env('SOLIDGATE_REPORTS_BASE_URL', 'https://reports.solidgate.com/'),
        'timeout' => env('SOLIDGATE_TIMEOUT', 30),
        'verify_ssl' => env('SOLIDGATE_VERIFY_SSL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for handling SolidGate webhook events. You can use
    | separate credentials for webhook verification if needed.
    |
    */

    'webhook' => [
        'enabled' => env('SOLIDGATE_WEBHOOK_ENABLED', false),
        'path' => env('SOLIDGATE_WEBHOOK_PATH', 'solidgate/webhook'),
        'public_key' => env('SOLIDGATE_WEBHOOK_PUBLIC_KEY'),
        'secret' => env('SOLIDGATE_WEBHOOK_SECRET'),
        'signature_header' => env('SOLIDGATE_SIGNATURE_HEADER', 'Signature'),
        'middleware' => env('SOLIDGATE_WEBHOOK_MIDDLEWARE', 'solidgate.webhook'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable logging of API requests and responses for debugging purposes.
    |
    */

    'log_requests' => env('SOLIDGATE_LOG_REQUESTS', false),

    /*
    |--------------------------------------------------------------------------
    | Default Payment Platform
    |--------------------------------------------------------------------------
    |
    | Applied to card payment requests (charge, recurring, resign, etc.) when
    | "platform" is not provided. Valid values: WEB, MOB, APP.
    |
    */

    'default_platform' => env('SOLIDGATE_DEFAULT_PLATFORM', 'WEB'),

    /*
    |--------------------------------------------------------------------------
    | Default Payment Type
    |--------------------------------------------------------------------------
    |
    | Applied to first card charges when card_cvv is present and payment_type
    | is omitted. Use PaymentType constants (e.g. 1-click for CIT).
    |
    */

    'default_payment_type' => env('SOLIDGATE_DEFAULT_PAYMENT_TYPE', '1-click'),
];
