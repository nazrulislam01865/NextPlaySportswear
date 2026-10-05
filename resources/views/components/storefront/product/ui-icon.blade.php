@props([
    'src' => null,
    'name' => 'info',
])

@if(filled($src))
    <span
        {{ $attributes->class(['np-configurable-ui-icon']) }}
        style="--np-configurable-icon: url('{{ $src }}')"
        aria-hidden="true"
    ></span>
@else
    <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'aria-hidden' => 'true']) }}>
        @switch($name)
            @case('heart')
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z" />
                @break
            @case('share')
                <path d="M18 16a3 3 0 0 0-2.4 1.2l-6.8-3.4a3.1 3.1 0 0 0 0-3.6l6.8-3.4A3 3 0 1 0 15 5c0 .2 0 .4.1.6L8.2 9a3 3 0 1 0 0 6l6.9 3.4A3 3 0 1 0 18 16Z" />
                @break
            @case('sample')
                <path d="M7 3h10v4l-2 3v8a3 3 0 0 1-3 3h0a3 3 0 0 1-3-3v-8L7 7V3Z"/><path d="M9 7h6"/>
                @break
            @case('sizes')
                <path d="M8 4 4 7l3 4v9h10v-9l3-4-4-3-2 3h-4L8 4Z"/>
                @break
            @case('box')
                <path d="m12 3 8 4-8 4-8-4 8-4Z"/><path d="m4 7 8 4 8-4v10l-8 4-8-4V7Z"/>
                @break
            @case('document')
                <path d="M6 3h9l3 3v15H6V3Z"/><path d="M9 10h6M9 14h6M9 18h4"/>
                @break
            @case('upload')
                <path d="M12 16V4M8 8l4-4 4 4"/><path d="M5 14a4 4 0 0 0 1 8h12a4 4 0 0 0 1-8"/>
                @break
            @case('image')
                <rect x="3" y="4" width="18" height="16" rx="2"/><path d="m6 16 4-4 3 3 2-2 3 3"/>
                @break
            @case('message')
                <path d="M4 5h16v11H8l-4 4V5Z"/><path d="M8 10h.01M12 10h.01M16 10h.01"/>
                @break
            @case('factory')
                <path d="M5 21V10l4 4V8l4 4V3h3v18H5Z"/>
                @break
            @case('truck')
                <path d="M3 6h12v10H3z"/><path d="M15 10h4l2 3v3h-6z"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>
                @break
            @case('calendar')
                <rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/>
                @break
            @case('globe')
                <circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 4 6 4 9s-1 6-4 9M12 3c-3 3-4 6-4 9s1 6 4 9"/>
                @break
            @default
                <circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>
        @endswitch
    </svg>
@endif

@once
    <style>
        .np-configurable-ui-icon {
            display: inline-block;
            width: 1.35rem;
            height: 1.35rem;
            flex: 0 0 auto;
            background: currentColor;
            -webkit-mask: var(--np-configurable-icon) center / contain no-repeat;
            mask: var(--np-configurable-icon) center / contain no-repeat;
        }
        .np-product-signal-button .np-configurable-ui-icon { width: 1.2rem; height: 1.2rem; }
        .np-product-order-facts .np-configurable-ui-icon,
        .np-proto-size-summary-fact .np-configurable-ui-icon,
        .np-proto-size-summary-note .np-configurable-ui-icon { color: #cf5d38; }
        .np-proto-artwork-methods .np-configurable-ui-icon { width: 1.7rem; height: 1.7rem; }
        .np-proto-upload-dropzone .np-configurable-ui-icon { width: 2.5rem; height: 2.5rem; }
        .np-proto-info-panel .np-configurable-ui-icon { width: 1.35rem; height: 1.35rem; color: #cf5d38; }
        .np-proto-section-title .np-configurable-ui-icon { width: 22px; height: 22px; }
        .np-proto-choice-media .np-configurable-ui-icon { width: 52px; height: 52px; color: var(--np-color-secondary); }
        .np-proto-delivery-equation .np-configurable-ui-icon { width: 30px; height: 30px; color: var(--np-color-primary); }
        .np-proto-delivery-equation .is-estimate .np-configurable-ui-icon { color: var(--np-color-secondary); }
        .np-proto-worldwide-panel .np-configurable-ui-icon { width: 34px; height: 34px; color: var(--np-color-primary); }
        .np-proto-important-notes > .np-configurable-ui-icon { width: 26px; height: 26px; color: var(--np-color-secondary); }
    </style>
@endonce
