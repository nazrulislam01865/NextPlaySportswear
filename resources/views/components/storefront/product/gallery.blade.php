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
    <div class="product-gallery-frame relative bg-white p-[3px] shadow-[0_18px_48px_rgba(15,23,42,.08)]">
        <div class="relative overflow-hidden bg-white">
            @if(filled($badge))
                <span class="absolute left-4 top-4 z-10 rounded-full bg-brand-red px-3 py-2 text-[10px] font-black uppercase tracking-[.14em] text-white shadow-sm">
                    {{ $badge }}
                </span>
            @endif

            <template x-for="(image, index) in config.gallery" :key="index">
                <button
                    type="button"
                    x-show="galleryIndex === index"
                    x-transition.opacity
                    class="block w-full cursor-zoom-in bg-white"
                    @click="$dispatch('open-product-image', currentImage())"
                    :aria-label="`Enlarge ${image.alt || 'product image'}`"
                >
                    <img
                        :src="image.url"
                        :alt="image.alt"
                        class="h-[430px] w-full object-contain p-5 sm:h-[560px] sm:p-8 lg:h-[650px]"
                        width="820"
                        height="820"
                    >
                </button>
            </template>

            <button
                type="button"
                class="absolute bottom-4 left-4 grid h-11 w-11 place-items-center rounded-full border border-slate-300 bg-white/95 text-lg font-black text-slate-500 shadow-card transition hover:border-brand-blue hover:text-brand-blue"
                @click="$dispatch('open-product-image', currentImage())"
                aria-label="Enlarge product image"
            >
                ↗
            </button>
        </div>
    </div>

    @if(count($gallery) > 1)
        <div class="mt-5 flex gap-4 overflow-x-auto pb-2" aria-label="Product image gallery">
            @foreach($gallery as $index => $image)
                <button
                    type="button"
                    @click="galleryIndex={{ $index }}"
                    :class="galleryIndex === {{ $index }} ? 'border-brand-blue ring-2 ring-blue-100' : 'border-slate-200 hover:border-slate-400'"
                    class="min-w-[108px] overflow-hidden border-2 bg-white p-1.5 transition sm:min-w-[126px]"
                    aria-label="View image {{ $index + 1 }}"
                >
                    <img
                        src="{{ $image['url'] }}"
                        alt="{{ $image['alt'] ?? '' }}"
                        class="h-[104px] w-full bg-white object-contain sm:h-[120px]"
                        width="150"
                        height="150"
                        loading="lazy"
                    >
                </button>
            @endforeach
        </div>
    @endif
</div>
