@props(['item', 'align' => 'left'])
@php
    $children = $item->childrenRecursive ?? collect();
    $isShopMega = str($item->label ?? '')->lower()->squish()->toString() === 'shop products'
        || (($item->route_name ?? null) === 'categories.index');
    $isActive = false;

    if (($item->route_name ?? null) === 'categories.index') {
        $isActive = request()->routeIs('categories.*');
    } elseif (($item->route_name ?? null) && request()->routeIs($item->route_name)) {
        $isActive = ! ($item->route_name === 'products.index' && request()->boolean('deals'));
    } elseif (($item->link_type ?? null) === 'category' && request()->routeIs('categories.show')) {
        $routeCategory = request()->route('category');
        $routeSlug = is_object($routeCategory) ? ($routeCategory->slug ?? null) : $routeCategory;
        $isActive = filled($routeSlug) && $routeSlug === ($item->category ?? null);
    }
@endphp
<div @class([
    'np-menu-item np-category-menu-item',
    'np-menu-item-right' => $align === 'right',
    'is-active' => $isActive,
])>
    <a
        href="{{ $item->resolvedUrl() }}"
        target="{{ $item->target }}"
        @if($item->target === '_blank') rel="noopener noreferrer" @endif
        class="np-category-link np-menu-link {{ $item->css_class }} {{ $isActive ? 'is-active' : '' }}"
        @if($isActive) aria-current="page" @endif
        @if($children->isNotEmpty()) aria-haspopup="true" aria-expanded="false" @endif
        data-header-analytics="header_navigation_click"
        data-header-analytics-label="{{ str($item->label)->slug('_') }}"
    >
        <span>{{ $item->label }}</span>
        @if($children->isNotEmpty())
            <svg class="np-category-caret" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
        @endif
    </a>

    @if($children->isNotEmpty())
        <div @class(['np-menu-panel', 'np-shop-panel' => $isShopMega]) role="group" aria-label="{{ $item->label }} submenu">
            @if($item->resolvedUrl() !== '#')
                <a
                    class="np-menu-view-all"
                    href="{{ $item->resolvedUrl() }}"
                    target="{{ $item->target }}"
                    @if($item->target === '_blank') rel="noopener noreferrer" @endif
                    data-header-analytics="header_navigation_click"
                    data-header-analytics-label="{{ str($item->label)->slug('_') }}_view_all"
                >
                    <span>View all {{ $item->label }}</span>
                    <span aria-hidden="true">→</span>
                </a>
            @endif

            <div @class(['np-mega-grid', 'np-standard-grid' => ! $isShopMega])>
                @foreach($children as $child)
                    <div class="np-mega-card">
                        <a
                            href="{{ $child->resolvedUrl() }}"
                            target="{{ $child->target }}"
                            @if($child->target === '_blank') rel="noopener noreferrer" @endif
                            class="np-mega-title {{ $isShopMega ? 'np-mega-title-with-icon' : '' }}"
                            data-header-analytics="header_navigation_click"
                            data-header-analytics-label="category_{{ str($child->label)->slug('_') }}"
                        >
                            @if($isShopMega)
                                <span class="np-mega-category-icon" aria-hidden="true">
                                    <x-storefront.category-icon :label="$child->label" :icon-url="$child->icon_url" />
                                </span>
                            @endif
                            <span>{{ $child->label }}</span>
                        </a>

                        @if($child->childrenRecursive->isNotEmpty())
                            <div class="np-mega-sublist">
                                @foreach($child->childrenRecursive as $grandchild)
                                    <div class="np-mega-subitem">
                                        <a
                                            href="{{ $grandchild->resolvedUrl() }}"
                                            target="{{ $grandchild->target }}"
                                            @if($grandchild->target === '_blank') rel="noopener noreferrer" @endif
                                            class="np-mega-subtitle"
                                            data-header-analytics="header_navigation_click"
                                            data-header-analytics-label="category_{{ str($grandchild->label)->slug('_') }}"
                                        >{{ $grandchild->label }}</a>

                                        @if($grandchild->childrenRecursive->isNotEmpty())
                                            <div class="np-mega-leaf-list">
                                                @foreach($grandchild->childrenRecursive as $leaf)
                                                    <a
                                                        href="{{ $leaf->resolvedUrl() }}"
                                                        target="{{ $leaf->target }}"
                                                        @if($leaf->target === '_blank') rel="noopener noreferrer" @endif
                                                        class="np-mega-leaf"
                                                        data-header-analytics="header_navigation_click"
                                                        data-header-analytics-label="category_{{ str($leaf->label)->slug('_') }}"
                                                    >{{ $leaf->label }}</a>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
