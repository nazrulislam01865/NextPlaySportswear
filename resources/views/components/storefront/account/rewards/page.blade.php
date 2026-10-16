@props([
    'seo' => [],
    'title',
    'subtitle',
    'account' => [],
    'navigation' => [],
    'breadcrumb',
    'badge' => null,
])

<x-layouts.storefront :seo="$seo">
    <x-storefront.account.frame :title="$title" :subtitle="$subtitle" :account="$account" :navigation="$navigation">
        <nav class="np-rewards-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('account.dashboard') }}">My Account</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">{{ $breadcrumb }}</span>
        </nav>
        @if ($badge)
            <p class="np-account-rewards-badge">{{ $badge }}</p>
        @endif
        {{ $slot }}
    </x-storefront.account.frame>
</x-layouts.storefront>
