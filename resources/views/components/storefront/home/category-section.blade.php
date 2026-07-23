@props(['categories' => [], 'section' => []])

@php
    $section = is_array($section) ? $section : [];
    $text = static fn (string $key, string $fallback = ''): string => filled(data_get($section, $key)) ? (string) data_get($section, $key) : $fallback;
@endphp

<section id="categories">
    <div class="container">
        <div class="section-head">
            <span class="small-red">{{ $text('eyebrow', 'Find it fast') }}</span>
            <h2>{{ $text('title', 'What Are You Looking For?') }}</h2>
            @if(filled($text('description')))
                <p>{{ $text('description', 'Start with an admin-managed category and find the right product faster.') }}</p>
            @endif
        </div>
        <div class="home-featured-category-grid home-category-card-grid home-category-card-grid--featured">
            @forelse($categories as $category)
                <x-storefront.category-card :category="$category" />
            @empty
                <a class="image-card" href="{{ route('categories.index') }}" aria-label="Browse Categories">
                    <div class="card-body">
                        <h3>Categories are being prepared</h3>
                        <p>Publish featured categories from the admin catalog to display them here.</p>
                        <span class="link-red">Browse Categories</span>
                    </div>
                </a>
            @endforelse
        </div>
    </div>
</section>
