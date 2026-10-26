@props(['variant' => 'sidebar'])

@php
    $isLogin = $variant === 'login';
    // The sidebar sits on navy and uses the independently uploaded footer logo.
    // Login branding continues to use the main storefront logo.
    $hasCustomLogo = $isLogin
        ? \App\Support\StorefrontBranding::hasCustomLogo()
        : \App\Support\StorefrontBranding::hasFooterImageLogo();
    $logoUrl = $isLogin
        ? \App\Support\StorefrontBranding::logoUrl()
        : \App\Support\StorefrontBranding::footerLogoUrl();
@endphp

@if($hasCustomLogo)
    @if($isLogin)
        <span class="flex min-w-0 flex-col items-start justify-center gap-1">
            <img
                src="{{ $logoUrl }}"
                alt="{{ config('storefront.name', 'NextPlay Sportswear') }}"
                class="block w-auto max-w-full object-contain object-left"
                style="max-width:220px;max-height:54px;background:transparent"
            >
            <span class="block text-[10px] font-black uppercase tracking-[.25em] text-slate-400">Commerce Admin</span>
        </span>
    @else
        {{-- Render only the image: admin.css targets the first direct span of the
             sidebar brand link with a border intended for the old icon. --}}
        <img
            src="{{ $logoUrl }}"
            alt="{{ config('storefront.name', 'NextPlay Sportswear') }}"
            class="block max-w-full object-contain"
            style="display:block;width:88%;height:auto;max-width:100%;max-height:100%;margin-inline:auto;object-fit:contain;object-position:center;background:transparent;border:0!important;box-shadow:none!important;border-radius:0!important"
        >
    @endif
@elseif($isLogin)
    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl border-2 border-brand-red font-black text-brand-red">✓</span>
    <span class="min-w-0">
        <span class="block text-xl font-black text-brand-dark">NextPlay</span>
        <span class="block text-xs font-black uppercase tracking-[.2em] text-slate-400">Commerce Admin</span>
    </span>
@else
    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border-2 border-brand-red text-brand-red">✓</span>
    <span class="min-w-0">
        <span class="block truncate text-lg">NextPlay</span>
        <span class="block truncate text-[10px] uppercase tracking-[.25em] text-slate-400">Commerce Admin</span>
    </span>
@endif
