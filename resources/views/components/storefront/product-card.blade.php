@props([
    'product',
    'showCategory' => false,
])

@php
    $tagClass = match ($product['tag_color'] ?? 'blue') {
        'red' => 'np-product-card-badge--red',
        'navy' => 'np-product-card-badge--navy',
        default => 'np-product-card-badge--blue',
    };

    $productUrl = (string) ($product['url'] ?? '#');
    $configureUrl = $productUrl === '#' ? '#' : $productUrl.'#configure-product';
    $categoryLabel = trim((string) (($product['category'] ?? '') ?: ($product['sport'] ?? '')));
    $allCustomizationOptions = collect($product['customization_options'] ?? [])
        ->filter(fn ($option) => filled($option))
        ->values();
    $customizationOptions = $allCustomizationOptions->take(2)->values();
    $rating = $product['rating'] ?? null;
    $reviewsCount = $product['reviews_count'] ?? null;

    if ((! is_numeric($rating) || ! is_numeric($reviewsCount) || (int) $reviewsCount <= 0)
        && (bool) config('storefront.product_cards.show_default_rating', true)) {
        $rating = config('storefront.product_cards.default_rating', 4.8);
        $reviewsCount = config('storefront.product_cards.default_reviews_count', 23);
    }

    $hasReviews = is_numeric($rating)
        && is_numeric($reviewsCount)
        && (int) $reviewsCount > 0;
    $ratingLabel = $hasReviews ? number_format((float) $rating, 1) : null;
    $ratingStarCount = $hasReviews ? max(0, min(5, (int) round((float) $rating))) : 0;
    $ratingStars = $hasReviews ? str_repeat('★', $ratingStarCount).str_repeat('☆', 5 - $ratingStarCount) : '';
    $activityLabel = trim((string) ($product['shopper_activity'] ?? ''));
    $productId = (int) ($product['id'] ?? 0);
    $wishlistEndpoint = $productId > 0
        ? route('wishlist.products.update', ['product' => $productId])
        : '';
    $wishlistPrice = (float) ($product['base_price'] ?? 0);
    $wishlistCurrency = (string) ($product['currency'] ?? 'USD');
@endphp

<article class="np-product-card np-product-card--nextplay group flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card card-hover" data-product-card data-product-id="{{ $product['id'] ?? '' }}">
    <div class="np-product-card-media-wrap relative">
        <a href="{{ $productUrl }}" class="np-product-square-media np-product-card-media relative block overflow-hidden" aria-label="View {{ $product['title'] }}">
            <img
                src="{{ $product['image'] }}"
                alt="{{ $product['alt'] }}"
                class="np-product-square-image transition duration-500 group-hover:scale-[1.035]"
                loading="lazy"
                width="900"
                height="900"
            >
        </a>

        @if (filled($product['tag'] ?? null))
            <span class="np-product-card-badge {{ $tagClass }}">
                {{ $product['tag'] }}
            </span>
        @endif

        <button
            type="button"
            class="np-product-card-favorite"
            aria-label="Add {{ $product['title'] }} to wishlist"
            aria-pressed="false"
            title="Add to wishlist"
            data-product-favorite="{{ $productId }}"
            data-wishlist-endpoint="{{ $wishlistEndpoint }}"
            data-wishlist-product-slug="{{ $product['slug'] ?? '' }}"
            data-wishlist-product-title="{{ $product['title'] ?? '' }}"
            data-wishlist-product-summary="{{ $product['summary'] ?? '' }}"
            data-wishlist-product-url="{{ $productUrl }}"
            data-wishlist-product-image="{{ $product['image'] ?? '' }}"
            data-wishlist-product-price="{{ $wishlistPrice }}"
            data-wishlist-product-currency="{{ $wishlistCurrency }}"
        >
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z" />
            </svg>
        </button>
    </div>

    <div class="np-product-card-body flex flex-1 flex-col">
        @if ($categoryLabel !== '')
            <p class="np-product-card-category">{{ $categoryLabel }}</p>
        @endif

        <h3 class="np-product-card-title">
            <a href="{{ $productUrl }}" class="transition hover:text-brand-red">
                {{ $product['title'] }}
            </a>
        </h3>

        @if ($customizationOptions->isNotEmpty())
            <div class="np-product-card-options" aria-label="Customization options">
                @foreach ($customizationOptions as $option)
                    <span>
                        <svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M7.7 13.3 4.4 10l-1.2 1.2 4.5 4.5 9-9-1.2-1.2-7.8 7.8Z" /></svg>
                        {{ $option }}
                    </span>
                @endforeach
            </div>
        @endif

        @if ($hasReviews)
            <div class="np-product-card-rating" aria-label="Rated {{ $ratingLabel }} out of 5 from {{ $reviewsCount }} reviews">
                <span class="np-product-card-rating-stars" aria-hidden="true">{{ $ratingStars }}</span>
                <span class="np-product-card-rating-value">{{ $ratingLabel }}</span>
                <span class="np-product-card-rating-count">({{ $reviewsCount }} review{{ (int) $reviewsCount === 1 ? '' : 's' }})</span>
            </div>
        @endif

        <p class="np-product-card-activity" data-product-card-activity aria-live="polite" @if ($activityLabel === '') hidden @endif>
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 5c5.2 0 9.4 4.4 10.7 6.1.4.5.4 1.2 0 1.8C21.4 14.6 17.2 19 12 19S2.6 14.6 1.3 12.9a1.5 1.5 0 0 1 0-1.8C2.6 9.4 6.8 5 12 5Zm0 2C7.8 7 4.3 10.4 3.2 12c1.1 1.6 4.6 5 8.8 5s7.7-3.4 8.8-5C19.7 10.4 16.2 7 12 7Zm0 2.2a2.8 2.8 0 1 1 0 5.6 2.8 2.8 0 0 1 0-5.6Z" /></svg>
            <span data-product-card-activity-label>{{ $activityLabel }}</span>
        </p>

        <div class="np-product-card-divider" aria-hidden="true"></div>

        <div class="np-product-card-price-row">
            <span class="np-product-card-price">{{ $product['price'] }}</span>
            @if (!empty($product['has_bulk_pricing']))
                <span class="np-product-card-bulk">Bulk quotes available</span>
            @endif
        </div>

        <a href="{{ $configureUrl }}" class="np-product-card-button">
            Customize &amp; Order
        </a>

        <a href="{{ $productUrl }}" class="np-product-card-details-link">
            View product details <span aria-hidden="true">→</span>
        </a>
    </div>
</article>
