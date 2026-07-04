@props([
    'gallery' => [],
    'badge' => null,
])

@php
    $gallery = collect($gallery)
        ->filter(fn ($image) => is_array($image) && filled($image['url'] ?? null))
        ->values()
        ->all();
@endphp

<div class="min-w-0">
    <div class="product-gallery-frame relative overflow-hidden bg-white">
        <div class="np-product-gallery-main relative overflow-hidden bg-white">
            <template x-for="(image, index) in config.gallery" :key="index">
                <button
                    type="button"
                    x-show="galleryIndex === index"
                    x-transition.opacity
                    class="np-product-gallery-slide block h-full w-full cursor-zoom-in bg-white"
                    @click="$dispatch('open-product-image', currentImage())"
                    :aria-label="`Enlarge ${image.alt || 'product image'}`"
                >
                    <img
                        :src="image.url"
                        :alt="image.alt"
                        class="np-product-gallery-image w-full object-contain"
                        width="900"
                        height="900"
                    >
                </button>
            </template>

        </div>
    </div>

    @if(count($gallery) > 1)
        <div class="mt-5 flex gap-4 overflow-x-auto pb-2" aria-label="Product image gallery">
            @foreach($gallery as $index => $image)
                <button
                    type="button"
                    @click="galleryIndex={{ $index }}"
                    :class="galleryIndex === {{ $index }} ? 'border-brand-blue ring-2 ring-blue-100' : 'border-slate-200 hover:border-slate-400'"
                    class="np-product-square-media min-w-[108px] overflow-hidden border-2 bg-white p-1.5 transition sm:min-w-[126px]"
                    aria-label="View image {{ $index + 1 }}"
                >
                    <img
                        src="{{ $image['url'] }}"
                        alt="{{ $image['alt'] ?? '' }}"
                        class="np-product-square-image bg-white"
                        width="150"
                        height="150"
                        loading="lazy"
                    >
                </button>
            @endforeach
        </div>
    @endif
</div>
