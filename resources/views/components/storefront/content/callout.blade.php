@props([
    'title',
    'tone' => 'navy',
])

@php
    $classes = match ($tone) {
        'red' => 'border-red-200 bg-red-50 text-red-950',
        'amber' => 'border-amber-200 bg-amber-50 text-amber-950',
        default => 'border-blue-200 bg-blue-50 text-brand-navy',
    };
@endphp

<div class="rounded-2xl border p-5 sm:p-6 {{ $classes }}">
    <h3 class="font-display text-xl font-bold uppercase">{{ $title }}</h3>
    <div class="mt-2 text-sm leading-6 opacity-80">{{ $slot }}</div>
</div>
