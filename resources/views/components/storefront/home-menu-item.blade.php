@props(['item', 'depth' => 0])
@php($children = $item->childrenRecursive ?? collect())

@if($depth === 0)
    <div class="home-nav-item">
        <a href="{{ $item->resolvedUrl() }}" target="{{ $item->target }}" @if($item->target === '_blank') rel="noopener noreferrer" @endif>
            {{ $item->label }}@if($children->isNotEmpty()) <span aria-hidden="true">▾</span>@endif
        </a>
        @if($children->isNotEmpty())
            <div class="home-nav-dropdown" role="group" aria-label="{{ $item->label }} submenu">
                @if($item->resolvedUrl() !== '#')
                    <a class="home-nav-view-all" href="{{ $item->resolvedUrl() }}" target="{{ $item->target }}" @if($item->target === '_blank') rel="noopener noreferrer" @endif>View all {{ $item->label }} →</a>
                @endif
                <div class="home-nav-columns">
                    @foreach($children as $child)
                        <x-storefront.home-menu-item :item="$child" :depth="1" />
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@else
    <div class="home-nav-child">
        <a href="{{ $item->resolvedUrl() }}" target="{{ $item->target }}" @if($item->target === '_blank') rel="noopener noreferrer" @endif>{{ $item->label }}</a>
        @if($children->isNotEmpty())
            <div class="home-nav-sublist">
                @foreach($children as $child)
                    <x-storefront.home-menu-item :item="$child" :depth="$depth + 1" />
                @endforeach
            </div>
        @endif
    </div>
@endif
