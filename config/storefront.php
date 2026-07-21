<?php

return [
    'name' => env('STOREFRONT_NAME', 'NextPlay Sportswear'),

    'tagline' => 'Custom sportswear, team uniforms, jerseys, hoodies, caps, bags, and promotional products.',

    'email' => env('STOREFRONT_EMAIL', 'support@example.com'),

    'whatsapp' => env('STOREFRONT_WHATSAPP', '+1 000 000 0000'),

    'whatsapp_url' => env('STOREFRONT_WHATSAPP_URL'),

    'social' => [
        'instagram' => env('STOREFRONT_INSTAGRAM_URL', 'https://www.instagram.com/nextplaysportswear/'),
        'facebook' => env('STOREFRONT_FACEBOOK_URL', 'https://www.facebook.com/nextplaysportswear'),
        'tiktok' => env('STOREFRONT_TIKTOK_URL', 'https://www.tiktok.com/@nextplaysportswear'),
        'youtube' => env('STOREFRONT_YOUTUBE_URL', 'https://www.youtube.com/@nextplaysportswear'),
    ],

    'url' => env('APP_URL', 'https://example.com'),

    'logo' => env('STOREFRONT_LOGO', '/images/logo.png'),

    'slider_cache_seconds' => (int) env('STOREFRONT_SLIDER_CACHE_SECONDS', 600),

    'product_cards' => [
        // Used only when a product has no product-level rating/review value yet.
        // Set STOREFRONT_PRODUCT_CARD_SHOW_DEFAULT_RATING=false to hide this fallback.
        'show_default_rating' => filter_var(env('STOREFRONT_PRODUCT_CARD_SHOW_DEFAULT_RATING', true), FILTER_VALIDATE_BOOLEAN),
        'default_rating' => (float) env('STOREFRONT_PRODUCT_CARD_DEFAULT_RATING', 4.8),
        'default_reviews_count' => (int) env('STOREFRONT_PRODUCT_CARD_DEFAULT_REVIEWS_COUNT', 23),
        'live_viewer_window_minutes' => (int) env('STOREFRONT_PRODUCT_CARD_LIVE_VIEWER_WINDOW_MINUTES', 5),
    ],
];
