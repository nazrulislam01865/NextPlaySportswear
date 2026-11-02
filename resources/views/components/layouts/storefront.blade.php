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
    $organizationLogo = \App\Support\StorefrontBranding::logoUrl();
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
    <script defer src="{{ asset('js/viewer-time.js') }}?v=20261020a"></script>

    <title>{{ $title }}</title>
    <link rel="icon" href="{{ \App\Support\StorefrontBranding::faviconUrl() }}">
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
    <meta property="og:url" content="{{ $seo['og_url'] ?? $canonical }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:alt" content="{{ $seo['og_image_alt'] ?? $ogTitle }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $ogTitle }}">
    <meta name="twitter:description" content="{{ $ogDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    <meta name="twitter:image:alt" content="{{ $seo['og_image_alt'] ?? $ogTitle }}">

    <meta name="theme-color" content="#051e44">

    <x-storefront.font-assets />

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
    {{-- Generated from the centralized theme + typography sources; loaded last
         so the approved homepage rules work on existing cloud builds too. --}}
    <link rel="stylesheet" href="{{ asset('css/storefront-typography.css') }}?v=20261102content-centralized1">
    @if(request()->routeIs('checkout.*', 'order.confirmation'))
        <link rel="stylesheet" href="{{ asset('css/nextplay-checkout.css') }}?v=20261031discount-sticky1">
        <script defer src="{{ asset('js/nextplay-checkout.js') }}?v=20261031phone-compact-v5"></script>
        <script defer src="{{ asset('js/nextplay-checkout-dropdown.js') }}?v=20261031phone-compact-v5"></script>
        <link rel="stylesheet" href="{{ asset('css/nextplay-checkout-patches.css') }}?v=20261031discount-sticky1">
    @endif
    @if(request()->routeIs('quote.request'))
        <link rel="stylesheet" href="{{ asset('css/bulk-quote-product.css') }}?v=20261030g">
        <link rel="stylesheet" href="{{ asset('css/bulk-quote-redesign.css') }}?v=20261030i">
        <link rel="stylesheet" href="{{ asset('css/bulk-quote-form-system.css') }}?v=20261031icons53clear">
        <link rel="stylesheet" href="{{ asset('css/bulk-quote-enhancements.css') }}?v=20261031shippingmaster1">
        <link rel="stylesheet" href="{{ asset('css/nextplay-quote-builder.css') }}?v=20261102breadcrumb1">
    @endif
    {{-- Track Bulk Quote uses the existing theme and form tokens; its fixes must not affect other quote steps. --}}
    @if(request()->routeIs('quote.guest.track', 'quote.guest.show'))
        <link rel="stylesheet" href="{{ asset('css/nextplay-guest-quote-track.css') }}?v=20261102trackdesign1">
    @endif
    {{-- Page-only layout styles. Never override the storefront header/footer or global typography. --}}
    @if(request()->routeIs('about', 'returns', 'contact', 'terms', 'privacy'))
        <link rel="stylesheet" href="{{ asset('css/nextplay-content-pages.css') }}?v=20261102returns-nav-hover5">
    @endif
    @if(request()->routeIs('privacy'))
        <link rel="stylesheet" href="{{ asset('css/nextplay-privacy-prototype.css') }}?v=20261102privacy-terms-width2">
    @endif
    @if(request()->routeIs('shipping'))
        <link rel="stylesheet" href="{{ asset('css/nextplay-shipping-delivery-new.css') }}?v=20261102newshipping1">
    @endif
    @if(request()->routeIs('how-to-order', 'artwork-guidelines'))
        <link rel="stylesheet" href="{{ asset('css/nextplay-managed-content-pages.css') }}?v=20261102managed-content-merge1">
    @endif
    @if(request()->routeIs('size-guide', 'artwork-guidelines'))
        <link rel="stylesheet" href="{{ asset('css/nextplay-size-guide.css') }}?v=20261102size-art2">
    @endif
    {{-- One centralized layout and scroll navigation for the four admin-managed guides. --}}
    @if(request()->routeIs('shipping', 'how-to-order', 'size-guide', 'artwork-guidelines'))
        <link rel="stylesheet" href="{{ asset('css/nextplay-guide-unified.css') }}?v=20261102content-centralized1">
        <script defer src="{{ asset('js/nextplay-guide-navigation.js') }}?v=20261102single-card-hover3"></script>
    @endif
    {{-- The same sidebar/hover rules for Returns and every admin-managed guide,
         loaded after legacy page CSS to avoid competing overrides. --}}
    @if(request()->routeIs('returns', 'shipping', 'how-to-order', 'size-guide', 'artwork-guidelines'))
        <link rel="stylesheet" href="{{ asset('css/nextplay-content-sidebar-shared.css') }}?v=20261102sidebar-header-offset2">
        <script defer src="{{ asset('js/nextplay-content-sidebar-offset.js') }}?v=20261102sidebar-header-offset2"></script>
    @endif
    {{-- Single template contract for fonts, sizes, buttons, colors and borders
         across managed storefront content pages.  Values come from the central
         storefront-theme.css; no legacy per-page palettes or CTA geometries. --}}
    @if(request()->routeIs('about', 'returns', 'contact', 'terms', 'privacy', 'shipping', 'how-to-order', 'size-guide', 'artwork-guidelines', 'faq', 'payment', 'customization-guide', 'bulk-ordering', 'sustainability'))
        <link rel="stylesheet" href="{{ asset('css/nextplay-content-design-system.css') }}?v=20261102privacy-subject-select1">
    @endif
</head>
<body
    class="storefront-clean-ui"
    data-image-placeholder-url="{{ asset('images/product-placeholder.svg') }}"
    data-product-activity-url="{{ route('products.visitor-activity') }}"
>
    <a class="np-skip-link" href="#main-content">Skip to main content</a>
    <x-storefront.topbar />
    <x-storefront.header />
    @if(request()->routeIs('checkout.*', 'quote.request', 'cart.*'))
        <link rel="stylesheet" href="{{ asset('css/nextplay-sticky-summary.css') }}?v=20261031discount-sticky1">
        <script defer src="{{ asset('js/nextplay-sticky-summary.js') }}?v=20261031discount-sticky1"></script>
    @endif
    <script defer src="{{ asset('js/nextplay-cart-float.js') }}?v=20261031float-v4"></script>
    <x-storefront.toast-center />

    <main id="main-content" tabindex="-1">
        {{ $slot }}
    </main>

    <x-storefront.footer />
</body>
</html>
