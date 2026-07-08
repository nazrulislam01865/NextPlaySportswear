@props(['categories' => []])

<section id="categories">
    <div class="container">
        <div class="section-head">
            <span class="small-red">Find it fast</span>
            <h2>What Are You Looking For?</h2>
            <p>Start with an admin-managed category and find the right product faster.</p>
        </div>
        <div class="home-featured-category-grid">
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
