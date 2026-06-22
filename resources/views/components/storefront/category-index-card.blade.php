@props(['category'])

<article
    class="group flex h-full flex-col overflow-hidden rounded-[14px] border border-slate-200 bg-white shadow-card transition duration-200 hover:-translate-y-1 hover:shadow-soft"
    data-tags="{{ implode(' ', $category['tags']) }}"
    x-show="filter === 'all' || $el.dataset.tags.split(' ').includes(filter)"
    x-transition.opacity.duration.150ms
>
    <a href="{{ $category['url'] }}" class="block overflow-hidden" aria-label="{{ $category['link_label'] }}">
        <img
            loading="lazy"
            src="{{ $category['image'] }}"
            alt="{{ $category['alt'] }}"
            class="h-[184px] w-full object-cover transition duration-300 group-hover:scale-[1.025] sm:h-[200px] lg:h-[184px]"
            width="700"
            height="500"
        >
    </a>

    <div class="flex flex-1 flex-col p-[17px]">
        <h3 class="text-lg font-extrabold text-brand-ink">
            <a href="{{ $category['url'] }}" class="hover:text-brand-red">{{ $category['title'] }}</a>
        </h3>

        <p class="mt-2 text-sm leading-[1.55] text-slate-500">{{ $category['description'] }}</p>

        @if ($category['best_for'])
            <div class="mt-3 border-t border-slate-100 pt-3 text-[13px] leading-5 text-slate-700">
                <strong class="mb-1 block text-[11px] font-black uppercase tracking-[.06em] text-brand-ink">Best For</strong>
                {{ $category['best_for'] }}
            </div>
        @endif

        <a href="{{ $category['url'] }}" class="mt-auto inline-flex items-center gap-2 pt-4 text-[13px] font-black uppercase tracking-[.02em] text-brand-red hover:text-brand-redDark">
            {{ $category['link_label'] }} <span aria-hidden="true">→</span>
        </a>
    </div>
</article>
