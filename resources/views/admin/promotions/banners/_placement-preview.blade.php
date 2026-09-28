<x-admin.section-card
    title="Placement Preview"
    description="See how the selected banner will appear around real storefront products."
>
    <div class="np-placement-preview-card">
        <h3>1. Banner at top of product list</h3>
        <div class="np-store-preview-shell">
            <div class="np-store-preview-header">
                <div class="np-store-preview-brand">NEXTPLAY <span>SPORTSWEAR</span></div>
                <div class="np-store-preview-search">Search products, kits, bags, sport...</div>
            </div>
            <div class="np-store-preview-nav"><span>Home</span><span>Shop Products</span><span>All Products</span><span>Bulk Quote</span></div>
            <div class="np-store-preview-title-row"><strong>ALL PRODUCTS</strong><span>Sort by&nbsp; Featured⌄</span></div>
            <div class="np-store-preview-body">
                <aside class="np-store-preview-filters">
                    <strong>FILTERS</strong>
                    <span>Search products</span>
                    <span>Accessories</span><span>Bags</span><span>Drinkware</span><span>Headwear</span><span>Performance Apparel</span>
                </aside>
                <div class="np-store-preview-results">
                    <div class="np-store-preview-banner" x-show="desktopPreviewUrl">
                        <img :src="desktopPreviewUrl" :alt="altText || name">
                        <span class="np-banner-live-artwork__shade" aria-hidden="true"></span>
                        <div class="np-banner-live-artwork__content np-banner-live-artwork__content--store-preview">
                            <strong x-text="heading || name || 'Banner heading'"></strong>
                            <span x-show="ctaLabel" x-text="ctaLabel"></span>
                        </div>
                    </div>
                    <div class="np-store-preview-products">
                        @forelse($previewProducts as $product)
                            <article>
                                <img src="{{ $product['image'] }}" alt="">
                                <strong>{{ \Illuminate\Support\Str::limit($product['name'], 24) }}</strong>
                            </article>
                        @empty
                            @for($i = 0; $i < 5; $i++)
                                <article class="is-skeleton"><div></div><strong>Product</strong></article>
                            @endfor
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="np-placement-preview-card">
        <h3>2. Banner after second product row</h3>
        <div class="np-store-preview-products np-store-preview-products--wide">
            @forelse($previewProducts as $product)
                <article><img src="{{ $product['image'] }}" alt=""><strong>{{ \Illuminate\Support\Str::limit($product['name'], 22) }}</strong></article>
            @empty
                @for($i = 0; $i < 5; $i++)<article class="is-skeleton"><div></div><strong>Product</strong></article>@endfor
            @endforelse
        </div>
        <div class="np-store-preview-banner np-store-preview-banner--after" x-show="desktopPreviewUrl">
            <img :src="desktopPreviewUrl" :alt="altText || name">
            <span class="np-banner-live-artwork__shade" aria-hidden="true"></span>
            <div class="np-banner-live-artwork__content np-banner-live-artwork__content--store-preview">
                <strong x-text="heading || name || 'Banner heading'"></strong>
                <span x-show="ctaLabel" x-text="ctaLabel"></span>
            </div>
        </div>
    </div>
</x-admin.section-card>
