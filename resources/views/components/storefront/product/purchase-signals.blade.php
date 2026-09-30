@props(['product' => []])

@php
    $rating = $product['rating'] ?? null;
    $reviewsCount = $product['reviews_count'] ?? null;
    $hasReviews = (bool) ($product['has_reviews'] ?? false) && is_numeric($rating) && is_numeric($reviewsCount) && (int) $reviewsCount > 0;
    $ratingLabel = $hasReviews ? number_format((float) $rating, 1) : '';
    $ratingStars = $hasReviews ? str_repeat('★', max(1, min(5, (int) round((float) $rating)))) : '';
    $activityLabel = trim((string) ($product['shopper_activity'] ?? ''));
    $sampleAvailable = (bool) data_get($product, 'sample.available', false);
@endphp

<div class="np-product-signals" aria-label="Product rating and customer actions">
    <div class="np-product-signals__meta">
        @if($hasReviews)
            <a class="np-product-signals__rating" href="#product-reviews" aria-label="Rated {{ $ratingLabel }} out of 5 from {{ $reviewsCount }} reviews">
                <span class="np-product-signals__stars" aria-hidden="true">{{ $ratingStars }}</span>
                <strong>{{ $ratingLabel }}</strong>
                <span>({{ number_format((int) $reviewsCount) }} review{{ (int) $reviewsCount === 1 ? '' : 's' }})</span>
            </a>
        @endif

        <div
            class="np-product-signal-activity"
            data-product-detail-activity
            data-product-id="{{ $product['id'] ?? '' }}"
            @if($activityLabel === '') hidden @endif
        >
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5c5.2 0 9.4 4.4 10.7 6.1.4.5.4 1.2 0 1.8C21.4 14.6 17.2 19 12 19S2.6 14.6 1.3 12.9a1.5 1.5 0 0 1 0-1.8C2.6 9.4 6.8 5 12 5Zm0 2C7.8 7 4.3 10.4 3.2 12c1.1 1.6 4.6 5 8.8 5s7.7-3.4 8.8-5C19.7 10.4 16.2 7 12 7Zm0 2.2a2.8 2.8 0 1 1 0 5.6 2.8 2.8 0 0 1 0-5.6Z" /></svg>
            <span data-product-detail-activity-label>{{ $activityLabel }}</span>
        </div>

        @if(filled($product['sku'] ?? null))
            <div class="np-product-sku-inline" aria-label="Product SKU">
                <strong>SKU: {{ $product['sku'] }}</strong>
                <button type="button" @click="copySku(@js($product['sku']))" :title="skuCopied ? 'Copied' : 'Copy SKU'" aria-label="Copy SKU">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="8" width="11" height="11" rx="2"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/></svg>
                    <span x-text="skuCopied ? 'Copied' : 'Copy'">Copy</span>
                </button>
            </div>
        @endif
    </div>

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
        </button>

        <div class="np-product-share-wrapper" @click.outside="shareOpen = false" @keydown.escape.window="shareOpen = false">
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
                class="np-product-share-menu"
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

        @if($sampleAvailable)
            <button
                type="button"
                class="np-product-signal-button"
                @click="startCustomizing(); $nextTick(() => document.getElementById('sample-order')?.scrollIntoView({ behavior: 'smooth', block: 'center' }))"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h10v4l-2 3v8a3 3 0 0 1-3 3h0a3 3 0 0 1-3-3v-8L7 7V3Z"/><path d="M9 7h6"/></svg>
                <span>Request a Sample</span>
            </button>
        @endif
    </div>

    <p
        x-cloak
        x-show="!socialConfig.authenticated && wishlisted"
        class="np-product-saved-note"
    >
        Saved on this browser. <a :href="socialConfig.login_url">Sign in to keep it across devices.</a>
    </p>
</div>
