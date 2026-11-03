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
            'capabilities' => [
                'is_online' => false,
                'requires_provider_redirect' => false,
                'requires_manual_review' => true,
                'allows_saved_methods' => false,
            ],
        ],

        'stripe' => [
            'driver' => StripeGateway::class,
            'enabled' => env('STRIPE_ENABLED', false),
            'secret_key' => env('STRIPE_SECRET_KEY'),
            'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
            'currency' => strtoupper((string) env('STRIPE_CURRENCY', 'USD')),
            'checkout_expires_minutes' => (int) env('STRIPE_CHECKOUT_EXPIRES_MINUTES', 30),
            'capabilities' => [
                'is_online' => true,
                'requires_provider_redirect' => true,
                'requires_manual_review' => false,
                'allows_saved_methods' => false,
            ],
        ],
    ],

    // Keep disabled until the accepted commercial quote includes explicit,
    // structured deposit and balance amounts approved by the customer.
    'installments' => [
        'enabled' => env('PAYMENT_APPROVED_INSTALLMENTS_ENABLED', false),
    ],

    'webhooks' => [
        'queue' => env('PAYMENT_WEBHOOK_QUEUE', 'payments'),
        'claim_timeout_minutes' => (int) env('PAYMENT_WEBHOOK_CLAIM_TIMEOUT_MINUTES', 10),
        'max_payload_bytes' => (int) env('PAYMENT_WEBHOOK_MAX_PAYLOAD_BYTES', 262144),
        'redispatch_after_minutes' => (int) env('PAYMENT_WEBHOOK_REDISPATCH_MINUTES', 5),
        'max_recovery_attempts' => (int) env('PAYMENT_WEBHOOK_MAX_RECOVERY_ATTEMPTS', 30),
    ],

    'reconciliation' => [
        'pending_after_minutes' => (int) env('PAYMENT_RECONCILE_AFTER_MINUTES', 10),
        'batch_size' => (int) env('PAYMENT_RECONCILE_BATCH_SIZE', 100),
        'cooldown_minutes' => (int) env('PAYMENT_RECONCILE_COOLDOWN_MINUTES', 20),
    ],

    'monitoring' => [
        'stale_after_minutes' => (int) env('PAYMENT_HEALTH_STALE_MINUTES', 30),
        'sample_size' => (int) env('PAYMENT_HEALTH_SAMPLE_SIZE', 75),
        'unsettled_warning_count' => (int) env('PAYMENT_HEALTH_UNSETTLED_THRESHOLD', 20),
        'alert_email' => env('PAYMENT_HEALTH_ALERT_EMAIL', ''),
        'alert_cooldown_minutes' => (int) env('PAYMENT_HEALTH_ALERT_COOLDOWN_MINUTES', 60),
    ],
];
