@props([
    'item' => null,
    'guest' => false,
])

@php
    $item = is_array($item) ? $item : [];
    $isGuestTemplate = (bool) $guest;
    $productKey = $isGuestTemplate ? '' : (string) ($item['id'] ?? '');
    $title = $isGuestTemplate ? '' : trim((string) ($item['title'] ?? 'Saved product'));
    $url = $isGuestTemplate ? '#' : (string) ($item['url'] ?? '#');
    $image = $isGuestTemplate ? asset('images/product-placeholder.svg') : (string) ($item['image'] ?? asset('images/product-placeholder.svg'));
    $alt = $isGuestTemplate ? '' : (string) ($item['alt'] ?? $title);
    $category = $isGuestTemplate ? '' : trim((string) ($item['category'] ?? ''));
    $price = $isGuestTemplate ? 0.0 : max(0, (float) ($item['price'] ?? 0));
    $priceAvailable = ! $isGuestTemplate && (bool) ($item['price_available'] ?? ($price > 0));
    $currency = $isGuestTemplate ? 'USD' : strtoupper((string) ($item['currency'] ?? 'USD'));
    $priceLabel = $currency === 'USD'
        ? '$'.number_format($price, 2)
        : $currency.' '.number_format($price, 2);
@endphp

<article
    class="np-product-card np-product-card--nextplay np-product-card--canonical np-wishlist-card"
    data-wishlist-item
    @if($productKey !== '') data-product-key="{{ $productKey }}" @endif
    @unless($isGuestTemplate)
        data-wishlist-saved-at="{{ $item['saved_at'] ?? '' }}"
        data-wishlist-sort-price="{{ $price }}"
        data-wishlist-sort-title="{{ $title }}"
    @endunless
>
    <a
        href="{{ $url }}"
        class="np-product-square-media np-product-card-media np-wishlist-card-media"
        aria-label="{{ $isGuestTemplate ? 'View saved product' : 'View '.$title }}"
        data-wishlist-product-link
    >
        <img
            src="{{ $image }}"
            alt="{{ $alt }}"
            class="np-product-square-image np-wishlist-card-image"
            loading="lazy"
            decoding="async"
            width="900"
            height="900"
            data-wishlist-product-image
        >
    </a>

    <div class="np-product-card-body np-wishlist-card-body">
        <p
            class="np-product-card-category np-wishlist-card-category {{ $category === '' ? 'invisible' : '' }}"
            data-wishlist-product-category
        >{{ $category !== '' ? $category : 'Product' }}</p>

        <h2 class="np-product-card-title np-wishlist-card-title">
            <a href="{{ $url }}" data-wishlist-product-title>{{ $title }}</a>
        </h2>

        <div class="np-product-card-divider np-wishlist-card-divider" aria-hidden="true"></div>

        <p class="np-wishlist-card-price" aria-live="polite">
            <span data-wishlist-price-known class="{{ $priceAvailable ? '' : 'hidden' }}">
                <span class="np-wishlist-card-price-prefix">From</span>
                <strong data-wishlist-product-price>{{ $priceAvailable ? $priceLabel : '' }}</strong>
                <span class="np-wishlist-card-price-suffix">each</span>
            </span>
            <span data-wishlist-price-unknown class="{{ $priceAvailable ? 'hidden' : '' }}">
                See price on product page
            </span>
        </p>

        <div class="np-wishlist-card-actions">
            <a href="{{ $url }}" class="btn btn-navy" data-wishlist-view-product>
                View Product
            </a>

            <button
                type="button"
                class="btn btn-white np-wishlist-remove-button"
                data-wishlist-remove
                data-product-key="{{ $productKey }}"
                data-endpoint="{{ $isGuestTemplate ? '' : ($item['remove_endpoint'] ?? '') }}"
                aria-label="{{ $isGuestTemplate ? 'Remove saved product from wishlist' : 'Remove '.$title.' from wishlist' }}"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 6h18"></path>
                    <path d="M8 6V4h8v2"></path>
                    <path d="M19 6l-1 14H6L5 6"></path>
                    <path d="M10 11v5M14 11v5"></path>
                </svg>
                Remove
            </button>
        </div>
    </div>
</article>
