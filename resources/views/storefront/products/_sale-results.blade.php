@php
    $visibleCampaignSections = collect($campaignSections ?? [])
        ->filter(fn (array $section): bool => collect($section['products'] ?? [])->isNotEmpty())
        ->values();
@endphp

@if ($visibleCampaignSections->isNotEmpty())
    <div class="space-y-10 lg:space-y-12">
        @foreach ($visibleCampaignSections as $section)
            @php
                $campaign = $section['campaign'];
                $campaignProducts = collect($section['products'] ?? []);
                $primaryBanner = $section['primary_banner'] ?? null;
            @endphp

            <section class="np-sale-campaign-section" aria-labelledby="sale-campaign-{{ $campaign['id'] }}">
                @if($primaryBanner)
                    <div class="mb-5">
                        <x-storefront.sale-banner :banner="$primaryBanner" />
                    </div>
                    <h3 id="sale-campaign-{{ $campaign['id'] }}" class="sr-only">{{ $campaign['name'] }}</h3>
                @else
                    <div class="mb-5 border-b border-slate-200 pb-3">
                        <h3 id="sale-campaign-{{ $campaign['id'] }}" class="font-display text-2xl font-bold uppercase tracking-tight text-brand-ink">
                            {{ $campaign['name'] }}
                        </h3>
                    </div>
                @endif

                <div class="np-product-listing-grid np-product-listing-grid--three grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($campaignProducts as $product)
                        <x-storefront.product-card :product="$product" />
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>

    @if(($salePaginator ?? null)?->hasPages())
        <div class="mt-8">
            {{ $salePaginator->links('pagination.nextplay', ['itemName' => 'product']) }}
        </div>
    @endif
@else
    <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-card">
        <h3 class="font-display text-3xl font-bold uppercase text-brand-ink">No sale products found</h3>
        <p class="mt-2 text-slate-600">Try another search term or remove one of the selected filters.</p>
        <a href="{{ route('sale.index') }}" class="btn btn-primary mt-5">Clear Filters</a>
    </div>
@endif
