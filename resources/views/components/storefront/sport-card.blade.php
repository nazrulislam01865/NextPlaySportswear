@props([
    'sport',
])

<article class="overflow-hidden rounded-2xl border border-slate-200 bg-white text-center shadow-card card-hover">
    <a href="{{ $sport['url'] }}" class="block">
        <img
            src="{{ $sport['image'] }}"
            alt="{{ $sport['alt'] }}"
            class="np-category-square-image"
            loading="lazy"
            width="500"
            height="500"
        >
    </a>

    <div class="p-4">
        <h3 class="font-display text-xl font-bold uppercase text-brand-ink">
            {{ $sport['title'] }}
        </h3>

        <a href="{{ $sport['url'] }}" class="mt-2 inline-flex text-[11px] font-black uppercase tracking-wide text-brand-red">
            Shop {{ $sport['title'] }} Gear
        </a>
    </div>
</article>
