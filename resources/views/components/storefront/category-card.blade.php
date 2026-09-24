@props([
    'category',
])

@php
    $productCount = (int) ($category['product_count'] ?? 0);
    $productLabel = $productCount === 1 ? 'product' : 'products';
    $hierarchyLabel = $category['hierarchy_label'] ?? $category['parent_name'] ?? null;
@endphp

<article class="np-home-category-card np-square-card group flex h-full flex-col overflow-hidden border border-slate-200 bg-white font-sans shadow-card card-hover">
    <a href="{{ $category['url'] }}" class="np-home-category-card__media np-category-square-media relative block overflow-hidden" aria-label="Browse {{ $category['title'] }}">
        <img
            src="{{ $category['image'] }}"
            alt="{{ $category['alt'] }}"
            class="np-category-square-image transition duration-500 group-hover:scale-[1.04]"
            loading="lazy"
            width="800"
            height="800"
        >
    </a>

    <div class="np-home-category-card__body flex flex-1 flex-col p-4">
        @if ($hierarchyLabel)
            <div class="mb-1 truncate text-[11px] font-semibold leading-4 text-slate-400" title="{{ $hierarchyLabel }}">
                {{ $hierarchyLabel }}
            </div>
        @endif

        <h3 class="np-home-category-card__title text-base font-extrabold text-brand-ink">
            <a href="{{ $category['url'] }}" class="transition hover:text-brand-red">
                {{ $category['title'] }}
            </a>
        </h3>

        <span class="np-home-category-card__count" aria-label="{{ $productCount }} {{ $productLabel }}">
            {{ number_format($productCount) }} {{ $productLabel }}
        </span>

        <p class="np-home-category-card__description mt-2 line-clamp-3 text-sm leading-6 text-slate-500">
            {{ $category['description'] }}
        </p>

        <a href="{{ $category['url'] }}" class="np-home-category-card__link mt-auto inline-flex items-center gap-1 pt-4 text-xs font-black uppercase tracking-wide text-brand-red">
            View Products
            <span aria-hidden="true">→</span>
        </a>
    </div>
</article>
