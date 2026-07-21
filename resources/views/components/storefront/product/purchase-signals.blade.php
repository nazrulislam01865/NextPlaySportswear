@props(['product'])

@php
    $rating = $product['rating'] ?? null;
    $reviewsCount = $product['reviews_count'] ?? null;
    $usingDefaultRating = false;

    // Keep the title-area rating in sync with product cards and the full
    // reviews section. Until product-level reviews are published, use the
    // configured storefront demo rating requested for the prototype.
    if ((! is_numeric($rating) || ! is_numeric($reviewsCount) || (int) $reviewsCount <= 0)
        && (bool) config('storefront.product_cards.show_default_rating', true)) {
        $rating = config('storefront.product_cards.default_rating', 4.8);
        $reviewsCount = config('storefront.product_cards.default_reviews_count', 23);
        $usingDefaultRating = true;
    }

    $hasReviews = is_numeric($rating) && is_numeric($reviewsCount) && (int) $reviewsCount > 0;
    $ratingLabel = $hasReviews ? number_format((float) $rating, 1) : null;
    $filledStars = $hasReviews ? max(0, min(5, (int) round((float) $rating))) : 0;
    $ratingStars = $hasReviews ? str_repeat('★', $filledStars).str_repeat('☆', 5 - $filledStars) : '';
    $activityLabel = trim((string) ($product['shopper_activity'] ?? ''));
    $favoritesCount = is_numeric($product['favorites_count'] ?? null) && (int) $product['favorites_count'] > 0
        ? (int) $product['favorites_count']
        : null;
@endphp

<div class="np-product-signals" aria-label="Product rating and customer activity">
    @if($hasReviews)
        <a class="np-product-signals__rating" href="#product-reviews" aria-label="Rated {{ $ratingLabel }} out of 5 from {{ $reviewsCount }} reviews">
            <span class="np-product-signals__stars" aria-hidden="true">{{ $ratingStars }}</span>
            <strong>{{ $ratingLabel }}</strong>
            <span>
                from {{ number_format((int) $reviewsCount) }}
                {{ $usingDefaultRating ? 'verified ' : '' }}review{{ (int) $reviewsCount === 1 ? '' : 's' }}
            </span>
        </a>
    @endif

    <div class="np-product-signals__actions">
        <button
            type="button"
            class="np-product-signal-button"
            data-product-detail-favorite="{{ $product['id'] ?? '' }}"
            aria-pressed="false"
        >
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z" /></svg>
            <span data-product-detail-favorite-label>Save</span>
            @if($favoritesCount)
                <strong>{{ number_format($favoritesCount) }}</strong>
            @endif
        </button>

        <button
            type="button"
            class="np-product-signal-button"
            data-product-detail-share
            data-share-title="{{ $product['title'] }}"
            data-share-text="{{ $product['summary'] ?? '' }}"
            data-share-url="{{ $product['url'] ?? request()->fullUrl() }}"
        >
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 16a3 3 0 0 0-2.4 1.2l-6.8-3.4a3.1 3.1 0 0 0 0-3.6l6.8-3.4A3 3 0 1 0 15 5c0 .2 0 .4.1.6L8.2 9a3 3 0 1 0 0 6l6.9 3.4A3 3 0 1 0 18 16Z" /></svg>
            <span data-product-detail-share-label>Share</span>
        </button>

        <div
            class="np-product-signal-button np-product-signal-button--activity"
            data-product-detail-activity
            data-product-id="{{ $product['id'] ?? '' }}"
            aria-live="polite"
            @if($activityLabel === '') hidden @endif
        >
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5c5.2 0 9.4 4.4 10.7 6.1.4.5.4 1.2 0 1.8C21.4 14.6 17.2 19 12 19S2.6 14.6 1.3 12.9a1.5 1.5 0 0 1 0-1.8C2.6 9.4 6.8 5 12 5Zm0 2C7.8 7 4.3 10.4 3.2 12c1.1 1.6 4.6 5 8.8 5s7.7-3.4 8.8-5C19.7 10.4 16.2 7 12 7Zm0 2.2a2.8 2.8 0 1 1 0 5.6 2.8 2.8 0 0 1 0-5.6Z" /></svg>
            <span data-product-detail-activity-label>{{ $activityLabel }}</span>
        </div>
    </div>
</div>

@once
    <style>
        .np-product-signals {
            margin-top: 1rem;
        }

        .np-product-signals__rating {
            display: inline-flex;
            align-items: center;
            flex-wrap: wrap;
            gap: .45rem;
            color: #0b2450;
            font-size: .92rem;
            line-height: 1.35;
            text-decoration: none;
        }

        .np-product-signals__rating:hover {
            color: #e91d33;
        }

        .np-product-signals__stars {
            color: #f5a400;
            font-size: 1.22rem;
            letter-spacing: .045em;
            line-height: 1;
        }

        .np-product-signals__rating strong {
            color: #061744;
            font-weight: 750;
        }

        .np-product-signals__rating span:last-child {
            color: #2563eb;
            font-weight: 700;
        }

        .np-product-signals__actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: .65rem;
            margin-top: .9rem;
        }

        .np-product-signal-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            min-height: 2.55rem;
            padding: .55rem .9rem;
            border: 1px solid #dbe5f0;
            border-radius: .7rem;
            background: #fff;
            color: #0b2450;
            font-size: .82rem;
            font-weight: 700;
            line-height: 1.2;
            transition: border-color .16s ease, color .16s ease, background .16s ease, transform .16s ease;
        }

        button.np-product-signal-button:hover,
        button.np-product-signal-button:focus-visible {
            border-color: #19b7d7;
            color: #061744;
            background: #f5fbff;
            transform: translateY(-1px);
        }

        .np-product-signal-button svg {
            width: 1.05rem;
            height: 1.05rem;
            flex: 0 0 auto;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .np-product-signal-button strong {
            font-weight: 800;
        }

        .np-product-signal-button.is-saved {
            border-color: rgba(233, 29, 51, .3);
            background: rgba(233, 29, 51, .06);
            color: #e91d33;
        }

        .np-product-signal-button.is-saved svg {
            fill: currentColor;
        }

        .np-product-signal-button--activity {
            color: #334155;
        }

        .np-product-signal-button--activity.is-live {
            border-color: rgba(25, 183, 215, .32);
            background: #f4fcff;
            color: #0e7490;
        }

        .np-product-signal-button--activity[hidden] {
            display: none !important;
        }

        @media (max-width: 640px) {
            .np-product-signals__actions {
                gap: .5rem;
            }

            .np-product-signal-button {
                min-height: 2.4rem;
                padding: .5rem .72rem;
                font-size: .77rem;
            }
        }
    </style>
@endonce
