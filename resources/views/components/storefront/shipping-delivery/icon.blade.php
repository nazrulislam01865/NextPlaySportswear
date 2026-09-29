@props([
    'iconUrl' => null,
    'fallback' => 'info',
    'alt' => '',
    'size' => 42,
])

@if($iconUrl)
<span class="np-shipping-delivery-icon">
        <img src="{{ $iconUrl }}" alt="{{ $alt }}" width="{{ $size }}" height="{{ $size }}" style="display:block;width:{{ (int) $size }}px;height:{{ (int) $size }}px;object-fit:contain;">
</span>
@else
<span class="np-shipping-delivery-icon" role="img" aria-label="{{ $alt }}">
        @switch($fallback)
            @case('cart')
                <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 7h5l4.3 22h22.2l4-16H12.2"/><circle cx="18" cy="37" r="2.5"/><circle cx="34" cy="37" r="2.5"/></svg>
                @break
            @case('box')
                <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M8 14 24 5l16 9v20l-16 9-16-9V14Z"/><path d="m8 14 16 9 16-9M24 23v20M16 9.5l16 9"/></svg>
                @break
            @case('user')
                <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><circle cx="24" cy="16" r="7"/><path d="M11 42c1-9 5.6-14 13-14s12 5 13 14H11Z"/></svg>
                @break
            @case('document')
                <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M13 5h15l8 8v30H13V5Z"/><path d="M28 5v9h8M19 22h11M19 28h11M19 34h8"/></svg>
                @break
            @case('pin')
                <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M39 20c0 10-15 23-15 23S9 30 9 20a15 15 0 1 1 30 0Z"/><circle cx="24" cy="20" r="5"/></svg>
                @break
            @case('phone')
                <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M14 6 8 10c-2 2-1 8 2 14 4 8 10 14 18 18 6 3 12 4 14 2l4-6-9-8-5 4c-5-2-10-7-12-12l4-5-10-11Z"/></svg>
                @break
            @default
                <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><circle cx="24" cy="24" r="19"/><path d="M24 21v13M24 14.5h.01"/></svg>
        @endswitch
</span>
@endif
