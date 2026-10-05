@props([
    'name',
    'size' => 22,
])

@php($px = max(16, min(40, (int) $size)))

<svg {{ $attributes->merge([
    'width' => $px,
    'height' => $px,
    'viewBox' => '0 0 24 24',
    'fill' => 'none',
    'stroke' => 'currentColor',
    'stroke-width' => '1.8',
    'stroke-linecap' => 'round',
    'stroke-linejoin' => 'round',
    'aria-hidden' => 'true',
]) }}>
    @switch($name)
        @case('user')
            <circle cx="12" cy="7.5" r="3.2" />
            <path d="M5.4 20a6.6 6.6 0 0 1 13.2 0" />
            @break
        @case('box')
            <path d="m4 7 8-4 8 4v10l-8 4-8-4V7Z" />
            <path d="m4 7 8 4 8-4M12 11v10M8 5l8 4" />
            @break
        @case('truck')
            <path d="M3 6h11v10H3V6Z" />
            <path d="M14 10h4l3 3v3h-7v-6Z" />
            <circle cx="7" cy="18" r="2" />
            <circle cx="18" cy="18" r="2" />
            @break
        @case('attachment')
            <path d="m9.5 12.5 5.9-5.9a3.2 3.2 0 0 1 4.5 4.5l-7.8 7.8a5 5 0 0 1-7.1-7.1l7.4-7.4" />
            <path d="m8.1 13.9 6.9-6.9" />
            @break
        @case('upload')
            <path d="M12 16V4" />
            <path d="m7.5 8.5 4.5-4.5 4.5 4.5" />
            <path d="M5 13v5a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-5" />
            @break
        @case('shield-check')
            <path d="M12 3 19 6v5c0 4.6-2.7 8.1-7 10-4.3-1.9-7-5.4-7-10V6l7-3Z" />
            <path d="m9.2 12.2 1.8 1.8 3.9-4.1" />
            @break
        @case('arrow-right')
            <path d="M5 12h14" />
            <path d="m14 7 5 5-5 5" />
            @break
        @case('logo')
            <rect x="4" y="5" width="16" height="14" rx="2" />
            <circle cx="9" cy="10" r="1.4" />
            <path d="m6.5 16 3.6-3.7 2.6 2.5 1.8-1.8 3 3" />
            @break
        @case('numbers')
            <path d="M8 4 6 20M14 4l-2 16M4 9h14M3 15h14" />
            @break
        @case('embroidery')
            <path d="m5 19 14-14M8 4l12 12M4 8l12 12" />
            <path d="M5 5h4v4H5zM15 15h4v4h-4z" />
            @break
        @case('drop')
            <path d="M12 3s5 5.6 5 10a5 5 0 0 1-10 0c0-4.4 5-10 5-10Z" />
            @break
        @case('custom')
            <path d="M4 17.5V20h2.5L18.8 7.7l-2.5-2.5L4 17.5Z" />
            <path d="m14.8 6.7 2.5 2.5M12 20h8" />
            @break
        @default
            <circle cx="12" cy="12" r="8" />
    @endswitch
</svg>
