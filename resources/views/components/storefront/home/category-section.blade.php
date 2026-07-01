@props(['categories' => []])

<section id="categories">
    <div class="container">
        <div class="section-head">
            <span class="small-red">Find it fast</span>
            <h2>What Are You Looking For?</h2>
            <p>Start with an admin-managed category and find the right product faster.</p>
        </div>
        <div class="grid-3">
            @forelse($categories as $category)
                <a class="image-card" href="{{ $category['url'] }}" aria-label="Browse {{ $category['title'] }}">
                    <img loading="lazy" decoding="async" src="{{ $category['image'] }}" alt="{{ $category['alt'] }}" width="650" height="450">
                    <div class="card-body">
                        <h3>{{ $category['title'] }}</h3>
                        <p>{{ $category['description'] }}</p>
                        <span class="link-red">{{ $category['link_label'] }}</span>
                    </div>
                </a>
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
