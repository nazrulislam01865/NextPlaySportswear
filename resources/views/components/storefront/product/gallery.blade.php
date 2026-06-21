@props([
    'product',
])

@php
    $gallery = $product['gallery'] ?? [$product['image']];
@endphp

<div class="sticky top-32" x-data="{ activeImage: @js($gallery[0]) }">
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card">
        <img :src="activeImage" alt="{{ $product['alt'] }}" class="h-[420px] w-full object-cover lg:h-[560px]">
    </div>

    <div class="mt-4 grid grid-cols-3 gap-3">
        @foreach ($gallery as $image)
            <button type="button" class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-1 transition hover:border-brand-red" @click="activeImage = @js($image)">
                <img src="{{ $image }}" alt="{{ $product['short_title'] }} image {{ $loop->iteration }}" class="h-24 w-full rounded-xl object-cover" loading="lazy">
            </button>
        @endforeach
    </div>
</div>
