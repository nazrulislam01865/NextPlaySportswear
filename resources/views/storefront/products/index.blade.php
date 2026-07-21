<x-layouts.storefront :seo="$seo">
    @php
        $activeFilterCount = count($selectedCategoryIds) + (filled($query) ? 1 : 0) + (filled($tag ?? null) ? 1 : 0);
    @endphp

    <section class="bg-gradient-to-br from-brand-navy via-brand-dark to-brand-blue py-16 text-white">
        <div class="site-container">
            <p class="text-xs font-black uppercase tracking-[.2em] text-red-100">NextPlay catalog</p>
            <h1 class="mt-3 font-display text-4xl font-bold uppercase leading-tight tracking-tight sm:text-5xl lg:text-6xl">Products</h1>
            <p class="mt-4 max-w-2xl text-base leading-7 text-blue-50">Browse custom jerseys, team uniforms, hoodies, caps, bags, and quote-ready sportswear products.</p>

            <form
                method="GET"
                action="{{ route('products.index') }}"
                class="relative mt-8 flex max-w-2xl flex-col gap-3 rounded-2xl bg-white p-2 shadow-hero sm:flex-row"
                data-storefront-search-suggest
                data-suggest-url="{{ route('products.suggestions') }}"
            >
                <input type="search" name="q" value="{{ $query }}" placeholder="Search football, jersey, cap, bag..." autocomplete="off" class="min-h-12 flex-1 rounded-xl border border-slate-200 px-4 text-sm text-slate-700 outline-none focus:border-brand-red">
                @if(filled($tag ?? null))<input type="hidden" name="tag" value="{{ $tag }}">@endif
                @foreach($selectedCategoryIds as $selectedCategoryId)
                    <input type="hidden" name="categories[]" value="{{ $selectedCategoryId }}">
                @endforeach
                <button class="btn btn-red" type="submit">Search</button>
                <div
                    class="storefront-search-suggestions absolute left-2 right-2 top-[calc(100%+0.5rem)] z-[60] hidden overflow-hidden rounded-2xl border border-slate-200 bg-white text-left text-brand-ink shadow-2xl"
                    data-storefront-search-suggestions
                    role="listbox"
                    aria-label="Product suggestions"
                ></div>
            </form>
        </div>
    </section>

    <section class="section-padding bg-slate-50">
        <div class="site-container" x-data="{filtersOpen:false}">
            <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-bold text-slate-500">{{ $products->total() }} product{{ $products->total() === 1 ? '' : 's' }} found</p>
                    <h2 class="font-display text-4xl font-bold uppercase tracking-tight text-brand-ink">
                        {{ filled($tag ?? null) ? 'Tag: ' . $tag : (filled($query) ? 'Search: ' . $query : ($selectedCategoryIds !== [] ? 'Filtered Products' : 'Featured Products')) }}
                    </h2>
                    @if($hasFilters)
                        <a href="{{ route('products.index') }}" class="mt-2 inline-flex text-sm font-black text-brand-red hover:underline">Clear all filters</a>
                    @endif
                </div>
                <div class="flex flex-col gap-3 sm:flex-row">
                    @if($categoryFilters !== [])
                        <button type="button" class="btn btn-white np-filter-mobile-open w-full sm:w-auto lg:hidden" x-on:click="filtersOpen=true">
                            <span>Filters</span>
                            @if($activeFilterCount > 0)
                                <span class="np-mobile-filter-count">{{ $activeFilterCount }}</span>
                            @endif
                        </button>
                    @endif
                    <a href="{{ route('quote.request') }}" class="btn btn-white">Need Bulk Quote?</a>
                </div>
            </div>

            <div class="np-product-layout {{ $categoryFilters !== [] ? 'has-filters' : '' }} grid gap-7">
                @if($categoryFilters !== [])
                    <aside class="np-filter-shell hidden self-start lg:flex">
                        <x-storefront.product.category-filter-panel
                            :filters="$categoryFilters"
                            :selected-category-ids="$selectedCategoryIds"
                            :query="$query"
                            :tag="$tag"
                            heading="Filters"
                            subheading="Select a parent category or open it to choose a specific subcategory."
                            id-prefix="desktop-product-filter"
                        />
                    </aside>
                @endif

                <div>
                    @if ($products->count())
                        <p class="mb-5 text-sm font-semibold text-slate-500">Showing {{ $products->firstItem() }}–{{ $products->lastItem() }} of {{ $products->total() }} products</p>
                        <div class="np-product-listing-grid grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
                            @foreach ($products as $product)
                                <x-storefront.product-card :product="$product" :show-category="true" />
                            @endforeach
                        </div>
                        <div class="mt-8">{{ $products->links() }}</div>
                    @else
                        <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-card">
                            <h3 class="font-display text-3xl font-bold uppercase text-brand-ink">No products found</h3>
                            <p class="mt-2 text-slate-600">Try another search keyword, change the selected category filter, or request a custom quote.</p>
                            <a href="{{ route('products.index') }}" class="btn btn-red mt-5">View All Products</a>
                        </div>
                    @endif
                </div>
            </div>

            @if($categoryFilters !== [])
                <div x-cloak x-show="filtersOpen" class="fixed inset-0 z-50 lg:hidden">
                    <div class="absolute inset-0 bg-slate-950/60" x-on:click="filtersOpen=false"></div>
                    <aside class="np-filter-drawer absolute inset-y-0 right-0">
                        <button type="button" class="np-filter-close" x-on:click="filtersOpen=false" aria-label="Close filters">×</button>
                        <x-storefront.product.category-filter-panel
                            :filters="$categoryFilters"
                            :selected-category-ids="$selectedCategoryIds"
                            :query="$query"
                            :tag="$tag"
                            heading="Filters"
                            subheading="Search or choose a category, then apply."
                            id-prefix="mobile-product-filter"
                        />
                    </aside>
                </div>
            @endif
        </div>
    </section>
</x-layouts.storefront>
