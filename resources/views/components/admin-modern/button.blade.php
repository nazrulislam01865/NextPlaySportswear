@props([
    'type' => 'button',
    'variant' => 'secondary',
])

<button
    type="{{ $type }}"
    {{ $attributes->class([
        'admin-modern-button',
        'admin-modern-button-primary' => $variant === 'primary',
        'admin-modern-button-secondary' => $variant === 'secondary',
    ]) }}
>
    {{ $slot }}
</button>
