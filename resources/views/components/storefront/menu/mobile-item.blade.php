@props(['item', 'depth' => 0])
@php
    $children = $item->childrenRecursive ?? collect();
    $iconUrl = $item->icon_url ?? null;
    $showCategoryIcon = ($item->link_type ?? null) === 'category' && (int) $depth === 1;

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

@if($children->isNotEmpty())
    <details class="np-mobile-nav-details border-b border-slate-100 py-1" {{ $isActive ? 'open' : '' }}>
        <summary class="np-mobile-nav-summary flex min-h-11 cursor-pointer items-center justify-between gap-3 rounded-lg px-3 py-2 font-extrabold hover:bg-slate-50 {{ $isActive ? 'is-active' : '' }}">
            <span class="flex min-w-0 items-center gap-2">
                @if($showCategoryIcon)
                    <span class="np-mobile-menu-category-icon" aria-hidden="true"><x-storefront.category-icon :label="$item->label" :icon-url="$iconUrl" /></span>
                @endif
                <span class="truncate">{{ $item->label }}</span>
            </span>
            <span aria-hidden="true">+</span>
        </summary>
        <div class="space-y-1 border-l border-slate-200 py-1 pl-3">
            @if($item->resolvedUrl() !== '#')
                <a
                    class="block rounded-lg px-3 py-2.5 font-bold text-brand-red hover:bg-red-50"
                    href="{{ $item->resolvedUrl() }}"
                    target="{{ $item->target }}"
                    @if($item->target === '_blank') rel="noopener noreferrer" @endif
                    data-header-analytics="header_navigation_click"
                    data-header-analytics-label="mobile_{{ str($item->label)->slug('_') }}_view_all"
                >View all {{ $item->label }}</a>
            @endif
            @foreach($children as $child)
                <x-storefront.menu.mobile-item :item="$child" :depth="$depth + 1" />
            @endforeach
        </div>
    </details>
@else
    <a
        class="np-mobile-nav-link flex min-h-11 items-center gap-2 rounded-lg px-3 py-2.5 font-semibold hover:bg-slate-100 {{ $isActive ? 'is-active' : '' }}"
        href="{{ $item->resolvedUrl() }}"
        target="{{ $item->target }}"
        @if($item->target === '_blank') rel="noopener noreferrer" @endif
        @if($isActive) aria-current="page" @endif
        data-header-analytics="header_navigation_click"
        data-header-analytics-label="mobile_{{ str($item->label)->slug('_') }}"
    >
        @if($showCategoryIcon)
            <span class="np-mobile-menu-category-icon" aria-hidden="true"><x-storefront.category-icon :label="$item->label" :icon-url="$iconUrl" /></span>
        @endif
        <span class="min-w-0 truncate">{{ $item->label }}</span>
    </a>
@endif
