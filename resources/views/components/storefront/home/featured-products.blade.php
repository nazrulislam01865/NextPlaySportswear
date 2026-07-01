@props(['products' => []])

<section id="products" class="section-alt">
    <div class="container">
        <div class="section-head">
            <span class="small-red">Shop online</span>
            <h2>Featured Products</h2>
            <p>Products marked as featured by the admin appear here automatically.</p>
        </div>
        <div class="grid-4">
            @forelse($products as $product)
                <article class="product-card">
                    <img loading="lazy" decoding="async" src="{{ $product['image'] }}" alt="{{ $product['alt'] }}" width="650" height="450">
                    <div class="product-info">
                        @if($product['tag'])
                            <span class="tag {{ $product['tag_color'] ?? '' }}">{{ $product['tag'] }}</span>
                        @endif
                        <h3>{{ $product['title'] }}</h3>
                        <div class="price-row"><span class="price">{{ $product['price'] }}</span><span class="stars">★★★★★</span></div>
                        <a class="btn btn-light" href="{{ $product['url'] }}">View Product</a>
                    </div>
                </article>
            @empty
                <p>No active featured products are available yet.</p>
            @endforelse
        </div>
    </div>
</section>
