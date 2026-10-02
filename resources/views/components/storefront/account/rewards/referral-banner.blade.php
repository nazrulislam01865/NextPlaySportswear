@props([
    'variant' => 'rewards',
    'actionHref' => null,
    'friendReward' => 5,
    'referrerReward' => 5,
    'minimumOrder' => 50,
])

@php
    $isReferralPage = $variant === 'referral';
    $give = number_format((float) $friendReward, ((float) $friendReward == floor((float) $friendReward)) ? 0 : 2);
    $get = number_format((float) $referrerReward, ((float) $referrerReward == floor((float) $referrerReward)) ? 0 : 2);
    $minimum = number_format((float) $minimumOrder, ((float) $minimumOrder == floor((float) $minimumOrder)) ? 0 : 2);
    $firstLine = $isReferralPage
        ? "Your friend gets £{$give} off their first eligible order of £{$minimum} or more."
        : "Your friend gets £{$give} off their first eligible £{$minimum} order.";
    $secondLine = $isReferralPage
        ? "You get £{$get} off a future eligible order after theirs is complete."
        : "You get £{$get} when their order is complete.";
@endphp

<section class="np-rewards-referral-banner">
    <div class="np-rewards-referral-banner__lead">
        <div class="np-rewards-referral-banner__icon" aria-hidden="true">
            <x-storefront.account.rewards.icon name="account" :size="54" />
            <span>+</span>
        </div>
        <div>
            <h2>GIVE £{{ $give }}. GET £{{ $get }}.</h2>
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
