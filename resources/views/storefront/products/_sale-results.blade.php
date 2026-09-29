@if ($saleProducts->count())
    @php($middleIndices = (array) ($saleMiddleInsertionIndices ?? []))
    <div class="np-product-listing-grid np-product-listing-grid--three np-sale-products-grid grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($saleProducts as $product)
            <x-storefront.product-card :product="$product" />
            @php($productIndex = $loop->iteration)

            @if($saleMiddleBanner ?? null)
                @foreach(range(1, 5) as $columns)
                    @if(($middleIndices[$columns] ?? null) === $productIndex)
                        <div class="np-promotion-midpoint-banner np-promotion-midpoint-banner--cols-{{ $columns }}">
                            <x-storefront.sale-banner :banner="$saleMiddleBanner" />
                        </div>
                    @endif
                @endforeach
            @endif
        @endforeach
    </div>

    @if($saleProducts->hasPages())
        <div class="mt-8">
            {{ $saleProducts->links('pagination.nextplay', ['itemName' => 'product']) }}
        </div>
    @endif
@else
    <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-card">
        <h3 class="font-display text-3xl font-bold uppercase text-brand-ink">No sale products found</h3>
        <p class="mt-2 text-slate-600">Try another search term or remove one of the selected filters.</p>
        <a href="{{ route('sale.index') }}" class="btn btn-primary mt-5">Clear Filters</a>
    </div>
@endif
