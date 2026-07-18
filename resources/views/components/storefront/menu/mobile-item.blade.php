@props(['item', 'depth' => 0])
@php($children = $item->childrenRecursive ?? collect())
@php($iconUrl = $item->icon_url ?? null)
@php($showCategoryIcon = ($item->link_type ?? null) === 'category' && (int) $depth === 1)
@if($children->isNotEmpty())
    <details class="border-b border-slate-100 py-1">
        <summary class="flex min-h-11 cursor-pointer items-center justify-between gap-3 rounded-lg px-3 py-2 font-extrabold hover:bg-slate-50">
            <span class="flex min-w-0 items-center gap-2">
                @if($showCategoryIcon)
                    <span class="np-mobile-menu-category-icon" aria-hidden="true"><x-storefront.category-icon :label="$item->label" :icon-url="$iconUrl" /></span>
                @endif
                <span class="truncate">{{ $item->label }}</span>
            </span><span aria-hidden="true">+</span>
        </summary>
        <div class="space-y-1 border-l border-slate-200 py-1 pl-3">
            @if($item->resolvedUrl() !== '#')
                <a class="block rounded-lg px-3 py-2.5 font-bold text-brand-red hover:bg-red-50" href="{{ $item->resolvedUrl() }}" target="{{ $item->target }}" @if($item->target==='_blank') rel="noopener noreferrer" @endif>View all {{ $item->label }}</a>
            @endif
            @foreach($children as $child)
                <x-storefront.menu.mobile-item :item="$child" :depth="$depth + 1" />
            @endforeach
        </div>
    </details>
@else
    <a class="flex min-h-11 items-center gap-2 rounded-lg px-3 py-2.5 font-semibold hover:bg-slate-100" href="{{ $item->resolvedUrl() }}" target="{{ $item->target }}" @if($item->target==='_blank') rel="noopener noreferrer" @endif>
        @if($showCategoryIcon)
            <span class="np-mobile-menu-category-icon" aria-hidden="true"><x-storefront.category-icon :label="$item->label" :icon-url="$iconUrl" /></span>
        @endif
        <span class="min-w-0 truncate">{{ $item->label }}</span>
    </a>
@endif
