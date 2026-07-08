@props(['categories' => [], 'section' => []])

@php
    $section = is_array($section) ? $section : [];
    $text = static fn (string $key, string $fallback = ''): string => filled(data_get($section, $key)) ? (string) data_get($section, $key) : $fallback;
@endphp

@if(! empty($categories))
    <section>
        <div class="container">
            <div class="section-head">
                <span class="small-red">{{ $text('eyebrow', 'Most requested') }}</span>
                <h2>{{ $text('title', 'Popular Custom Sportswear Categories') }}</h2>
                @if(filled($text('description')))<p>{{ $text('description', 'Our most requested sport categories and subcategories for teams, events, and fan gear.') }}</p>@endif
            </div>
            <div class="grid-4">
                @foreach($categories as $category)
                    <x-storefront.category-card :category="$category" />
                @endforeach
            </div>
        </div>
    </section>
@endif
