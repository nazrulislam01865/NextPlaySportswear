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
    <section class="np-rewards-page">
        <div class="site-container">
            <nav class="np-rewards-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('account.dashboard') }}">My Account</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">{{ $breadcrumb }}</span>
            </nav>

            <div class="np-account-prototype-layout np-rewards-account-layout">
                <x-storefront.account.sidebar :account="$account" :navigation="$navigation" />

                <div class="np-rewards-layout__content np-account-square-cards">
                    <header class="np-rewards-page-heading">
                        <div>
                            <h1>{{ $title }}</h1>
                            <p>{{ $subtitle }}</p>
                        </div>
                        @if ($badge)
                            <span class="np-rewards-page-heading__badge">{{ $badge }}</span>
                        @endif
                    </header>

                    {{ $slot }}
                </div>
            </div>
        </div>
    </section>
</x-layouts.storefront>
