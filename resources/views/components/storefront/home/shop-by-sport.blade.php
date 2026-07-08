@props(['sports' => [], 'section' => []])

@php
    $section = is_array($section) ? $section : [];
    $text = static fn (string $key, string $fallback = ''): string => filled(data_get($section, $key)) ? (string) data_get($section, $key) : $fallback;
@endphp

<section id="sports" class="section-alt">
    <div class="container">
        <div class="section-head">
            <span class="small-red">{{ $text('eyebrow', 'Find your sport') }}</span>
            <h2>{{ $text('title', 'Shop by Sport') }}</h2>
            @if(filled($text('description')))<p>{{ $text('description', 'Active sport categories from the admin catalog appear here automatically.') }}</p>@endif
        </div>
        <div class="grid-6">
            @forelse($sports as $sport)
                <a class="sport-card" href="{{ $sport['url'] }}" aria-label="Shop {{ $sport['short_title'] }} Gear">
                    <img loading="lazy" decoding="async" src="{{ $sport['image'] }}" alt="{{ $sport['alt'] }}" class="np-category-square-image" width="420" height="420">
                    <h3>{{ $sport['title'] }}</h3>
                    <span class="link-red">Shop {{ $sport['short_title'] }} Gear</span>
                </a>
            @empty
                <p>No active sport categories are available yet.</p>
            @endforelse
        </div>
    </div>
</section>
