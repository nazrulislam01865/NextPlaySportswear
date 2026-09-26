@if($products->count())
    <div class="np-product-listing-grid np-product-listing-grid--three grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($products as $product)
            <x-storefront.product-card :product="$product" />
        @endforeach
    </div>
    <div class="mt-7">
        {{ $products->links('pagination.nextplay', ['itemName' => 'product']) }}
    </div>
@else
    <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-14 text-center">
        <h3 class="font-display text-3xl font-bold uppercase">No matching products</h3>
        <p class="mt-2 text-slate-500">Change the selected filters or clear all filters.</p>
        <a class="btn btn-primary mt-5" href="{{ $category['url'] }}">Clear Filters</a>
    </div>
@endif
