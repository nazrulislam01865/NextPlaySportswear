@props(['categories' => [], 'section' => []])

@php
    $section = is_array($section) ? $section : [];
    $text = static fn (string $key, string $fallback = ''): string => filled(data_get($section, $key)) ? (string) data_get($section, $key) : $fallback;
@endphp

<section id="gear">
    <div class="container">
        <div class="section-head">
            <span class="small-red">{{ $text('eyebrow', 'Popular gear') }}</span>
            <h2>{{ $text('title', 'Best-Selling Team Gear') }}</h2>
            @if(filled($text('description')))<p>{{ $text('description') }}</p>@endif
        </div>
        <div class="gear-list">
            @forelse($categories as $category)
                <a class="gear-card" href="{{ $category['url'] }}" aria-label="Browse {{ $category['short_title'] }}">
                    <img loading="lazy" decoding="async" src="{{ $category['image'] }}" alt="{{ $category['alt'] }}" class="np-category-square-image" width="184" height="184">
                    <div>
                        <h3>{{ $category['short_title'] }}</h3>
                        <p>{{ $category['description'] }}</p>
                        <span class="link-red">{{ $category['link_label'] }}</span>
                    </div>
                </a>
            @empty
                <p>No featured catalog categories are available yet.</p>
            @endforelse
        </div>
    </div>
</section>
