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
];
