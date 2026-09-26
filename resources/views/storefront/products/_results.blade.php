@if ($products->count())
    <div class="np-product-listing-grid np-product-listing-grid--three grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($products as $product)
            <x-storefront.product-card :product="$product" />
        @endforeach
    </div>
    <div class="mt-7">{{ $products->links('pagination.nextplay', ['itemName' => 'product']) }}</div>
@else
    <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-card">
        <h3 class="font-display text-3xl font-bold uppercase text-brand-ink">No products found</h3>
        <p class="mt-2 text-slate-600">Try another search term or remove one of the selected filters.</p>
        <a href="{{ route('products.index') }}" class="btn btn-primary mt-5">Clear Filters</a>
    </div>
@endif
