@props([
    'eyebrow' => null,
    'title',
    'description' => null,
])

<div class="mx-auto mb-9 max-w-3xl text-center">
    @if($eyebrow)
        <p class="np-section-heading__eyebrow mb-2 uppercase tracking-[.18em] text-brand-red">
            {{ $eyebrow }}
        </p>
    @endif

    <h2 class="np-section-heading__title font-display uppercase tracking-tight text-brand-ink">
        {{ $title }}
    </h2>

    @if($description)
        <p class="np-section-heading__description mx-auto mt-3 max-w-2xl text-slate-500">
            {{ $description }}
        </p>
    @endif
</div>
