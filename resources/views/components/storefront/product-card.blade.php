@props([
    'product',
    'showCategory' => false,
])

@php
    $tagClass = match ($product['tag_color'] ?? 'blue') {
        'red' => 'bg-red-50 text-brand-red',
        'navy' => 'bg-indigo-50 text-brand-navy',
        default => 'bg-blue-50 text-brand-blue',
    };
@endphp

<article class="group flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card card-hover">
    <a href="{{ $product['url'] }}" class="relative block overflow-hidden bg-slate-100" aria-label="View {{ $product['title'] }}">
        <img
            src="{{ $product['image'] }}"
            alt="{{ $product['alt'] }}"
            class="h-[220px] w-full object-cover transition duration-500 group-hover:scale-[1.035]"
            loading="lazy"
            width="900"
            height="700"
        >
        @if (filled($product['tag'] ?? null))
            <span class="{{ $tagClass }} absolute left-3 top-3 inline-flex rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide shadow-sm">
                {{ $product['tag'] }}
            </span>
        @endif
    </a>

    <div class="flex flex-1 flex-col p-4">
        @if ($showCategory)
            <p class="text-[11px] font-black uppercase tracking-[.12em] text-slate-400">{{ $product['category'] }}</p>
        @endif

        <h3 class="{{ $showCategory ? 'mt-2' : '' }} min-h-[44px] text-sm font-extrabold leading-snug text-brand-ink">
            <a href="{{ $product['url'] }}" class="transition hover:text-brand-red">
                {{ $product['title'] }}
            </a>
        </h3>

        <p class="mt-2 line-clamp-2 text-xs leading-5 text-slate-500">{{ $product['summary'] }}</p>

        <div class="mt-auto flex items-center justify-between gap-3 pt-4">
            <span class="font-black text-brand-ink">{{ $product['price'] }}</span>

            <span class="inline-flex items-center gap-1 text-xs" aria-label="Rated {{ $product['rating'] }} out of 5 from {{ $product['reviews_count'] }} reviews">
                <span class="tracking-wider text-amber-500" aria-hidden="true">★★★★★</span>
                <span class="font-bold text-slate-400">({{ $product['reviews_count'] }})</span>
            </span>
        </div>

        <a href="{{ $product['url'] }}" class="btn btn-light mt-4 w-full text-xs">
            View Product
        </a>
    </div>
</article>
