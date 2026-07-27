@props([
    'options' => [],
    'filters' => [],
    'query' => '',
    'tag' => '',
    'idPrefix' => 'product-filter',
    'heading' => 'Filters',
    'subheading' => 'Choose the options that match your order.',
])

@php
    $categoryTree = $options['categories'] ?? [];
    $selectedCategoryIds = collect($filters['categories'] ?? [])
        ->map(fn ($id): int => (int) $id)
        ->filter()
        ->values()
        ->all();
@endphp

<form method="GET" action="{{ route('products.index') }}" class="np-catalog-filter-form" x-data="{ categorySearch: '' }" aria-label="Filter all products">
    <div class="np-catalog-filter-header">
        <div>
            <p class="np-catalog-filter-eyebrow">Product finder</p>
            <h3>{{ $heading }}</h3>
            <p>{{ $subheading }}</p>
        </div>
        @if(request()->query())
            <a href="{{ route('products.index') }}" class="np-catalog-filter-reset">Reset</a>
        @endif
    </div>

    @if(filled($query))<input type="hidden" name="q" value="{{ $query }}">@endif
    @if(filled($tag))<input type="hidden" name="tag" value="{{ $tag }}">@endif
    <input type="hidden" name="sort" value="{{ $filters['sort'] ?? 'featured' }}">

    <div class="np-catalog-filter-scroll">
        @if($categoryTree !== [])
            <section class="np-catalog-filter-category-section">
                <label class="np-catalog-filter-search" for="{{ $idPrefix }}-category-search">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m21 21-4.3-4.3m1.3-5.2a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    <input id="{{ $idPrefix }}-category-search" type="search" placeholder="Search categories" x-model="categorySearch" autocomplete="off">
                </label>

                <div class="np-catalog-filter-section-label">Shop by category</div>
                <div class="np-catalog-category-tree">
                    @foreach($categoryTree as $parent)
                        @php
                            $children = $parent['children'] ?? [];
                            $hasChildren = $children !== [];
                            $categorySearchText = strtolower($parent['label'].' '.collect($children)->pluck('label')->implode(' '));
                            $parentUrl = filled($parent['slug'] ?? null)
                                ? route('categories.show', $parent['slug'])
                                : route('products.index');
                        @endphp

                        @if($hasChildren)
                            <details
                                class="np-catalog-category-group"
                                @if(($parent['selected'] ?? false) || ($parent['has_selected_child'] ?? false)) open @endif
                                data-category-search="{{ $categorySearchText }}"
                                x-show="categorySearch === '' || $el.dataset.categorySearch.includes(categorySearch.toLowerCase())"
                            >
                                <summary aria-label="Expand {{ $parent['label'] }} subcategories">
                                    <span class="np-catalog-category-parent">
                                        <span class="np-catalog-category-icon" aria-hidden="true">
                                            <x-storefront.category-icon :label="$parent['label']" :icon-url="$parent['icon_url'] ?? null" />
                                        </span>
                                        <span class="np-catalog-category-name">{{ $parent['label'] }}</span>
                                        <span class="np-catalog-filter-count">{{ $parent['count'] }}</span>
                                        <span class="np-catalog-category-chevron" aria-hidden="true">
                                            <svg viewBox="0 0 20 20" fill="none"><path d="m6.75 8.25 3.25 3.5 3.25-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        </span>
                                    </span>
                                </summary>

                                <div class="np-catalog-category-children">
                                    @foreach($children as $child)
                                        @php($childFieldId = $idPrefix.'-category-'.$child['id'])
                                        <label class="np-catalog-filter-option" for="{{ $childFieldId }}">
                                            <span class="np-catalog-filter-option__main">
                                                <input id="{{ $childFieldId }}" type="checkbox" name="categories[]" value="{{ $child['id'] }}" @checked($child['selected'] ?? false)>
                                                <span>{{ $child['label'] }}</span>
                                            </span>
                                            <span class="np-catalog-filter-count">{{ $child['count'] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </details>
                        @else
                            <div
                                class="np-catalog-category-group np-catalog-category-group--leaf"
                                data-category-search="{{ $categorySearchText }}"
                                x-show="categorySearch === '' || $el.dataset.categorySearch.includes(categorySearch.toLowerCase())"
                            >
                                <a class="np-catalog-category-parent" href="{{ $parentUrl }}" aria-label="View {{ $parent['label'] }} products">
                                    <span class="np-catalog-category-icon" aria-hidden="true">
                                        <x-storefront.category-icon :label="$parent['label']" :icon-url="$parent['icon_url'] ?? null" />
                                    </span>
                                    <span class="np-catalog-category-name">{{ $parent['label'] }}</span>
                                    <span class="np-catalog-filter-count">{{ $parent['count'] }}</span>
                                </a>
                            </div>
                        @endif
                    @endforeach
                </div>
            </section>
        @endif

        <x-storefront.catalog.shared-filter-sections :filters="$filters" :options="$options" :id-prefix="$idPrefix" />
    </div>

    <div class="np-catalog-filter-actions">
        <a href="{{ route('products.index') }}" class="btn btn-white">Clear</a>
        <button type="submit" class="btn btn-red">Apply Filters</button>
    </div>
</form>
