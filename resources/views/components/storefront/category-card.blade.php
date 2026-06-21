@props([
    'category',
])

<article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card card-hover">
    <a href="{{ $category['url'] }}" class="block">
        <img
            src="{{ $category['image'] }}"
            alt="{{ $category['alt'] }}"
            class="h-[210px] w-full object-cover"
            loading="lazy"
        >
    </a>

    <div class="p-4">
        <h3 class="text-base font-extrabold text-brand-ink">
            <a href="{{ $category['url'] }}" class="hover:text-brand-red">
                {{ $category['title'] }}
            </a>
        </h3>

        <p class="mt-2 text-sm text-slate-500">
            {{ $category['description'] }}
        </p>

        <a href="{{ $category['url'] }}" class="mt-4 inline-flex text-xs font-black uppercase tracking-wide text-brand-red">
            {{ $category['link_label'] }}
        </a>
    </div>
</article>
