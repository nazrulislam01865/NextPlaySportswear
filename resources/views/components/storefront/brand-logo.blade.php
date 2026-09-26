@props([
    'variant' => 'header',
    'alt' => null,
])

@php
    $logoUrl = \App\Support\StorefrontBranding::logoUrl();
    $hasCustomLogo = \App\Support\StorefrontBranding::hasCustomLogo();
    $logoAlt = $alt ?: config('storefront.name', 'NextPlay Sportswear');
    $variant = in_array($variant, ['header', 'footer', 'document', 'error'], true) ? $variant : 'header';

    $imageStyle = match ($variant) {
        'footer' => 'display:block;max-width:190px;width:auto;height:48px;object-fit:contain;object-position:left center;',
        'document' => 'display:block;max-width:210px;width:auto;height:58px;object-fit:contain;object-position:left center;',
        'error' => 'display:block;max-width:210px;width:auto;height:48px;object-fit:contain;object-position:left center;',
        default => 'display:block;max-width:220px;width:auto;height:46px;object-fit:contain;object-position:left center;',
    };
@endphp

@if($hasCustomLogo)
    <img src="{{ $logoUrl }}" alt="{{ $logoAlt }}" style="{{ $imageStyle }}" {{ $attributes }}>
@elseif($variant === 'footer')
    <span class="relative grid h-8 w-8 place-items-center rounded-lg border-[3px] border-brand-red text-brand-red" aria-hidden="true">✓</span>
    <span class="min-w-0 break-words">NextPlay <span class="text-brand-red">Sportswear</span></span>
@elseif($variant === 'document')
    <span class="font-display text-3xl font-bold uppercase">NextPlay Sportswear</span>
@elseif($variant === 'error')
    <span class="mark" aria-hidden="true">✓</span><span>NextPlay <b>Sportswear</b></span>
@else
    <span class="np-brand-mark" aria-hidden="true">
        <svg viewBox="0 0 64 64" width="64" height="64" fill="none">
            <path d="M18 20h28c3.2 0 5.8 2.6 5.8 5.8v24.4c0 3.2-2.6 5.8-5.8 5.8H18c-3.2 0-5.8-2.6-5.8-5.8V25.8C12.2 22.6 14.8 20 18 20Z" stroke="currentColor" stroke-width="4.8" />
            <path d="M22.5 20v-4.2C22.5 9.9 26.5 6 32 6s9.5 3.9 9.5 9.8V20" stroke="currentColor" stroke-width="4.8" stroke-linecap="round" />
            <path d="m22.5 36 7.1 7.5 13.9-17" stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </span>
    <span class="np-brand-text"><span>NEXTPLAY</span> <strong>SPORTSWEAR</strong></span>
@endif
