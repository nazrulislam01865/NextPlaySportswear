<x-layouts.storefront :seo="$seo">
    <section class="relative isolate overflow-hidden bg-gradient-to-br from-brand-navy via-brand-dark to-brand-blue py-14 text-white sm:py-16">
        <div class="site-container">
            <p class="text-xs font-black uppercase tracking-[.2em] text-red-100">NextPlay catalog</p>
            <h1 class="mt-3 font-display text-4xl font-bold uppercase leading-tight tracking-tight sm:text-5xl lg:text-6xl">Sale</h1>
            <p class="mt-4 max-w-2xl text-base leading-7 text-blue-50">Shop discounted teamwear and sports apparel. Customizable jerseys, shorts, training gear and more at special prices.</p>
        </div>
    </section>

    <section class="section-padding bg-slate-50 np-sale-page">
        <div class="site-container np-products-catalog-container" x-data="{filtersOpen:false}">
            <div class="mb-5 flex items-end justify-between gap-4 lg:hidden">
                <div>
                    <h2 class="font-display text-4xl font-bold uppercase tracking-tight text-brand-ink">Sale Products</h2>
                    <p class="mt-2 text-sm font-semibold text-slate-500">{{ number_format($saleResultCount) }} results</p>
                </div>
                <button type="button" class="btn btn-outline np-filter-mobile-open" x-on:click="filtersOpen=true">
                    <span>Filters</span>
                    @if($activeFilterCount > 0)<span class="np-mobile-filter-count">{{ $activeFilterCount }}</span>@endif
                </button>
            </div>

            <div class="np-product-layout has-filters grid gap-4">
                <aside class="np-filter-shell np-filter-shell--clean hidden self-start lg:flex lg:flex-col">
                    <div class="mb-5">
                        <h2 class="font-display text-3xl font-bold uppercase tracking-tight text-brand-ink">Sale Products</h2>
                        <p class="mt-2 text-sm font-semibold text-slate-500">{{ number_format($saleResultCount) }} results</p>
                    </div>

                    <x-storefront.product.category-filter-panel
                        :options="$filterOptions"
                        :filters="$filters"
                        :query="$filters['q']"
                        :tag="$filters['tag']"
                        :action="route('sale.index')"
                        :reset-url="route('sale.index')"
                        aria-label="Filter sale products"
                        heading="Filters"
                        id-prefix="desktop-sale-filter"
                    />
                </aside>

                <div class="min-w-0">
                    @if($saleTopBanner ?? null)
                        <div class="np-sale-page__top-banner mb-5">
                            <x-storefront.sale-banner :banner="$saleTopBanner" />
                        </div>
                    @endif

                    <div class="np-catalog-active-bar np-catalog-active-bar--plain mb-5">
                        <div class="np-catalog-active-summary">
                            <span class="text-sm font-semibold text-slate-600">Sale applies to eligible options. Final price shown before checkout.</span>
                            @if($activeFilterCount > 0)
                                <span class="np-catalog-active-pill">{{ $activeFilterCount }} filter{{ $activeFilterCount === 1 ? '' : 's' }} active</span>
                                <a href="{{ route('sale.index') }}" class="font-extrabold text-brand-red hover:underline">Clear all</a>
                            @endif
                        </div>
                        <div class="np-catalog-sort">
                            <label for="sale-products-sort">Sort by</label>
                            <select id="sale-products-sort" data-product-sort data-default-sort="featured">
                                <option value="featured" @selected($filters['sort']==='featured')>Featured</option>
                                <option value="best-selling" @selected($filters['sort']==='best-selling')>Best selling</option>
                                <option value="newest" @selected($filters['sort']==='newest')>Newest</option>
                                <option value="price-low" @selected($filters['sort']==='price-low')>Price: Low to High</option>
                                <option value="price-high" @selected($filters['sort']==='price-high')>Price: High to Low</option>
                                @if(($filterOptions['rating_options'] ?? []) !== [])<option value="rating-high" @selected($filters['sort']==='rating-high')>Highest rated</option>@endif
                                <option value="name-asc" @selected($filters['sort']==='name-asc')>Name: A to Z</option>
                            </select>
                        </div>
                    </div>

                    <div data-product-results aria-live="polite">
                        @include('storefront.products._sale-results', [
                            'saleProducts' => $saleProducts,
                            'saleMiddleBanner' => $saleMiddleBanner,
                            'saleMiddleInsertionIndices' => $saleMiddleInsertionIndices,
                        ])
                    </div>
                </div>
            </div>

            <div x-cloak x-show="filtersOpen" class="fixed inset-0 z-50 lg:hidden">
                <div class="absolute inset-0 bg-slate-950/60" x-on:click="filtersOpen=false"></div>
                <aside class="np-filter-drawer absolute inset-y-0 right-0">
                    <button type="button" class="np-filter-close" x-on:click="filtersOpen=false" aria-label="Close filters">×</button>
                    <x-storefront.product.category-filter-panel
                        :options="$filterOptions"
                        :filters="$filters"
                        :query="$filters['q']"
                        :tag="$filters['tag']"
                        :action="route('sale.index')"
                        :reset-url="route('sale.index')"
                        aria-label="Filter sale products"
                        heading="Filters"
                        id-prefix="mobile-sale-filter"
                    />
                </aside>
            </div>
        </div>
    </section>
</x-layouts.storefront>
