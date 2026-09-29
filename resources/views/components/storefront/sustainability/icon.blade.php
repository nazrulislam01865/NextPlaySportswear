@props(['iconUrl' => null, 'fallback' => 'leaf', 'alt' => '', 'size' => 52, 'class' => ''])
@php($label = trim((string) $alt))
<span {{ $attributes->merge(['class' => 'sustainability-page__icon '.$class]) }} style="--sustainability-icon-size: {{ (int) $size }}px;">
    @if(filled($iconUrl))
        <img src="{{ $iconUrl }}" alt="{{ $label }}" loading="lazy" decoding="async">
    @else
        @switch($fallback)
            @case('partners')
                <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="{{ $label }}"><circle cx="24" cy="22" r="8"/><circle cx="43" cy="24" r="7"/><path d="M9 49c0-9 6-15 15-15s15 6 15 15"/><path d="M34 38c2.5-3 5.5-5 9.5-5 7 0 12.5 5 12.5 13v3"/></svg>
                @break
            @case('box')
                <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="{{ $label }}"><path d="M12 20 32 10l20 10-20 10-20-10Z"/><path d="M12 20v24l20 10 20-10V20"/><path d="M32 30v24"/></svg>
                @break
            @default
                <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="{{ $label }}"><path d="M50 10C28 12 14 24 13 43c11-6 19-12 26-23-5 14-13 24-25 31"/><path d="M13 43c8 3 18 1 25-6 9-9 11-21 12-27Z"/></svg>
        @endswitch
    @endif
</span>
