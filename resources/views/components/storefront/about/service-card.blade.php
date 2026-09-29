@props([
    'icon' => null,
    'iconUrl' => null,
    'iconAlt' => '',
    'title',
    'description',
])

<article {{ $attributes->class('np-about-service-card') }}>
    @if(filled($iconUrl))
        <span class="np-about-icon np-about-service-card__icon">
            <img src="{{ $iconUrl }}" alt="{{ $iconAlt }}" loading="lazy" decoding="async" style="display:block;width:54px;height:54px;object-fit:contain;">
        </span>
    @else
        <x-storefront.about.icon :name="$icon" :size="54" class="np-about-service-card__icon" />
    @endif
    <div class="np-about-service-card__copy">
        <h3>{{ $title }}</h3>
        <p>{{ $description }}</p>
    </div>
</article>
