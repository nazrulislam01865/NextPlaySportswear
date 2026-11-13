<?php

return [
    // Fail-safe toggles for a staged rollout. Calendar revisions migration is required when index is enabled.
    'holiday_index_enabled' => env('NEXTPLAY_HOLIDAY_INDEX_ENABLED', true),
    'holiday_shadow_compare' => env('NEXTPLAY_HOLIDAY_SHADOW_COMPARE', false),
    'holiday_async_recalculation' => env('NEXTPLAY_HOLIDAY_ASYNC_RECALC', true),
    'holiday_queue' => env('NEXTPLAY_HOLIDAY_QUEUE', 'default'),
    'holiday_batch_size' => max(25, min(250, (int) env('NEXTPLAY_HOLIDAY_BATCH_SIZE', 150))),
];
