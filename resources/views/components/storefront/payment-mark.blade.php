@props([
    'name' => 'Payment method',
    'provider' => '',
    'code' => '',
    'iconUrl' => null,
    'iconAlt' => null,
    'bare' => false,
])

@php
    $labelIdentity = strtolower(trim($code.' '.$name));
    $providerIdentity = strtolower(trim($provider));
    $brand = match (true) {
        str_contains($labelIdentity, 'paypal') => 'paypal',
        str_contains($labelIdentity, 'apple pay'), str_contains($labelIdentity, 'apple-pay') => 'applepay',
        str_contains($labelIdentity, 'google pay'), str_contains($labelIdentity, 'google-pay'), str_contains($labelIdentity, 'gpay') => 'googlepay',
        str_contains($labelIdentity, 'mastercard'), str_contains($labelIdentity, 'master card') => 'mastercard',
        str_contains($labelIdentity, 'visa') => 'visa',
        str_contains($labelIdentity, 'american express'), str_contains($labelIdentity, 'amex') => 'amex',
        str_contains($labelIdentity, 'stripe'), str_contains($providerIdentity, 'stripe') => 'stripe',
        str_contains($providerIdentity, 'paypal') => 'paypal',
        default => 'generic',
    };
    $alt = trim((string) ($iconAlt ?: $name));
    $wrapperClasses = $bare
        ? 'inline-flex items-center justify-center leading-none'
        : 'inline-flex h-9 min-w-[56px] items-center justify-center rounded-lg border border-slate-200 bg-white px-2.5 shadow-sm';
@endphp

<span
    {{ $attributes->class($wrapperClasses) }}
    title="{{ $name }}"
    aria-label="{{ $name }}"
>
    @if(filled($iconUrl))
        <img
            src="{{ $iconUrl }}"
            alt="{{ $alt }}"
            class="{{ $bare ? 'max-h-6 max-w-[86px]' : 'max-h-5 max-w-[82px]' }} object-contain"
            loading="lazy"
            decoding="async"
        >
    @elseif($brand === 'stripe')
        <span aria-hidden="true" class="text-[17px] font-black lowercase leading-none tracking-[-.08em] text-[#635BFF]">stripe</span>
    @elseif($brand === 'paypal')
        <span aria-hidden="true" class="text-[14px] font-black leading-none tracking-[-.04em] text-[#003087]">Pay<span class="text-[#0070BA]">Pal</span></span>
    @elseif($brand === 'visa')
        <span aria-hidden="true" class="text-[15px] font-black italic leading-none tracking-[-.04em] text-[#1434CB]">VISA</span>
    @elseif($brand === 'mastercard')
        <span aria-hidden="true" class="relative block h-5 w-9">
            <span class="absolute left-0 top-0 h-5 w-5 rounded-full bg-[#EB001B]"></span>
            <span class="absolute right-0 top-0 h-5 w-5 rounded-full bg-[#F79E1B] opacity-95"></span>
        </span>
    @elseif($brand === 'amex')
        <span aria-hidden="true" class="rounded-sm bg-[#006FCF] px-1.5 py-1 text-[9px] font-black leading-none text-white">AMEX</span>
    @elseif($brand === 'applepay')
        <span aria-hidden="true" class="text-[12px] font-black leading-none tracking-tight {{ $bare ? 'text-white' : 'text-black' }}">Apple Pay</span>
    @elseif($brand === 'googlepay')
        <span aria-hidden="true" class="text-[12px] font-black leading-none tracking-tight {{ $bare ? 'text-white' : 'text-slate-900' }}">G Pay</span>
    @else
        <svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-6 fill-none {{ $bare ? 'stroke-slate-300' : 'stroke-brand-dark' }}" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2.5" y="5" width="19" height="14" rx="2.5"></rect>
            <path d="M2.5 9.5h19"></path>
            <path d="M6.5 15h4"></path>
        </svg>
    @endif
    <span class="sr-only">{{ $name }}</span>
</span>
