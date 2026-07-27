@props([
    'seo' => [],
    'structuredData' => [],
])

@php
    $siteName = config('storefront.name');
    $title = $seo['title'] ?? $siteName;
    $description = $seo['description'] ?? config('storefront.tagline');
    $robots = $seo['robots'] ?? 'index, follow';
    $canonical = $seo['canonical'] ?? url()->current();
    $ogTitle = $seo['og_title'] ?? $title;
    $ogDescription = $seo['og_description'] ?? $description;
    $configuredOgImage = (string) config('storefront.og_image', 'images/og-default.jpg');
    $defaultOgImage = preg_match('/^https?:\/\//i', $configuredOgImage)
        ? $configuredOgImage
        : asset(ltrim($configuredOgImage, '/'));
    $configuredLogo = (string) config('storefront.logo', '/images/logo.png');
    $organizationLogo = preg_match('/^https?:\/\//i', $configuredLogo)
        ? $configuredLogo
        : asset(ltrim($configuredLogo, '/'));
    $ogImage = $seo['og_image'] ?? $defaultOgImage;
    $ogType = $seo['og_type'] ?? 'website';
    $locale = str_replace('-', '_', app()->getLocale());

    $organizationSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $siteName,
        'url' => config('storefront.url'),
        'logo' => $organizationLogo,
        'contactPoint' => [
            [
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'email' => config('storefront.email'),
                'areaServed' => config('storefront.area_served', 'Worldwide'),
                'availableLanguage' => config('storefront.available_languages', ['English']),
            ],
        ],
    ];

    $websiteSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $siteName,
        'url' => config('storefront.url'),
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => route('products.index') . '?q={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ],
    ];

    $pageSchema = [
        '@context' => 'https://schema.org',
        '@type' => $seo['schema_type'] ?? 'WebPage',
        'name' => $title,
        'description' => $description,
        'url' => $canonical,
        'isPartOf' => [
            '@type' => 'WebSite',
            'name' => $siteName,
            'url' => config('storefront.url'),
        ],
    ];
@endphp

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="robots" content="{{ $robots }}">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <link rel="canonical" href="{{ $canonical }}">
    <link rel="sitemap" type="application/xml" title="Sitemap" href="{{ route('sitemap') }}">

    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:locale" content="{{ $locale }}">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:alt" content="{{ $seo['og_image_alt'] ?? $ogTitle }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $ogTitle }}">
    <meta name="twitter:description" content="{{ $ogDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    <meta name="twitter:image:alt" content="{{ $seo['og_image_alt'] ?? $ogTitle }}">

    <meta name="theme-color" content="#15345d">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">

    <script type="application/ld+json">
        @json($organizationSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    </script>

    <script type="application/ld+json">
        @json($websiteSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    </script>

    <script type="application/ld+json">
        @json($pageSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    </script>

    @foreach ($structuredData as $schema)
        <script type="application/ld+json">
            @json($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        </script>
    @endforeach

    @vite(['resources/css/storefront.css', 'resources/js/storefront.js'])
</head>
<body
    class="storefront-clean-ui"
    data-image-placeholder-url="{{ asset('images/product-placeholder.svg') }}"
    data-product-activity-url="{{ route('products.visitor-activity') }}"
>
    <a class="np-skip-link" href="#main-content">Skip to main content</a>
    <x-storefront.topbar />
    <x-storefront.header />
    <x-storefront.toast-center />

    <main id="main-content" tabindex="-1">
        {{ $slot }}
    </main>

    <x-storefront.footer />
</body>
</html>
