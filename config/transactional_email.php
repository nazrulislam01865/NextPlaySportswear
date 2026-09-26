<?php

return [

    'enabled' => filter_var(
        env('TRANSACTIONAL_EMAIL_ENABLED', true),
        FILTER_VALIDATE_BOOLEAN
    ),

    'mailer' => env(
        'TRANSACTIONAL_EMAIL_MAILER',
        env('MAIL_MAILER', 'log')
    ),

    /*
    |--------------------------------------------------------------------------
    | Delivery Mode
    |--------------------------------------------------------------------------
    |
    | "after_response" is the safe default for normal transactional email.
    | It sends after the HTTP response in the current PHP process and therefore
    | does not depend on a long-running queue worker. This prevents a stopped
    | worker from silently leaving customer emails in the jobs table forever.
    |
    | Use "queue" only when production has a supervised queue worker. Use
    | "sync" when every message must be sent before the request completes.
    |
    | Supported: "after_response", "queue", "sync"
    |
    */
    'delivery' => [
        'mode' => env(
            'TRANSACTIONAL_EMAIL_DELIVERY_MODE',
            'after_response'
        ),
    ],

    'critical' => [

        // Verification is user-blocking. By default the SMTP/API provider must
        // accept the message before registration/resend reports success.
        'email_verification_sync' => filter_var(
            env('TRANSACTIONAL_EMAIL_VERIFICATION_SYNC', true),
            FILTER_VALIDATE_BOOLEAN
        ),

        // Password reset is user-blocking and security-critical. The provider
        // must accept it before Laravel reports that a reset link was sent.
        'password_reset_sync' => filter_var(
            env('TRANSACTIONAL_EMAIL_PASSWORD_RESET_SYNC', true),
            FILTER_VALIDATE_BOOLEAN
        ),
    ],

    'queue' => [

        // Backward-compatible switch used only when delivery.mode = "queue".
        'enabled' => filter_var(
            env('TRANSACTIONAL_EMAIL_QUEUE', true),
            FILTER_VALIDATE_BOOLEAN
        ),

        'connection' => env(
            'TRANSACTIONAL_EMAIL_QUEUE_CONNECTION'
        ),

        'name' => env(
            'TRANSACTIONAL_EMAIL_QUEUE_NAME'
        ),

        'tries' => (int) env(
            'TRANSACTIONAL_EMAIL_QUEUE_TRIES',
            3
        ),

        'timeout' => (int) env(
            'TRANSACTIONAL_EMAIL_QUEUE_TIMEOUT',
            30
        ),

        'backoff' => array_values(
            array_filter(
                array_map(
                    static fn (string $value): int =>
                        max(1, (int) trim($value)),

                    explode(
                        ',',
                        (string) env(
                            'TRANSACTIONAL_EMAIL_QUEUE_BACKOFF',
                            '60,300,900'
                        )
                    )
                )
            )
        ),
    ],

    'recipients' => [

        'support' => env(
            'EMAIL_SUPPORT_ADDRESS',
            env(
                'STOREFRONT_EMAIL',
                env('MAIL_FROM_ADDRESS')
            )
        ),

        'sales' => env(
            'EMAIL_SALES_ADDRESS',
            env(
                'EMAIL_SUPPORT_ADDRESS',
                env(
                    'STOREFRONT_EMAIL',
                    env('MAIL_FROM_ADDRESS')
                )
            )
        ),

        'orders' => env(
            'EMAIL_ORDERS_ADDRESS',
            env(
                'EMAIL_SUPPORT_ADDRESS',
                env(
                    'STOREFRONT_EMAIL',
                    env('MAIL_FROM_ADDRESS')
                )
            )
        ),

        'returns' => env(
            'EMAIL_RETURNS_ADDRESS',
            env(
                'EMAIL_SUPPORT_ADDRESS',
                env(
                    'STOREFRONT_EMAIL',
                    env('MAIL_FROM_ADDRESS')
                )
            )
        ),
    ],

    'brand' => [

        'name' => env(
            'STOREFRONT_NAME',
            env('APP_NAME', 'NextPlay Sportswear')
        ),

        'support_email' => env(
            'EMAIL_SUPPORT_ADDRESS',
            env(
                'STOREFRONT_EMAIL',
                env('MAIL_FROM_ADDRESS')
            )
        ),
    ],

];
