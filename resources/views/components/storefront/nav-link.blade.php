@props([
    'href' => '#',
    'active' => false,
])

<a
    href="{{ $href }}"
    {{ $attributes->class([
        'whitespace-nowrap transition hover:text-brand-red',
        'text-brand-red' => $active,
    ]) }}
>
    {{ $slot }}
</a>
