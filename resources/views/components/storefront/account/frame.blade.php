@props([
    'title' => 'My Account',
    'subtitle' => '',
    'account' => [],
    'navigation' => [],
    'fullWidth' => false,
    'contentOnly' => false,
])

{{-- Shared presentation for every authenticated My Account screen. --}}
@unless($contentOnly)
<section class="np-account-typography np-account-prototype-hero" aria-labelledby="account-page-title">
    <div class="np-account-prototype-container">
        <h1 id="account-page-title">{{ $title }}</h1>
        <p>{{ $subtitle }}</p>
    </div>
</section>
@endunless

<section class="np-account-typography np-account-prototype-page {{ $contentOnly ? 'np-account-prototype-page--artwork' : '' }}">
    <div class="np-account-prototype-container">
        @if (session('status'))
            <div class="np-account-prototype-alert" role="status">{{ session('status') }}</div>
        @endif
        @if (session('password_status'))
            <div class="np-account-prototype-alert" role="status">{{ session('password_status') }}</div>
        @endif
        @if ($errors->any())
            <div class="np-account-prototype-alert np-account-prototype-alert--error" role="alert">
                <strong>Please review the information below.</strong>
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @if ($fullWidth)
            <div class="np-account-square-cards">{{ $slot }}</div>
        @else
            <div class="np-account-prototype-layout">
                <x-storefront.account.sidebar :account="$account" :navigation="$navigation" />
                <div class="np-account-prototype-content np-account-square-cards" id="account-content">{{ $slot }}</div>
            </div>
        @endif
    </div>
</section>
