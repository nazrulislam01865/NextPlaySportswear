@props(['categories' => [], 'section' => []])

@php
    $section = is_array($section) ? $section : [];
    $text = static fn (string $key, string $fallback = ''): string => filled(data_get($section, $key)) ? (string) data_get($section, $key) : $fallback;
@endphp

<section id="categories">
    <div class="container">
        <x-storefront.home.section-heading
            class="section-head"
            :eyebrow="$text('eyebrow', 'Find it fast')"
            :title="$text('title', 'What Are You Looking For?')"
            :description="filled($text('description')) ? $text('description', 'Start with an admin-managed category and find the right product faster.') : null"
        />
        <div class="np-shared-category-card-grid home-featured-category-grid home-category-card-grid home-category-card-grid--featured">
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
