@props([
    'products' => [],
    'section' => [],
    'sectionId' => 'products',
    'fallbackEyebrow' => '',
    'fallbackTitle' => 'Products',
    'fallbackDescription' => '',
    'emptyMessage' => 'No active products are available yet.',
    'alternate' => false,
    'slider' => false,
    'liveRefreshUrl' => null,
    'liveRefreshSignature' => '',
    'liveRefreshInterval' => 5000,
])

@php
    $section = is_array($section) ? $section : [];
    $products = is_iterable($products) ? $products : [];
    $text = static fn (string $key, string $fallback = ''): string => filled(data_get($section, $key)) ? (string) data_get($section, $key) : $fallback;
    $sectionClasses = trim('home-product-section'.($alternate ? ' section-alt' : '').($slider ? ' home-product-section--slider' : ''));
@endphp

<section
    id="{{ $sectionId }}"
    class="{{ $sectionClasses }}"
    @if(filled($liveRefreshUrl))
        data-live-product-section
        data-live-product-refresh-url="{{ $liveRefreshUrl }}"
        data-live-product-signature="{{ $liveRefreshSignature }}"
        data-live-product-refresh-ms="{{ max(3000, (int) $liveRefreshInterval) }}"
    @endif
>
    <div class="container">
        <div class="section-head home-product-section-head">
            @if(filled($text('eyebrow', $fallbackEyebrow)))
                <span class="small-red">{{ $text('eyebrow', $fallbackEyebrow) }}</span>
            @endif
            <h2>{{ $text('title', $fallbackTitle) }}</h2>
            @if(filled($text('description', $fallbackDescription)))
                <p>{{ $text('description', $fallbackDescription) }}</p>
            @endif
        </div>

        @if($slider)
            <div
                class="home-product-slider"
                data-product-slider
                role="region"
                aria-roledescription="carousel"
                aria-label="{{ $text('title', $fallbackTitle) }} products"
            >
                <div class="home-product-slider-viewport" data-product-slider-track role="list" tabindex="0">
                    @forelse($products as $product)
                        <div class="home-product-slider-item" role="listitem">
                            <x-storefront.product-card :product="$product" />
                        </div>
                    @empty
                        <div class="home-product-slider-empty">
                            {{ $emptyMessage }}
                        </div>
                    @endforelse
                </div>

                <button
                    type="button"
                    class="home-product-slider-button home-product-slider-button--prev"
                    data-product-slider-prev
                    aria-label="Show previous products"
                    hidden
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="m15 18-6-6 6-6" />
                    </svg>
                </button>

                <button
                    type="button"
                    class="home-product-slider-button home-product-slider-button--next"
                    data-product-slider-next
                    aria-label="Show next products"
                    hidden
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="m9 18 6-6-6-6" />
                    </svg>
                </button>
            </div>
        @else
            <div class="home-product-desktop grid-4">
                @forelse($products as $product)
                    <x-storefront.product-card :product="$product" />
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500 sm:col-span-2 lg:col-span-4">
                        {{ $emptyMessage }}
                    </div>
                @endforelse
            </div>

            <div class="home-product-mobile-list" role="list">
                @forelse($products as $product)
                    <article class="home-product-mobile-item" role="listitem">
                        <a
                            href="{{ $product['url'] }}"
                            class="home-product-mobile-media"
                            aria-label="View {{ $product['title'] }}"
                        >
                            <img
                                src="{{ $product['image'] }}"
                                alt="{{ $product['alt'] }}"
                                loading="lazy"
                                decoding="async"
                                width="180"
                                height="180"
                            >
                        </a>

                        <h3 class="home-product-mobile-title">
                            <a href="{{ $product['url'] }}">{{ $product['title'] }}</a>
                        </h3>
                    </article>
                @empty
                    <p class="home-product-mobile-empty">{{ $emptyMessage }}</p>
                @endforelse
            </div>
        @endif
    </div>
</section>
