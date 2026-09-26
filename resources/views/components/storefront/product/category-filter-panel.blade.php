@props([
    'options' => [],
    'filters' => [],
    'query' => '',
    'tag' => '',
    'idPrefix' => 'product-filter',
    'heading' => 'Filters',
])

@php
    $categoryTree = $options['categories'] ?? [];
    $selectedCategoryIds = collect($filters['categories'] ?? [])
        ->map(fn ($id): int => (int) $id)
        ->filter()
        ->values()
        ->all();
    $filterScrollThreshold = 12;
@endphp

<form method="GET" action="{{ route('products.index') }}" class="np-catalog-filter-form" data-product-filter-form aria-label="Filter all products">
    <div class="np-catalog-filter-header">
        <div>
            <h3>{{ $heading }}</h3>
        </div>
        @if(request()->query())
            <a href="{{ route('products.index') }}" class="np-catalog-filter-reset">Reset</a>
        @endif
    </div>

    @if(filled($tag))<input type="hidden" name="tag" value="{{ $tag }}">@endif
    <input type="hidden" name="sort" value="{{ $filters['sort'] ?? 'featured' }}">

    <div class="np-catalog-filter-scroll">
        <section class="np-catalog-filter-category-section">
            <label class="np-catalog-filter-search" for="{{ $idPrefix }}-product-search">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m21 21-4.3-4.3m1.3-5.2a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <input
                    id="{{ $idPrefix }}-product-search"
                    type="search"
                    name="q"
                    value="{{ $query }}"
                    placeholder="Search products"
                    autocomplete="off"
                    aria-label="Search products"
                    data-product-filter-search
                >
            </label>

            @if($categoryTree !== [])
                <div class="np-catalog-filter-section-label">Shop by category</div>
                <div class="np-catalog-category-tree @if(count($categoryTree) > $filterScrollThreshold) np-catalog-option-list--scroll @endif">
                    @foreach($categoryTree as $parent)
                        @php
                            $children = $parent['children'] ?? [];
                            $hasChildren = $children !== [];
                            $parentUrl = filled($parent['slug'] ?? null)
                                ? route('categories.show', $parent['slug'])
                                : route('products.index');
                        @endphp

                        @if($hasChildren)
                            <details
                                class="np-catalog-category-group"
                                @if(($parent['selected'] ?? false) || ($parent['has_selected_child'] ?? false)) open @endif
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

                                <div class="np-catalog-category-children @if(count($categoryTree) <= $filterScrollThreshold && count($children) > $filterScrollThreshold) np-catalog-option-list--scroll np-catalog-option-list--children @endif">
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
            @endif
        </section>

        <x-storefront.catalog.shared-filter-sections :filters="$filters" :options="$options" :id-prefix="$idPrefix" :show-gender="true" />
    </div>

    <div class="np-catalog-filter-actions">
        <a href="{{ route('products.index') }}" class="btn btn-outline btn-sm">Clear</a>
    </div>
</form>
