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

<article class="np-product-card group flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card card-hover">
    <a href="{{ $product['url'] }}" class="np-product-square-media np-product-card-media relative block overflow-hidden bg-white" aria-label="View {{ $product['title'] }}">
        <img
            src="{{ $product['image'] }}"
            alt="{{ $product['alt'] }}"
            class="np-product-square-image transition duration-500 group-hover:scale-[1.035]"
            loading="lazy"
            width="900"
            height="900"
        >
    </a>

    <div class="flex flex-1 flex-col p-4">
        @if (filled($product['tag'] ?? null))
            <span class="{{ $tagClass }} np-product-card-tag mb-2 inline-flex w-fit rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide">
                {{ $product['tag'] }}
            </span>
        @endif

        @if ($showCategory)
            <p class="np-product-card-category text-[11px] font-black uppercase tracking-[.12em] text-slate-400">{{ $product['category'] }}</p>
        @endif

        <h3 class="np-product-card-title {{ $showCategory ? 'mt-2' : '' }} min-h-[44px] text-sm font-extrabold leading-snug text-brand-ink">
            <a href="{{ $product['url'] }}" class="transition hover:text-brand-red">
                {{ $product['title'] }}
            </a>
        </h3>

        <p class="np-product-card-summary mt-2 line-clamp-2 text-xs leading-5 text-slate-500">{{ $product['summary'] }}</p>

        <div class="np-product-card-meta mt-auto flex items-center justify-between gap-3 pt-4">
            <span class="np-product-card-price font-black text-brand-ink">{{ $product['price'] }}</span>

            <span class="np-product-card-stars inline-flex items-center gap-1 text-xs" aria-label="Rated {{ $product['rating'] }} out of 5 from {{ $product['reviews_count'] }} reviews">
                <span class="tracking-wider text-amber-500" aria-hidden="true">★★★★★</span>
                <span class="font-bold text-slate-400">({{ $product['reviews_count'] }})</span>
            </span>
        </div>

        <a href="{{ $product['url'] }}" class="np-product-card-button btn btn-light mt-4 w-full text-xs">
            View Product
        </a>
    </div>
</article>
