@props([
    'name',
    'size' => 52,
])

@php
    $size = max(16, (int) $size);
    $common = 'fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"';
@endphp

<span {{ $attributes->class('np-about-icon') }} aria-hidden="true">
    @switch($name)
        @case('teamwear')
            <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 48 48" {!! $common !!}>
                <path d="M17 9.5 11 12l-6 8.5 7 4.2 3.2-4.5V39h17.6V20.2l3.2 4.5 7-4.2L37 12l-6-2.5c-1 3-3.5 5-7 5s-6-2-7-5Z" />
                <path d="M18.2 10.2c.8 2.2 2.8 3.5 5.8 3.5s5-1.3 5.8-3.5" />
            </svg>
            @break
        @case('sportswear')
            <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 48 48" {!! $common !!}>
                <path d="M17 9.5 11 12l-6 8.5 7 4.2 3.2-4.5V39h17.6V20.2l3.2 4.5 7-4.2L37 12l-6-2.5c-1 3-3.5 5-7 5s-6-2-7-5Z" />
                <path d="M18 10.5h12" />
            </svg>
            @break
        @case('bulk')
            <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 48 48" {!! $common !!}>
                <circle cx="24" cy="14" r="6" />
                <circle cx="10.5" cy="18.5" r="4.5" />
                <circle cx="37.5" cy="18.5" r="4.5" />
                <path d="M14 39v-4.5C14 28.7 18.5 25 24 25s10 3.7 10 9.5V39H14Z" />
                <path d="M3.5 39v-3.5c0-5 3.2-8 7.6-8 1.6 0 3 .4 4.2 1.1M44.5 39v-3.5c0-5-3.2-8-7.6-8-1.6 0-3 .4-4.2 1.1" />
            </svg>
            @break
        @case('cart')
            <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 48 48" {!! $common !!}>
                <path d="M7 8h5l4 20h19l4-14H15" />
                <path d="M18 20h17" />
                <circle cx="19" cy="36" r="2.4" />
                <circle cx="34" cy="36" r="2.4" />
            </svg>
            @break
        @case('pencil')
            <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 48 48" {!! $common !!}>
                <path d="m10 34-1 6 6-1 24-24-5-5-24 24Z" />
                <path d="m30 14 5 5" />
            </svg>
            @break
        @case('review')
            <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 48 48" {!! $common !!}>
                <rect x="11" y="7" width="26" height="34" rx="2" />
                <path d="M17 15h14M17 22h14M17 29h10" />
            </svg>
            @break
        @case('box')
            <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 48 48" {!! $common !!}>
                <path d="m8 16 16-8 16 8-16 8-16-8Z" />
                <path d="m8 16v17l16 8 16-8V16" />
                <path d="M24 24v17M16 12l16 8" />
            </svg>
            @break
        @case('headset')
            <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 48 48" {!! $common !!}>
                <path d="M8 26v-4C8 13.7 14.7 7 23 7h2c8.3 0 15 6.7 15 15v4" />
                <path d="M8 24h5v13H9.5A3.5 3.5 0 0 1 6 33.5v-6A3.5 3.5 0 0 1 9.5 24ZM40 24h-5v13h3.5a3.5 3.5 0 0 0 3.5-3.5v-6a3.5 3.5 0 0 0-3.5-3.5Z" />
                <path d="M35 37c-2 3-5 4-9 4" />
            </svg>
            @break
        @case('chevron-right')
            <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" {!! $common !!}>
                <path d="m9 18 6-6-6-6" />
            </svg>
            @break
    @endswitch
</span>
