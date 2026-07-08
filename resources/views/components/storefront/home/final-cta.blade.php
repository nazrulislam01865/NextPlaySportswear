@props(['section' => []])

@php
    $section = is_array($section) ? $section : [];
    $text = static fn (string $key, string $fallback = ''): string => filled(data_get($section, $key)) ? (string) data_get($section, $key) : $fallback;
    $contacts = collect(data_get($section, 'items', []))->filter(fn ($item) => filled(data_get($item, 'title')))->values();
@endphp

<section class="final-cta">
    <div class="container">
        <h2>{{ $text('title', 'Ready to Build Your Team Gear?') }}</h2>
        @if(filled($text('description')))<p>{{ $text('description', 'Start with a product or send us your order details for a custom quote.') }}</p>@endif
        <div class="hero-ctas final-cta-actions">
            @if(filled($text('primary_label')))<a class="btn btn-red" href="{{ $text('primary_url', '#products') }}">{{ $text('primary_label', 'Shop Now') }}</a>@endif
            @if(filled($text('secondary_label')))<a class="btn btn-white" href="{{ $text('secondary_url', '#bulk') }}">{{ $text('secondary_label', 'Request Bulk Quote') }}</a>@endif
        </div>
        @if($contacts->isNotEmpty())
            <div class="contacts">
                @foreach($contacts as $contact)
                    <span>{{ data_get($contact, 'title') }}</span>
                @endforeach
            </div>
        @endif
    </div>
</section>
