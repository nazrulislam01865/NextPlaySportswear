@props(['variant' => 'sidebar'])

@php
    $isLogin = $variant === 'login';
    $hasCustomLogo = \App\Support\StorefrontBranding::hasCustomLogo();
@endphp

@if($hasCustomLogo)
    @if($isLogin)
        <span class="flex min-w-0 flex-col items-start justify-center gap-1">
            <img
                src="{{ \App\Support\StorefrontBranding::logoUrl() }}"
                alt="{{ config('storefront.name', 'NextPlay Sportswear') }}"
                class="block w-auto max-w-full object-contain object-left"
                style="max-width:220px;max-height:54px;background:transparent"
            >
            <span class="block text-[10px] font-black uppercase tracking-[.25em] text-slate-400">Commerce Admin</span>
        </span>
    @else
        <span class="flex min-w-0 flex-col items-start justify-center" style="max-width:100%;min-width:0;overflow:hidden;gap:3px;flex:1 1 auto">
            <img
                src="{{ \App\Support\StorefrontBranding::logoUrl() }}"
                alt="{{ config('storefront.name', 'NextPlay Sportswear') }}"
                style="display:block;width:auto;height:34px;max-width:100%;object-fit:contain;object-position:left center;background:transparent;flex:none"
            >
            <span class="block text-[10px] font-black uppercase tracking-[.25em] text-slate-400" style="display:block;max-width:100%;min-width:0;line-height:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:none">Commerce Admin</span>
        </span>
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
