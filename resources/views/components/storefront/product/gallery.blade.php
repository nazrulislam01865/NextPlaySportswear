@props([
    'gallery' => [],
    'badge' => null,
    'social' => [],
])

@php
    $gallery = collect($gallery)
        ->filter(fn ($image) => is_array($image) && filled($image['url'] ?? null))
        ->values()
        ->all();
@endphp

<div class="np-product-gallery-column min-w-0">
    <div class="product-gallery-frame relative">
        <div class="np-product-gallery-main relative">
            <template x-for="(image, index) in config.gallery" :key="index">
                <button
                    type="button"
                    x-show="galleryIndex === index"
                    x-transition.opacity
                    class="np-product-gallery-slide block w-full cursor-zoom-in border-0 bg-transparent p-0 leading-none shadow-none"
                    style="margin:0;padding:0;border:0;border-radius:0;box-shadow:none;background:transparent;line-height:0;"
                    @click="$dispatch('open-product-image', currentImage())"
                    :aria-label="`Enlarge ${image.alt || 'product image'}`"
                >
                    <img
                        :src="image.url"
                        :alt="image.alt"
                        class="np-product-gallery-image block h-auto w-full"
                        style="display:block;width:100%;height:auto;margin:0;padding:0;border:0;border-radius:0;box-shadow:none;background:transparent;"
                        width="900"
                        height="900"
                    >
                </button>
            </template>

            <button
                type="button"
                class="np-product-wishlist-button absolute right-4 top-4 z-20 grid h-12 w-12 place-items-center rounded-full border border-slate-200 bg-white/95 text-brand-navy shadow-lg backdrop-blur transition hover:-translate-y-0.5 hover:border-brand-red hover:text-brand-red focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-blue/25 disabled:cursor-wait disabled:opacity-60 sm:right-5 sm:top-5"
                @click.stop="toggleWishlist()"
                :aria-label="wishlistLabel()"
                :title="wishlistLabel()"
                :aria-pressed="wishlisted ? 'true' : 'false'"
                :disabled="wishlistBusy"
            >
                <svg
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    :fill="wishlisted ? 'currentColor' : 'none'"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                    class="transition-transform"
                    :class="wishlisted ? 'scale-110 text-brand-red' : ''"
                >
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z"></path>
                </svg>
                <span class="sr-only" x-text="wishlistLabel()"></span>
            </button>
        </div>
    </div>

    @if(count($gallery) > 1)
        <div class="np-product-gallery-thumbnails mt-4 flex gap-3 overflow-x-auto pb-2" aria-label="Product image gallery">
            @foreach($gallery as $index => $image)
                <button
                    type="button"
                    @click="galleryIndex={{ $index }}"
                    :aria-current="galleryIndex === {{ $index }} ? 'true' : 'false'"
                    :class="galleryIndex === {{ $index }} ? 'opacity-100' : 'opacity-70 hover:opacity-100'"
                    class="np-product-gallery-thumb border-0 bg-transparent p-0 leading-none shadow-none transition-opacity focus-visible:outline-none"
                    style="margin:0;padding:0;border:0;border-radius:0;outline:0;box-shadow:none;background:transparent;line-height:0;"
                    aria-label="View image {{ $index + 1 }}"
                >
                    <img
                        src="{{ $image['url'] }}"
                        alt="{{ $image['alt'] ?? '' }}"
                        class="np-product-gallery-thumb-image block h-auto w-full"
                        style="display:block;width:100%;height:auto;margin:0;padding:0;border:0;border-radius:0;box-shadow:none;background:transparent;"
                        width="150"
                        height="150"
                        loading="lazy"
                    >
                </button>
            @endforeach
        </div>
    @endif


    <p class="sr-only" role="status" aria-live="polite" aria-atomic="true" x-text="socialStatus"></p>
</div>
