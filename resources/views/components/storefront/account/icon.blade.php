@props([
    'name',
    'size' => 22,
])

@php
    $size = max(14, min(64, (int) $size));
    $attrs = [
        'width' => $size,
        'height' => $size,
        'viewBox' => '0 0 24 24',
        'fill' => 'none',
        'stroke' => 'currentColor',
        'stroke-width' => '1.8',
        'stroke-linecap' => 'round',
        'stroke-linejoin' => 'round',
        'aria-hidden' => 'true',
    ];
@endphp

@switch($name)
    @case('dashboard')
        <svg {{ $attributes->merge($attrs) }}><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
        @break
    @case('profile')
        <svg {{ $attributes->merge($attrs) }}><circle cx="12" cy="8" r="3.5"/><path d="M5 21a7 7 0 0 1 14 0"/></svg>
        @break
    @case('order-center')
        <svg {{ $attributes->merge($attrs) }}><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/><path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 2-1.6L21 8H7"/></svg>
        @break
    @case('order-history')
    @case('orders')
        <svg {{ $attributes->merge($attrs) }}><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 8h6M9 12h6M9 16h4"/></svg>
        @break
    @case('rewards')
        <svg {{ $attributes->merge($attrs) }}><rect x="3" y="8" width="18" height="13" rx="1.5"/><path d="M12 8v13M3 12h18"/><path d="M12 8H8.5A2.5 2.5 0 1 1 11 5.5V8Zm0 0h3.5A2.5 2.5 0 1 0 13 5.5V8Z"/></svg>
        @break
    @case('referral')
        <svg {{ $attributes->merge($attrs) }}><circle cx="8" cy="8" r="3"/><path d="M2.8 20a5.2 5.2 0 0 1 10.4 0"/><circle cx="17" cy="9" r="2.5"/><path d="M15.4 14.5a4.8 4.8 0 0 1 4.6 4.8"/></svg>
        @break
    @case('returns')
        <svg {{ $attributes->merge($attrs) }}><path d="M7 7H4v-3"/><path d="M4 7a8 8 0 1 1-1 8"/><path d="M9 9h6v6H9z"/></svg>
        @break
    @case('downloads')
        <svg {{ $attributes->merge($attrs) }}><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
        @break
    @case('address')
    @case('location')
        <svg {{ $attributes->merge($attrs) }}><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.7"/></svg>
        @break
    @case('payment')
        <svg {{ $attributes->merge($attrs) }}><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/></svg>
        @break
    @case('support')
        <svg {{ $attributes->merge($attrs) }}><circle cx="12" cy="12" r="9"/><path d="M9.6 9.2a2.7 2.7 0 1 1 4.6 1.9c-.9.8-2.2 1.3-2.2 2.9M12 17h.01"/></svg>
        @break
    @case('truck')
        <svg {{ $attributes->merge($attrs) }}><path d="M3 6h11v10H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>
        @break
    @case('gift')
        <svg {{ $attributes->merge($attrs) }}><rect x="3" y="9" width="18" height="12" rx="1.5"/><path d="M12 9v12M3 13h18"/><path d="M12 9H8.5A2.5 2.5 0 1 1 11 6.5V9Zm0 0h3.5A2.5 2.5 0 1 0 13 6.5V9Z"/></svg>
        @break
    @case('secure')
        <svg {{ $attributes->merge($attrs) }}><path d="M12 3 19 6v5c0 4.6-2.7 8.1-7 10-4.3-1.9-7-5.4-7-10V6z"/><path d="m9.5 12 1.6 1.6 3.6-3.8"/></svg>
        @break
    @case('team')
        <svg {{ $attributes->merge($attrs) }}><circle cx="8" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M2.8 20a5.2 5.2 0 0 1 10.4 0M15.4 14.5a4.8 4.8 0 0 1 4.6 4.8"/></svg>
        @break
    @case('search')
        <svg {{ $attributes->merge($attrs) }}><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
        @break
    @case('calendar')
        <svg {{ $attributes->merge($attrs) }}><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>
        @break
    @case('arrow-right')
        <svg {{ $attributes->merge($attrs) }}><path d="M5 12h14M14 7l5 5-5 5"/></svg>
        @break
    @case('chevron-right')
        <svg {{ $attributes->merge($attrs) }}><path d="m9 6 6 6-6 6"/></svg>
        @break
    @case('logout')
        <svg {{ $attributes->merge($attrs) }}><path d="M10 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h5"/><path d="m16 8 4 4-4 4M20 12H9"/></svg>
        @break
    @default
        <svg {{ $attributes->merge($attrs) }}><circle cx="12" cy="12" r="9"/></svg>
@endswitch
