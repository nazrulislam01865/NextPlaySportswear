@props([
    'seo' => [],
    'title',
    'subtitle',
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

            <div class="np-rewards-layout">
                <aside class="np-rewards-layout__sidebar">
                    <x-storefront.account.rewards.sidebar :navigation="$navigation" />
                </aside>

                <div class="np-rewards-layout__content">
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
