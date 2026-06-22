@props([
    'category',
])

<article class="group flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card card-hover">
    <a href="{{ $category['url'] }}" class="relative block overflow-hidden" aria-label="Browse {{ $category['title'] }}">
        <img
            src="{{ $category['image'] }}"
            alt="{{ $category['alt'] }}"
            class="h-[210px] w-full object-cover transition duration-500 group-hover:scale-[1.04]"
            loading="lazy"
            width="800"
            height="600"
        >
        @isset($category['product_count'])
            <span class="absolute bottom-3 left-3 rounded-full bg-slate-950/75 px-3 py-1 text-[10px] font-black uppercase tracking-wide text-white backdrop-blur-sm">
                {{ $category['product_count'] }} product{{ $category['product_count'] === 1 ? '' : 's' }}
            </span>
        @endisset
    </a>

    <div class="flex flex-1 flex-col p-4">
        <h3 class="text-base font-extrabold text-brand-ink">
            <a href="{{ $category['url'] }}" class="transition hover:text-brand-red">
                {{ $category['title'] }}
            </a>
        </h3>

        <p class="mt-2 line-clamp-3 text-sm leading-6 text-slate-500">
            {{ $category['description'] }}
        </p>

        <a href="{{ $category['url'] }}" class="mt-auto inline-flex items-center gap-1 pt-4 text-xs font-black uppercase tracking-wide text-brand-red">
            {{ $category['link_label'] }}
            <span aria-hidden="true">→</span>
        </a>
    </div>
</article>
