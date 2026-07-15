@props([
    'products' => [],
    'section' => [],
    'sectionId' => 'products',
    'fallbackEyebrow' => '',
    'fallbackTitle' => 'Products',
    'fallbackDescription' => '',
    'emptyMessage' => 'No active products are available yet.',
    'alternate' => false,
])

@php
    $section = is_array($section) ? $section : [];
    $text = static fn (string $key, string $fallback = ''): string => filled(data_get($section, $key)) ? (string) data_get($section, $key) : $fallback;
    $sectionClasses = trim('home-product-section'.($alternate ? ' section-alt' : ''));
@endphp

<section id="{{ $sectionId }}" class="{{ $sectionClasses }}">
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
    </div>
</section>
