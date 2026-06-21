@props([
    'eyebrow' => null,
    'title',
    'description' => null,
])

<div class="mx-auto mb-9 max-w-3xl text-center">
    @if($eyebrow)
        <p class="mb-2 text-xs font-black uppercase tracking-[.18em] text-brand-red">
            {{ $eyebrow }}
        </p>
    @endif

    <h2 class="font-display text-3xl font-bold uppercase leading-tight tracking-tight text-brand-ink sm:text-4xl">
        {{ $title }}
    </h2>

    @if($description)
        <p class="mx-auto mt-3 max-w-2xl text-sm text-slate-500 sm:text-base">
            {{ $description }}
        </p>
    @endif
</div>
