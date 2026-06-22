@props(['sport'])

<article class="group relative min-h-[190px] overflow-hidden rounded-[14px] bg-brand-dark text-white shadow-card">
    <img
        loading="lazy"
        src="{{ $sport['image'] }}"
        alt="{{ $sport['alt'] }}"
        class="absolute inset-0 h-full w-full object-cover opacity-[.58] transition duration-300 group-hover:scale-[1.04] group-hover:opacity-[.46]"
        width="500"
        height="500"
    >
    <div class="absolute inset-0 bg-gradient-to-b from-brand-dark/15 to-brand-dark/95"></div>
    <div class="absolute inset-x-[14px] bottom-[14px] z-10">
        <h3 class="font-display text-[22px] font-bold uppercase leading-none">{{ $sport['title'] }}</h3>
        <p class="mt-2 text-xs leading-5 text-white/90">{{ $sport['description'] }}</p>
        <a href="{{ $sport['url'] }}" class="mt-2 inline-flex text-[11px] font-black uppercase text-white">
            {{ $sport['link_label'] }} <span class="ml-1 text-brand-red" aria-hidden="true">→</span>
        </a>
    </div>
</article>
