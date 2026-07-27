<x-layouts.storefront :seo="$seo">
    <section class="bg-gradient-to-br from-brand-navy via-brand-dark to-brand-blue py-14 text-white sm:py-16">
        <div class="site-container">
            <p class="text-xs font-black uppercase tracking-[.2em] text-red-100">NextPlay catalog</p>
            <h1 class="mt-3 font-display text-4xl font-bold uppercase leading-tight tracking-tight sm:text-5xl lg:text-6xl">Products</h1>
            <p class="mt-4 max-w-2xl text-base leading-7 text-blue-50">Find sportswear and team gear by category, sport, color, material, customization, quantity, price, availability, and rating.</p>

            <form
                method="GET"
                action="{{ route('products.index') }}"
                class="relative mt-8 flex max-w-2xl flex-col gap-3 rounded-2xl bg-white p-2 shadow-hero sm:flex-row"
                data-storefront-search-suggest
                data-suggest-url="{{ route('products.suggestions') }}"
            >
                <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search jersey, cap, bag, sport..." autocomplete="off" class="min-h-12 flex-1 rounded-xl border border-slate-200 px-4 text-sm text-slate-700 outline-none focus:border-brand-red">
                @if(filled($filters['tag']))<input type="hidden" name="tag" value="{{ $filters['tag'] }}">@endif
                @foreach(['categories','sports','product_types','colors','materials','artwork_methods','moq','customization','availability'] as $key)
                    @foreach((array) ($filters[$key] ?? []) as $value)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $value }}">
                    @endforeach
                @endforeach
                @foreach((array) ($filters['attributes'] ?? []) as $attributeSlug => $values)
                    @foreach((array) $values as $value)
                        <input type="hidden" name="attributes[{{ $attributeSlug }}][]" value="{{ $value }}">
                    @endforeach
                @endforeach
                @if(($filters['min_price'] ?? null) !== null)<input type="hidden" name="min_price" value="{{ $filters['min_price'] }}">@endif
                @if(($filters['max_price'] ?? null) !== null)<input type="hidden" name="max_price" value="{{ $filters['max_price'] }}">@endif
                @if(($filters['min_rating'] ?? null) !== null)<input type="hidden" name="min_rating" value="{{ $filters['min_rating'] }}">@endif
                <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
                <button class="btn btn-red" type="submit">Search</button>
                <div class="storefront-search-suggestions absolute left-2 right-2 top-[calc(100%+0.5rem)] z-[60] hidden overflow-hidden rounded-2xl border border-slate-200 bg-white text-left text-brand-ink shadow-2xl" data-storefront-search-suggestions role="listbox" aria-label="Product suggestions"></div>
            </form>
        </div>
    </section>

    <section class="section-padding bg-slate-50">
        <div class="site-container np-products-catalog-container" x-data="{filtersOpen:false}">
            <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-bold text-slate-500">{{ number_format($products->total()) }} product{{ $products->total() === 1 ? '' : 's' }} found</p>
                    <h2 class="font-display text-4xl font-bold uppercase tracking-tight text-brand-ink">
                        {{ filled($filters['tag']) ? 'Tag: '.$filters['tag'] : (filled($filters['q']) ? 'Search: '.$filters['q'] : ($hasFilters ? 'Filtered Products' : 'All Products')) }}
                    </h2>
                </div>
                <div class="lg:hidden">
                    <button type="button" class="btn btn-white np-filter-mobile-open w-full sm:w-auto" x-on:click="filtersOpen=true">
                        <span>Filters</span>
                        @if($activeFilterCount > 0)<span class="np-mobile-filter-count">{{ $activeFilterCount }}</span>@endif
                    </button>
                </div>
            </div>

            <div class="np-catalog-active-bar mb-5">
                <div class="np-catalog-active-summary">
                    <strong>{{ number_format($products->total()) }} results</strong>
                    @if($activeFilterCount > 0)
                        <span class="np-catalog-active-pill">{{ $activeFilterCount }} filter{{ $activeFilterCount === 1 ? '' : 's' }} active</span>
                        <a href="{{ route('products.index') }}" class="font-extrabold text-brand-red hover:underline">Clear all</a>
                    @else
                        <span>Use the product finder to narrow the catalog.</span>
                    @endif
                </div>
                <div class="np-catalog-sort">
                    <label for="products-sort">Sort by</label>
                    <select id="products-sort" onchange="const url=new URL(window.location.href);url.searchParams.set('sort',this.value);url.searchParams.delete('page');window.location.assign(url.toString())">
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
                <aside class="np-filter-shell hidden self-start lg:flex">
                    <x-storefront.product.category-filter-panel
                        :options="$filterOptions"
                        :filters="$filters"
                        :query="$filters['q']"
                        :tag="$filters['tag']"
                        heading="Filters"
                        subheading="Choose a category, then refine by the product details that matter to your order."
                        id-prefix="desktop-product-filter"
                    />
                </aside>

                <div>
                    @if ($products->count())
                        <div class="np-product-listing-grid np-product-listing-grid--three grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($products as $product)
                                <x-storefront.product-card :product="$product" :show-category="true" />
                            @endforeach
                        </div>
                        <div class="mt-7 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-card">{{ $products->links('pagination.nextplay', ['itemName' => 'product']) }}</div>
                    @else
                        <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-card">
                            <h3 class="font-display text-3xl font-bold uppercase text-brand-ink">No products found</h3>
                            <p class="mt-2 text-slate-600">Try another search term or remove one of the selected filters.</p>
                            <a href="{{ route('products.index') }}" class="btn btn-red mt-5">Clear Filters</a>
                        </div>
                    @endif
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
                        subheading="Choose options, then apply to update the products."
                        id-prefix="mobile-product-filter"
                    />
                </aside>
            </div>
        </div>
    </section>
</x-layouts.storefront>
