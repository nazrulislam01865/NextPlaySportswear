@props([
    'number',
    'title',
    'description',
    'icon',
    'optional' => false,
])

<section {{ $attributes->class(['bulk-quote-section']) }}>
    <div class="bulk-quote-section-layout">
        <div class="bulk-quote-section-icon" aria-hidden="true">
            <x-storefront.bulk-quote.icon :name="$icon" />
        </div>
        <div class="bulk-quote-section-content">
            <div class="bulk-quote-section-heading">
                <h2><span>{{ $number }}.</span> {{ $title }} @if($optional)<small class="bulk-quote-optional">(optional)</small>@endif</h2>
                <p>{{ $description }}</p>
            </div>
            {{ $slot }}
        </div>
    </div>
</section>
