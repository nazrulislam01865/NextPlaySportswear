<?php

use App\Payments\Gateways\ManualGateway;
use App\Payments\Gateways\StripeGateway;

return [
    'default' => env('PAYMENT_DEFAULT_GATEWAY', 'stripe'),

    /*
    |--------------------------------------------------------------------------
    | Central payment gateway registry
    |--------------------------------------------------------------------------
    |
    | Checkout/payment-method records reference these provider codes. Adding a
    | new gateway should require a new adapter plus one entry here; controllers
    | and checkout business logic should remain provider-agnostic.
    |
    */
    'gateways' => [
        'manual' => [
            'driver' => ManualGateway::class,
            'enabled' => true,
        ],

        'stripe' => [
            'driver' => StripeGateway::class,
            'enabled' => env('STRIPE_ENABLED', false),
            'secret_key' => env('STRIPE_SECRET_KEY'),
            'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
            'currency' => strtoupper((string) env('STRIPE_CURRENCY', 'USD')),
            'checkout_expires_minutes' => (int) env('STRIPE_CHECKOUT_EXPIRES_MINUTES', 30),
        ],
    ],

    'webhooks' => [
        'queue' => env('PAYMENT_WEBHOOK_QUEUE', 'payments'),
    ],

    'reconciliation' => [
        'pending_after_minutes' => (int) env('PAYMENT_RECONCILE_AFTER_MINUTES', 10),
        'batch_size' => (int) env('PAYMENT_RECONCILE_BATCH_SIZE', 100),
    ],
];
