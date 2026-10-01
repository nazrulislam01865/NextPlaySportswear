@props([
    'variant' => 'rewards',
    'actionHref' => null,
])

@php
    $isReferralPage = $variant === 'referral';
    $firstLine = $isReferralPage
        ? 'Your friend gets £5 off their first eligible order of £50 or more.'
        : 'Your friend gets £5 off their first eligible £50 order.';
    $secondLine = $isReferralPage
        ? 'You get £5 off a future eligible order after theirs is complete.'
        : 'You get £5 when their order is complete.';
@endphp

<section class="np-rewards-referral-banner">
    <div class="np-rewards-referral-banner__lead">
        <div class="np-rewards-referral-banner__icon" aria-hidden="true">
            <x-storefront.account.rewards.icon name="account" :size="54" />
            <span>+</span>
        </div>
        <div>
            <h2>GIVE £5. GET £5.</h2>
            <p>{{ $firstLine }}</p>
            <p>{{ $secondLine }}</p>
        </div>
    </div>

    @if ($actionHref)
        <a href="{{ $actionHref }}" class="btn btn-secondary np-rewards-referral-banner__action">
            <span>REFER A FRIEND</span>
            <x-storefront.account.rewards.icon name="arrow-right" :size="20" />
        </a>
    @endif
</section>
