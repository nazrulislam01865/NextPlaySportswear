@props([
    'number',
    'title',
    'description',
    'icon',
])

<section {{ $attributes->class(['bulk-quote-section']) }}>
    <div class="bulk-quote-section-layout">
        <div class="bulk-quote-section-icon" aria-hidden="true">
            <x-storefront.bulk-quote.icon :name="$icon" />
        </div>
        <div class="bulk-quote-section-content">
            <div class="bulk-quote-section-heading">
                <h2><span>{{ $number }}.</span> {{ $title }}</h2>
                <p>{{ $description }}</p>
            </div>
            {{ $slot }}
        </div>
    </div>
</section>
