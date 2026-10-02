@props([
    'seo' => [],
    'title',
    'subtitle',
    'account' => [],
    'navigation' => [],
])

<x-layouts.storefront :seo="$seo">
    <section class="np-account-prototype-hero" aria-labelledby="account-page-title">
        <div class="np-account-prototype-container">
            <h1 id="account-page-title">{{ $title }}</h1>
            <p>{{ $subtitle }}</p>
        </div>
    </section>

    <section class="np-account-prototype-page">
        <div class="np-account-prototype-container">
            @if (session('status'))
                <div class="np-account-prototype-alert">{{ session('status') }}</div>
            @endif
            @if (session('password_status'))
                <div class="np-account-prototype-alert">{{ session('password_status') }}</div>
            @endif
            @if ($errors->any())
                <div class="np-account-prototype-alert np-account-prototype-alert--error">
                    <strong>Please review the information below.</strong>
                    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="np-account-prototype-layout">
                <x-storefront.account.sidebar :account="$account" :navigation="$navigation" />
                <div class="np-account-prototype-content np-account-square-cards">{{ $slot }}</div>
            </div>
        </div>
    </section>
</x-layouts.storefront>
