@if($products->count())
    @if(!empty($categoryTopBanner))
        <div class="mb-4">
            <x-storefront.sale-banner :banner="$categoryTopBanner" />
        </div>
    @endif
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
        <h3 class="nps-type-h3 np-category-empty-title">No matching products</h3>
        <p class="np-category-section-description mt-2">Change the selected filters or clear all filters.</p>
        <a class="btn btn-primary np-category-action mt-5" href="{{ $category['url'] }}">Clear Filters</a>
    </div>
@endif
