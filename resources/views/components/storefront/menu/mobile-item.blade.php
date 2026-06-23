@props(['item', 'depth' => 0])
@php($children = $item->childrenRecursive ?? collect())
@if($children->isNotEmpty())
    <details class="border-b border-slate-100 py-1">
        <summary class="flex min-h-11 cursor-pointer items-center justify-between gap-3 rounded-lg px-3 py-2 font-extrabold hover:bg-slate-50">
            <span>{{ $item->label }}</span><span aria-hidden="true">+</span>
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
    <a class="block min-h-11 rounded-lg px-3 py-2.5 font-semibold hover:bg-slate-100" href="{{ $item->resolvedUrl() }}" target="{{ $item->target }}" @if($item->target==='_blank') rel="noopener noreferrer" @endif>{{ $item->label }}</a>
@endif
