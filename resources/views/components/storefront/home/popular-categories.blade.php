@props([
    'categories' => [],
])

@if(! empty($categories))
    <section>
        <div class="container">
            <div class="section-head">
                <span class="small-red">Most requested</span>
                <h2>Popular Custom Sportswear Categories</h2>
                <p>Our most requested sport categories and subcategories for teams, events, and fan gear.</p>
            </div>
            <div class="grid-4">
                @foreach($categories as $category)
                    <x-storefront.category-card :category="$category" />
                @endforeach
            </div>
        </div>
    </section>
@endif
