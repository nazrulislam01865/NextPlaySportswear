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

    'critical' => [

        // Password reset is user-blocking and security-critical. It is
        // queued by default so valid and unknown reset requests have similar
        // response behavior and SMTP latency cannot reveal account existence.
        // Set true only if you explicitly accept that timing/reliability tradeoff.
        'password_reset_sync' => filter_var(
            env('TRANSACTIONAL_EMAIL_PASSWORD_RESET_SYNC', false),
            FILTER_VALIDATE_BOOLEAN
        ),
    ],

    'queue' => [

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
