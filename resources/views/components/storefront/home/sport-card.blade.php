@props([
    'sport',
])

@php
    $sport = is_array($sport) ? $sport : [];
    $title = trim((string) ($sport['title'] ?? $sport['short_title'] ?? 'Sport'));
    $count = max(0, (int) ($sport['product_count'] ?? 0));
    $countLabel = number_format($count).' '.($count === 1 ? 'item' : 'items');
    $url = $sport['url'] ?? route('categories.index');
@endphp

<a
    class="np-shop-sport-card np-square-card"
    href="{{ $url }}"
    aria-label="Browse {{ $title }}, {{ $countLabel }}"
>
    <span class="np-shop-sport-card__media">
        <img
            loading="lazy"
            decoding="async"
            src="{{ $sport['image'] ?? asset('images/category-placeholder.svg') }}"
            alt="{{ $sport['alt'] ?? $title }}"
            width="420"
            height="420"
        >
    </span>

    <span class="np-shop-sport-card__body">
        <strong class="np-shop-sport-card__title">{{ $title }}</strong>
        <span class="np-shop-sport-card__meta">
            <span>{{ $countLabel }}</span>
            <svg class="np-shop-sport-card__arrow" viewBox="0 0 30 30" aria-hidden="true">
                <path d="M5 15h18M17 8l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </span>
    </span>
</a>
