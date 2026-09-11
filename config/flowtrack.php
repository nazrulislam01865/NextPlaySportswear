<?php

return [
    /*
    |--------------------------------------------------------------------------
    | NextPlay -> FlowTrack integration
    |--------------------------------------------------------------------------
    |
    | Orders and bulk quote requests are sent from the Laravel backend. The
    | shared token belongs only in the two server-side .env files.
    |
    */
    'enabled' => filter_var(env('FLOWTRACK_INTEGRATION_ENABLED', false), FILTER_VALIDATE_BOOL),

    'base_url' => rtrim((string) env('FLOWTRACK_BASE_URL', 'http://127.0.0.1:8001'), '/'),
    'token' => env('FLOWTRACK_INTEGRATION_TOKEN'),

    'endpoints' => [
        'health' => '/api/integrations/nextplay/health',
        'orders' => '/api/integrations/nextplay/orders',
        'inquiry_health' => '/api/integrations/nextplay/inquiries/health',
        'inquiries' => '/api/integrations/nextplay/inquiries',
    ],

    'http' => [
        'connect_timeout' => max(1, (int) env('FLOWTRACK_CONNECT_TIMEOUT', 2)),
        'timeout' => max(2, (int) env('FLOWTRACK_REQUEST_TIMEOUT', 10)),
    ],

    /*
    | Local development: keep this false for immediate, easy-to-see syncing.
    | Production: set FLOWTRACK_QUEUE_ENABLED=true and run a queue worker.
    */
    'queue' => [
        'enabled' => filter_var(env('FLOWTRACK_QUEUE_ENABLED', false), FILTER_VALIDATE_BOOL),
        'connection' => env('FLOWTRACK_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database')),
        'name' => env('FLOWTRACK_QUEUE_NAME', 'integrations'),
        'tries' => max(1, (int) env('FLOWTRACK_QUEUE_TRIES', 5)),
        'timeout' => max(10, (int) env('FLOWTRACK_QUEUE_TIMEOUT', 30)),
        'backoff' => array_values(array_filter(array_map(
            static fn (string $seconds): int => max(1, (int) trim($seconds)),
            explode(',', (string) env('FLOWTRACK_QUEUE_BACKOFF', '10,60,300,900,1800')),
        ))),
    ],
];
