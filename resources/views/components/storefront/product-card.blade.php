@props([
    'product',
])

@php
    $tagClass = match ($product['tag_color'] ?? 'blue') {
        'red' => 'bg-red-50 text-brand-red',
        'navy' => 'bg-indigo-50 text-brand-navy',
        default => 'bg-blue-50 text-brand-blue',
    };
@endphp

<article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card card-hover">
    <a href="{{ $product['url'] }}" class="block">
        <img
            src="{{ $product['image'] }}"
            alt="{{ $product['alt'] }}"
            class="h-[220px] w-full object-cover"
            loading="lazy"
        >
    </a>

    <div class="p-4">
        <span class="{{ $tagClass }} inline-flex rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide">
            {{ $product['tag'] }}
        </span>

        <h3 class="mt-3 min-h-[44px] text-sm font-extrabold leading-snug text-brand-ink">
            <a href="{{ $product['url'] }}" class="hover:text-brand-red">
                {{ $product['title'] }}
            </a>
        </h3>

        <div class="mt-3 flex items-center justify-between gap-3">
            <span class="font-black text-brand-ink">
                {{ $product['price'] }}
            </span>

            <span class="text-xs tracking-widest text-amber-500">
                ★★★★★
            </span>
        </div>

        <a href="{{ $product['url'] }}" class="btn btn-light mt-4 w-full text-xs">
            View Product
        </a>
    </div>
</article>
