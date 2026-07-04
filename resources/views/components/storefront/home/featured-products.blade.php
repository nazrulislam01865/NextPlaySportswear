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
                <x-storefront.product-card :product="$product" />
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500 sm:col-span-2 lg:col-span-4">
                    No active featured products are available yet.
                </div>
            @endforelse
        </div>
    </div>
</section>
