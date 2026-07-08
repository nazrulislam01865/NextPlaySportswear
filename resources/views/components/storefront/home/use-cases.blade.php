@props(['section' => []])

@php
    $section = is_array($section) ? $section : [];
    $text = static fn (string $key, string $fallback = ''): string => filled(data_get($section, $key)) ? (string) data_get($section, $key) : $fallback;
    $fallbackImages = ['football.webp', 'training.webp', 'bags.webp'];
    $items = collect(data_get($section, 'items', []))->filter(fn ($item) => filled(data_get($item, 'title')))->values();
@endphp

<section>
    <div class="container">
        <div class="section-head">
            <span class="small-red">{{ $text('eyebrow', 'Built for use') }}</span>
            <h2>{{ $text('title', 'Made for Play, Practice, Travel, and Team Events') }}</h2>
            @if(filled($text('description')))<p>{{ $text('description', 'Choose products based on how they will be used.') }}</p>@endif
        </div>
        <div class="use-grid">
            @foreach($items as $case)
                @php($image = data_get($case, 'image') ?: ($fallbackImages[$loop->index] ?? 'football.webp'))
                <article class="use-card">
                    <img loading="lazy" decoding="async" src="{{ asset('storage/storefront/home/'.$image) }}" alt="{{ data_get($case, 'image_alt', data_get($case, 'title')) }}" width="700" height="420">
                    <div><h3>{{ data_get($case, 'title') }}</h3><p>{{ data_get($case, 'description') }}</p></div>
                </article>
            @endforeach
        </div>
    </div>
</section>
