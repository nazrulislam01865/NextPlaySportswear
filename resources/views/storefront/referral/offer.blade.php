@php
    $reward = (float) ($offer['reward_amount'] ?? 0);
    $minimum = (float) ($offer['minimum_order'] ?? 0);
    $rewardLabel = number_format($reward, $reward == floor($reward) ? 0 : 2);
    $minimumLabel = number_format($minimum, $minimum == floor($minimum) ? 0 : 2);
@endphp
<x-layouts.storefront :seo="$seo">
    <section class="np-referral-offer-page">
        <div class="np-referral-offer-hero">
            <div class="np-referral-offer-hero__copy">
                <div class="np-referral-offer-hero__inner">
                    <h1>YOUR FRIEND<br>SENT YOU £{{ $rewardLabel }}</h1>
                    <p>New customers get £{{ $rewardLabel }} off one eligible first NEXTPLAY order of £{{ $minimumLabel }} or more.</p>
                    <a href="{{ route('products.index') }}" class="btn btn-secondary np-referral-offer-hero__cta">
                        <span>SHOP NEXTPLAY</span>
                        <x-storefront.referral.icon name="arrow-right" :size="19" />
                    </a>
                    <div class="np-referral-offer-hero__links">
                        <a href="{{ route('register', ['redirect' => route('products.index')]) }}">New here? Create an account</a>
                        <span aria-hidden="true">|</span>
                        <a href="{{ route('login', ['redirect' => route('products.index')]) }}">Already have an account? Sign in</a>
                    </div>
                </div>
            </div>
            <div class="np-referral-offer-hero__image" aria-hidden="true">
                <img src="{{ asset('images/storefront/home/hero-slide-real-team-gear.webp') }}" alt="" width="1100" height="700" fetchpriority="high">
            </div>
        </div>

        <div class="site-container np-referral-offer-body">
            <section class="np-referral-facts" aria-label="Referral offer summary">
                <div class="np-referral-fact">
                    <span class="np-referral-fact__icon"><x-storefront.referral.icon name="gift" :size="38" /></span>
                    <div><small>YOUR OFFER</small><strong>£{{ $rewardLabel }} <span>off</span></strong><p>One eligible first order of £{{ $minimumLabel }} or more for a new customer account.</p></div>
                </div>
                <div class="np-referral-fact">
                    <span class="np-referral-fact__icon"><x-storefront.referral.icon name="bag" :size="38" /></span>
                    <div><small>MINIMUM FIRST ORDER</small><strong>£{{ $minimumLabel }}</strong><p>Minimum spend on your first order.</p></div>
                </div>
                <div class="np-referral-fact">
                    <span class="np-referral-fact__icon"><x-storefront.referral.icon name="card" :size="38" /></span>
                    <div><small>APPLIED AT CHECKOUT</small><strong class="np-referral-fact__text">When eligible</strong><p>Your £{{ $rewardLabel }} offer will be applied when your order qualifies.</p></div>
                </div>
                <p class="np-referral-facts__note"><x-storefront.referral.icon name="info" :size="17" /> Offer subject to programme terms; quoted team orders excluded.</p>
            </section>

            <section class="np-referral-how" aria-labelledby="np-referral-how-title">
                <div class="np-referral-section-heading">
                    <h2 id="np-referral-how-title">HOW TO USE YOUR OFFER</h2>
                    <a href="{{ route('terms') }}">Terms of the offer <x-storefront.referral.icon name="arrow-right" :size="18" /></a>
                </div>
                <div class="np-referral-how__grid">
                    <div class="np-referral-how-step"><span>1</span><div><strong>Open your friend's link</strong><p>Use this referral link and create a new customer account so we can securely link the offer to you.</p></div></div>
                    <div class="np-referral-how-step"><span>2</span><div><strong>Shop an eligible £{{ $minimumLabel }}+ first order</strong><p>Browse NEXTPLAY and add £{{ $minimumLabel }} or more of eligible items to your basket.</p></div></div>
                    <div class="np-referral-how-step"><span>3</span><div><strong>See your £{{ $rewardLabel }} offer at checkout</strong><p>Your £{{ $rewardLabel }} discount will be applied automatically when your order qualifies.</p></div></div>
                </div>
            </section>

            <section class="np-referral-explore" aria-labelledby="np-referral-explore-title">
                <h2 id="np-referral-explore-title">EXPLORE NEXTPLAY</h2>
                <div class="np-referral-explore__grid">
                    <a href="{{ route('products.index', ['q' => 'basketball']) }}" class="np-referral-explore-card">
                        <img src="{{ asset('images/storefront/home/hero-slide-product-lineup.webp') }}" alt="Basketball apparel" loading="lazy" width="760" height="430">
                        <span>BASKETBALL <x-storefront.referral.icon name="arrow-right" :size="21" /></span>
                    </a>
                    <a href="{{ route('products.index', ['q' => 'training']) }}" class="np-referral-explore-card">
                        <img src="{{ asset('images/storefront/about/team-intro.webp') }}" alt="Training apparel" loading="lazy" width="760" height="430">
                        <span>TRAINING <x-storefront.referral.icon name="arrow-right" :size="21" /></span>
                    </a>
                    <a href="{{ route('products.index') }}" class="np-referral-explore-card">
                        <img src="{{ asset('images/storefront/home/hero-slide-team-store.webp') }}" alt="NEXTPLAY teamwear" loading="lazy" width="760" height="430">
                        <span>TEAMWEAR <x-storefront.referral.icon name="arrow-right" :size="21" /></span>
                    </a>
                </div>
            </section>
        </div>
    </section>
</x-layouts.storefront>
