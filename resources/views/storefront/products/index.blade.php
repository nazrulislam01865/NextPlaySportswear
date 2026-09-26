<x-layouts.storefront :seo="$seo">
    @php
        $catalogBannerImage = $catalogBanner['image'] ?? null;
        $catalogBannerColor = $catalogBanner['color'] ?? null;
        $catalogBannerHasCustomBackground = filled($catalogBannerImage) || filled($catalogBannerColor);
    @endphp

    <section
        class="relative isolate overflow-hidden py-14 text-white sm:py-16 {{ $catalogBannerHasCustomBackground ? 'bg-brand-dark' : 'bg-gradient-to-br from-brand-navy via-brand-dark to-brand-blue' }}"
        @if(filled($catalogBannerColor)) style="background-color: {{ $catalogBannerColor }};" @endif
    >
        @if(filled($catalogBannerImage))
            <img
                src="{{ $catalogBannerImage }}"
                alt=""
                class="absolute inset-0 -z-20 h-full w-full object-cover"
                fetchpriority="high"
                aria-hidden="true"
            >
            <div class="absolute inset-0 -z-10 bg-gradient-to-r from-brand-dark/90 via-brand-navy/75 to-brand-dark/35"></div>
        @endif

        <div class="site-container">
            <p class="text-xs font-black uppercase tracking-[.2em] text-red-100">NextPlay catalog</p>
            <h1 class="mt-3 font-display text-4xl font-bold uppercase leading-tight tracking-tight sm:text-5xl lg:text-6xl">Products</h1>
            <p class="mt-4 max-w-2xl text-base leading-7 text-blue-50">Find sportswear and team gear by category, sport, color, material, customization, quantity, price, availability, and rating.</p>
        </div>
    </section>

    <section class="section-padding bg-slate-50">
        <div class="site-container np-products-catalog-container" x-data="{filtersOpen:false}">
            <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="font-display text-4xl font-bold uppercase tracking-tight text-brand-ink">
                        {{ filled($filters['tag']) ? 'Tag: '.$filters['tag'] : (filled($filters['q']) ? 'Search: '.$filters['q'] : ($hasFilters ? 'Filtered Products' : 'All Products')) }}
                    </h2>
                </div>
                <div class="lg:hidden">
                    <button type="button" class="btn btn-outline np-filter-mobile-open w-full sm:w-auto" x-on:click="filtersOpen=true">
                        <span>Filters</span>
                        @if($activeFilterCount > 0)<span class="np-mobile-filter-count">{{ $activeFilterCount }}</span>@endif
                    </button>
                </div>
            </div>

            <div class="np-catalog-active-bar np-catalog-active-bar--plain mb-5">
                <div class="np-catalog-active-summary">
                    <strong>{{ number_format($products->total()) }} results</strong>
                    @if($activeFilterCount > 0)
                        <span class="np-catalog-active-pill">{{ $activeFilterCount }} filter{{ $activeFilterCount === 1 ? '' : 's' }} active</span>
                        <a href="{{ route('products.index') }}" class="font-extrabold text-brand-red hover:underline">Clear all</a>
                    @endif
                </div>
                <div class="np-catalog-sort">
                    <label for="products-sort">Sort by</label>
                    <select id="products-sort" data-product-sort>
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

            <div class="np-product-layout has-filters grid gap-4">
                <aside class="np-filter-shell np-filter-shell--clean hidden self-start lg:flex">
                    <x-storefront.product.category-filter-panel
                        :options="$filterOptions"
                        :filters="$filters"
                        :query="$filters['q']"
                        :tag="$filters['tag']"
                        heading="Filters"
                        id-prefix="desktop-product-filter"
                    />
                </aside>

                <div data-product-results aria-live="polite">
                    @include('storefront.products._results', ['products' => $products])
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
                        heading="Filters"
                        id-prefix="mobile-product-filter"
                    />
                </aside>
            </div>
        </div>
    </section>
</x-layouts.storefront>
