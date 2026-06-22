@props(['gallery'])
<div class="min-w-0">
    <div class="relative overflow-hidden rounded-[28px] border border-slate-200 bg-gradient-to-br from-white to-slate-100 shadow-hero">
        <template x-for="(image,index) in config.gallery" :key="index">
            <img x-show="galleryIndex === index" x-transition.opacity :src="image.url" :alt="image.alt" class="aspect-[1/1.06] w-full object-cover" width="760" height="805">
        </template>
        <span class="absolute left-4 top-4 rounded-full bg-brand-red px-3 py-2 text-[10px] font-black uppercase tracking-[.14em] text-white">{{ $attributes->get('badge', 'Customizable') }}</span>
        <button type="button" class="absolute bottom-4 right-4 grid h-11 w-11 place-items-center rounded-xl border border-slate-200 bg-white/95 text-brand-navy shadow-card" @click="$dispatch('open-product-image', currentImage())" aria-label="Enlarge product image">⌕</button>
    </div>
    <div class="mt-4 grid grid-cols-4 gap-3 sm:grid-cols-5" aria-label="Product image gallery">
        @foreach($gallery as $index => $image)
            <button type="button" @click="galleryIndex={{ $index }}" :class="galleryIndex === {{ $index }} ? 'border-brand-red ring-2 ring-red-100' : 'border-slate-200'" class="overflow-hidden rounded-2xl border-2 bg-white p-1 transition" aria-label="View image {{ $index + 1 }}">
                <img src="{{ $image['url'] }}" alt="" class="aspect-square w-full rounded-xl object-cover" width="140" height="140">
            </button>
        @endforeach
    </div>
</div>
