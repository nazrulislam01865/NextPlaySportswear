@props(['section' => []])

@php
    $section = is_array($section) ? $section : [];
    $text = static fn (string $key, string $fallback = ''): string => filled(data_get($section, $key)) ? (string) data_get($section, $key) : $fallback;
    $items = collect(data_get($section, 'items', []))->filter(fn ($item) => filled(data_get($item, 'title')))->values();
@endphp

<section>
    <div class="container">
        <div class="section-head">
            <span class="small-red">{{ $text('eyebrow', 'Customer words') }}</span>
            <h2>{{ $text('title', 'What Teams and Customers Say') }}</h2>
            @if(filled($text('description')))<p>{{ $text('description') }}</p>@endif
        </div>
        <div class="testimonial-grid">
            @foreach($items as $testimonial)
                <article class="testimonial">
                    <div class="stars-line" aria-label="5 out of 5 stars">★★★★★</div>
                    <p>“{{ data_get($testimonial, 'description') }}”</p>
                    <div class="person"><strong>{{ data_get($testimonial, 'title') }}</strong><span>{{ data_get($testimonial, 'subtitle') }}</span></div>
                </article>
            @endforeach
        </div>
        @if(filled($text('primary_label')))
            <p class="home-center-action"><a class="btn btn-light" href="{{ $text('primary_url', route('products.index')) }}">{{ $text('primary_label', 'View Product') }}</a></p>
        @endif
    </div>
</section>
