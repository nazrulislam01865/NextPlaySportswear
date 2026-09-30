@props([
    'gallery' => [],
    'badge' => null,
])

@php
    $gallery = collect($gallery)
        ->filter(fn ($image) => is_array($image) && filled($image['url'] ?? null))
        ->values()
        ->all();
    $galleryCount = count($gallery);
@endphp

<div class="np-product-gallery-column">
    <div class="product-gallery-frame np-product-gallery-stage">
        <div class="np-product-gallery-main" x-init="$el.dataset.galleryReady = 'true'">
            @foreach($gallery as $index => $image)
                <button
                    type="button"
                    class="np-product-gallery-slide"
                    :class="galleryIndex === {{ $index }} ? 'is-active' : 'is-inactive'"
                    :aria-hidden="galleryIndex === {{ $index }} ? 'false' : 'true'"
                    :tabindex="galleryIndex === {{ $index }} ? 0 : -1"
                    @click="$dispatch('open-product-image', config.gallery[{{ $index }}])"
                    aria-label="Enlarge {{ $image['alt'] ?? 'product image' }}"
                >
                    <img
                        src="{{ $image['url'] }}"
                        alt="{{ $image['alt'] ?? '' }}"
                        class="np-product-gallery-image"
                        width="900"
                        height="900"
                        decoding="async"
                        loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                        @load="markGalleryImageReady({{ $index }}, $event)"
                        x-on:error="markGalleryImageError({{ $index }})"
                    >
                </button>
            @endforeach

            @if(filled($badge))
                <span class="np-product-gallery-sale-badge">{{ $badge }}</span>
            @endif

            <button
                type="button"
                class="np-product-wishlist-button"
                @click.stop="toggleWishlist()"
                :aria-label="wishlistLabel()"
                :title="wishlistLabel()"
                :aria-pressed="wishlisted ? 'true' : 'false'"
                :disabled="wishlistBusy"
            >
                <svg viewBox="0 0 24 24" :fill="wishlisted ? 'currentColor' : 'none'" aria-hidden="true">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z"></path>
                </svg>
            </button>

            @if($galleryCount > 1)
                <button
                    type="button"
                    class="np-product-gallery-arrow np-product-gallery-arrow--previous"
                    @click.stop="selectGalleryImage((galleryIndex - 1 + {{ $galleryCount }}) % {{ $galleryCount }})"
                    aria-label="Previous product image"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                </button>

                <button
                    type="button"
                    class="np-product-gallery-arrow np-product-gallery-arrow--next"
                    @click.stop="selectGalleryImage((galleryIndex + 1) % {{ $galleryCount }})"
                    aria-label="Next product image"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </button>
            @endif

            <button
                type="button"
                class="np-product-gallery-zoom"
                @click.stop="$dispatch('open-product-image', currentImage())"
                aria-label="Zoom product image"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/><path d="M11 8v6M8 11h6"/></svg>
            </button>
        </div>
    </div>

    @if($galleryCount > 1)
        <div class="np-product-gallery-thumbnails" aria-label="Product image gallery">
            @foreach($gallery as $index => $image)
                <button
                    type="button"
                    @click="selectGalleryImage({{ $index }})"
                    :aria-current="galleryIndex === {{ $index }} ? 'true' : 'false'"
                    :class="galleryIndex === {{ $index }} ? 'is-active' : ''"
                    class="np-product-gallery-thumb"
                    aria-label="View image {{ $index + 1 }}"
                >
                    <img
                        src="{{ $image['url'] }}"
                        alt="{{ $image['alt'] ?? '' }}"
                        class="np-product-gallery-thumb-image"
                        width="150"
                        height="150"
                        loading="lazy"
                        decoding="async"
                    >
                </button>
            @endforeach
        </div>
    @endif

    <p class="sr-only" role="status" aria-live="polite" aria-atomic="true" x-text="socialStatus"></p>
</div>
