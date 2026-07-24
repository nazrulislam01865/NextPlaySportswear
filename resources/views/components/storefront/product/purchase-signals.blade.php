@props(['product'])

@php
    $rating = $product['rating'] ?? null;
    $reviewsCount = $product['reviews_count'] ?? null;
    $usingDefaultRating = false;

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
            :class="wishlisted ? 'is-saved' : ''"
            @click="toggleWishlist()"
            :aria-label="wishlistLabel()"
            :title="wishlistLabel()"
            :aria-pressed="wishlisted ? 'true' : 'false'"
            :disabled="wishlistBusy"
        >
            <svg viewBox="0 0 24 24" :fill="wishlisted ? 'currentColor' : 'none'" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z" /></svg>
            <span x-text="wishlisted ? 'Saved' : 'Save'">Save</span>
            <strong x-show="productFavoritesCount > 0" x-text="Number(productFavoritesCount).toLocaleString()"></strong>
        </button>

        <div
            class="np-product-share-wrapper relative"
            @click.outside="shareOpen = false"
            @keydown.escape.window="shareOpen = false"
        >
            <button
                type="button"
                class="np-product-signal-button"
                @click="shareProduct()"
                :aria-expanded="shareOpen ? 'true' : 'false'"
                :aria-controls="shareMenuId()"
                aria-label="Share this product"
                :disabled="shareBusy"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 16a3 3 0 0 0-2.4 1.2l-6.8-3.4a3.1 3.1 0 0 0 0-3.6l6.8-3.4A3 3 0 1 0 15 5c0 .2 0 .4.1.6L8.2 9a3 3 0 1 0 0 6l6.9 3.4A3 3 0 1 0 18 16Z" /></svg>
                <span>Share</span>
            </button>

            <div
                x-cloak
                x-show="shareOpen"
                x-transition.origin.top.left
                :id="shareMenuId()"
                role="menu"
                aria-label="Share this product"
                class="np-product-share-menu absolute left-0 top-[calc(100%+.65rem)] z-50 w-64 overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl"
            >
                <button type="button" role="menuitem" @click="copyProductLink()">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                    Copy link
                </button>
                <button type="button" role="menuitem" @click="shareThrough('whatsapp')"><span aria-hidden="true">W</span>WhatsApp</button>
                <button type="button" role="menuitem" @click="shareThrough('facebook')"><span aria-hidden="true">f</span>Facebook</button>
                <button type="button" role="menuitem" @click="shareThrough('x')"><span aria-hidden="true">X</span>X</button>
                <button type="button" role="menuitem" @click="shareThrough('email')">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"></rect><path d="m22 7-10 5L2 7"></path></svg>
                    Email
                </button>
            </div>
        </div>

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

    <p
        x-cloak
        x-show="!socialConfig.authenticated && wishlisted"
        class="mt-3 text-xs font-semibold leading-5 text-slate-500"
    >
        Saved on this browser.
        <a :href="socialConfig.login_url" class="font-black text-brand-blue underline decoration-brand-blue/30 underline-offset-2 hover:text-brand-red focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-blue">
            Sign in to keep it across devices.
        </a>
    </p>
</div>

@once
    <style>
        .np-product-signals {
            display: block;
            margin-top: .72rem;
        }
        .np-product-signals__rating {
            display: inline-flex;
            align-items: center;
            flex-wrap: wrap;
            gap: .28rem;
            color: #0b2450;
            font-size: .8rem;
            line-height: 1.2;
            text-decoration: none;
        }
        .np-product-signals__rating:hover { color: #e91d33; }
        .np-product-signals__stars {
            color: #f5a400;
            font-size: 1.05rem;
            letter-spacing: .015em;
            line-height: 1;
        }
        .np-product-signals__rating strong {
            color: #061744;
            font-size: .82rem;
            font-weight: 850;
        }
        .np-product-signals__rating span:last-child {
            color: #2563eb;
            font-size: .8rem;
            font-weight: 750;
        }
        .np-product-signals__actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: .42rem;
            margin-top: .55rem;
        }
        .np-product-signal-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .38rem;
            min-height: 2.22rem;
            padding: .4rem .68rem;
            border: 1px solid #dbe5f0;
            border-radius: .68rem;
            background: #fff;
            color: #0b2450;
            font-size: .75rem;
            font-weight: 800;
            line-height: 1.15;
            white-space: nowrap;
            transition: border-color .16s ease, color .16s ease, background .16s ease, transform .16s ease;
        }
        button.np-product-signal-button:hover, button.np-product-signal-button:focus-visible { border-color: #19b7d7; color: #061744; background: #f5fbff; transform: translateY(-1px); }
        button.np-product-signal-button:focus-visible, .np-product-share-menu button:focus-visible { outline: 3px solid rgba(24, 92, 170, .28); outline-offset: 2px; }
        button.np-product-signal-button:disabled { cursor: wait; opacity: .6; transform: none; }
        .np-product-signal-button svg {
            width: .92rem;
            height: .92rem;
            flex: 0 0 auto;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .np-product-signal-button strong { font-weight: 850; }
        .np-product-signal-button.is-saved { border-color: rgba(233, 29, 51, .3); background: rgba(233, 29, 51, .06); color: #e91d33; }
        .np-product-signal-button.is-saved svg { fill: currentColor; }
        .np-product-signal-button--activity {
            max-width: min(100%, 15.5rem);
            color: #334155;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .np-product-signal-button--activity span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .np-product-signal-button--activity.is-live { border-color: rgba(25, 183, 215, .32); background: #f4fcff; color: #0e7490; }
        .np-product-signal-button--activity[hidden] { display: none !important; }
        .np-product-signals > p {
            margin-top: .4rem !important;
        }
        .np-product-share-menu button { display: flex; width: 100%; align-items: center; gap: .7rem; border-radius: .7rem; padding: .65rem .7rem; text-align: left; font-size: .82rem; font-weight: 700; color: #334155; transition: background-color .18s ease, color .18s ease; }
        .np-product-share-menu button:hover { background: #f1f5f9; color: #15345d; }
        .np-product-share-menu button svg, .np-product-share-menu button > span { width: 1rem; height: 1rem; flex: 0 0 1rem; display: inline-grid; place-items: center; font-weight: 900; }
        @media (max-width: 900px) {
            .np-product-signals__actions { gap: .38rem; }
            .np-product-signal-button--activity { max-width: min(100%, 15rem); }
        }
        @media (max-width: 640px) {
            .np-product-signals { margin-top: .62rem; }
            .np-product-signals__rating { gap: .22rem; font-size: .74rem; }
            .np-product-signals__stars { font-size: .96rem; }
            .np-product-signals__rating strong,
            .np-product-signals__rating span:last-child { font-size: .75rem; }
            .np-product-signals__actions { margin-top: .5rem; gap: .34rem; }
            .np-product-signal-button { min-height: 2.08rem; padding: .36rem .58rem; font-size: .71rem; }
            .np-product-signal-button svg { width: .86rem; height: .86rem; }
            .np-product-signal-button--activity { max-width: 100%; }
        }
    </style>
@endonce
