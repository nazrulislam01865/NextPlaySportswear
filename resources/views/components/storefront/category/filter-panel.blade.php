@props(['category', 'filters', 'options', 'idPrefix' => 'category-filter'])

<form method="GET" action="{{ $category['url'] }}" class="np-catalog-filter-form" aria-label="Filter products in this category">
    <div class="np-catalog-filter-header np-catalog-filter-header--compact">
        <div>
            <p class="np-catalog-filter-eyebrow">Refine results</p>
            <h3>Filters</h3>
        </div>
        @if(request()->query())
            <a href="{{ $category['url'] }}" class="np-catalog-filter-reset">Reset</a>
        @endif
    </div>

    <input type="hidden" name="sort" value="{{ $filters['sort'] ?? 'featured' }}">

    <div class="np-catalog-filter-scroll">
        <div class="np-catalog-filter-search-wrap" data-storefront-search-suggest data-suggest-url="{{ route('products.suggestions') }}">
            <label class="np-catalog-filter-search" for="{{ $idPrefix }}-search">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m21 21-4.3-4.3m1.3-5.2a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <input id="{{ $idPrefix }}-search" type="search" name="q" value="{{ $filters['q'] }}" maxlength="100" placeholder="Search this category" autocomplete="off">
            </label>
            <div class="storefront-search-suggestions absolute left-0 right-0 top-[calc(100%+0.5rem)] z-[60] hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl" data-storefront-search-suggestions role="listbox" aria-label="Product suggestions"></div>
        </div>

        @if(($options['subcategories'] ?? []) !== [])
            <details class="np-catalog-filter-section" @if(($filters['subcategory'] ?? []) !== []) open @endif>
                <summary class="np-catalog-filter-title"><span>Shop within {{ $category['short_title'] }}</span><span aria-hidden="true">+</span></summary>
                <div class="np-catalog-filter-options">
                    @foreach($options['subcategories'] as $option)
                        @php($fieldId = $idPrefix.'-subcategory-'.$option['id'])
                        <label class="np-catalog-filter-option" for="{{ $fieldId }}">
                            <span class="np-catalog-filter-option__main">
                                <input id="{{ $fieldId }}" type="checkbox" name="subcategory[]" value="{{ $option['id'] }}" @checked(in_array((int) $option['id'], array_map('intval', (array) ($filters['subcategory'] ?? [])), true))>
                                <span>{{ $option['label'] }}</span>
                            </span>
                            <span class="np-catalog-filter-count">{{ $option['count'] }}</span>
                        </label>
                    @endforeach
                </div>
            </details>
        @endif

        <x-storefront.catalog.shared-filter-sections :filters="$filters" :options="$options" :id-prefix="$idPrefix" />
    </div>

    <div class="np-catalog-filter-actions">
        <a href="{{ $category['url'] }}" class="btn btn-white">Clear</a>
        <button type="submit" class="btn btn-red">Apply Filters</button>
    </div>
</form>
