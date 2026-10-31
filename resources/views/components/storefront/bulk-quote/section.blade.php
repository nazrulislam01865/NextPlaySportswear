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
            @php
                $iconSlot = match ($icon) {
                    'box' => 'product',
                    'user' => 'contact',
                    'truck', 'shipping' => 'delivery',
                    'attachment' => 'attachments',
                    default => null,
                };
                $customIcon = $iconSlot ? \App\Support\BulkQuoteIcons::url($iconSlot) : null;
            @endphp
            @if($customIcon)
                <img src="{{ $customIcon }}" alt="" width="52" height="52" class="bulk-quote-uploaded-icon">
            @else
                <x-storefront.bulk-quote.icon :name="$icon" />
            @endif
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
