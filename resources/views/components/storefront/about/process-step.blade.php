@props([
    'number',
    'icon' => null,
    'iconUrl' => null,
    'iconAlt' => '',
    'title',
    'description',
    'last' => false,
])

<div class="np-about-process-step-wrap">
    <article {{ $attributes->class('np-about-process-step') }}>
        <span class="np-about-process-step__number" aria-hidden="true">{{ $number }}</span>
        @if(filled($iconUrl))
            <span class="np-about-icon np-about-process-step__icon">
                <img src="{{ $iconUrl }}" alt="{{ $iconAlt }}" loading="lazy" decoding="async" style="display:block;width:42px;height:42px;object-fit:contain;">
            </span>
        @else
            <x-storefront.about.icon :name="$icon" :size="42" class="np-about-process-step__icon" />
        @endif
        <h3>{{ $title }}</h3>
        <p>{{ $description }}</p>
    </article>

    @unless($last)
        <span class="np-about-process-arrow" aria-hidden="true">
            <x-storefront.about.icon name="chevron-right" :size="28" />
        </span>
    @endunless
</div>
