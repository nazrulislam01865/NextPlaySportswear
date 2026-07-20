@props(['sport'])

@php
    $description = trim((string) ($sport['description'] ?? ''));
@endphp

<article class="np-sport-index-card group relative overflow-hidden rounded-[18px] bg-brand-dark text-white shadow-card">
    <img
        loading="lazy"
        src="{{ $sport['image'] }}"
        alt="{{ $sport['alt'] }}"
        class="absolute inset-0 h-full w-full object-cover opacity-[.58] transition duration-300 group-hover:scale-[1.04] group-hover:opacity-[.48]"
        width="500"
        height="500"
    >
    <div class="absolute inset-0 bg-gradient-to-b from-brand-dark/10 via-brand-dark/35 to-brand-dark/95"></div>

    <div class="relative z-10 flex h-full min-h-[260px] flex-col justify-end p-5 sm:p-6">
        <h3 class="np-sport-card-title font-display text-[clamp(24px,2.8vw,32px)] font-bold uppercase leading-[.95] text-white">
            {{ $sport['title'] }}
        </h3>

        @if ($description !== '')
            <p class="np-sport-card-description mt-3 text-[14px] leading-6 text-white/90 sm:text-[15px]">
                {{ $description }}
            </p>
        @endif

        <a href="{{ $sport['url'] }}" class="mt-4 inline-flex w-fit items-center text-[12px] font-black uppercase tracking-[.02em] text-white transition group-hover:text-white">
            {{ $sport['link_label'] }} <span class="ml-1.5 text-brand-red" aria-hidden="true">→</span>
        </a>
    </div>
</article>
