@props([
    'seo' => [], 'steps' => [], 'currentStep' => 'information',
    'title' => 'Checkout', 'description' => '', 'summary' => [], 'orderIdempotencyKey' => null,
])

<x-layouts.storefront :seo="$seo">
    <section class="np-checkout">
        <div class="site-container np-checkout__container">
            <nav class="np-checkout__breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Home</a><span>/</span>
                <a href="{{ route('cart.index') }}">Cart</a><span>/</span><span>Checkout</span>
            </nav>
            <div class="np-checkout__heading">
                <h1>Checkout</h1>
                <span class="np-checkout__secure"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>Secure checkout</span>
            </div>
            <x-storefront.checkout.stepper :steps="$steps" :current-step="$currentStep" />

            @if(session('status'))
                <div class="np-checkout__status" role="status">{{ session('status') }}</div>
            @endif
            @if($errors->has('checkout') || $errors->has('payment_method') || $errors->has('coupon_code'))
                <div class="np-checkout__alert" role="alert">{{ $errors->first('checkout') ?: ($errors->first('payment_method') ?: $errors->first('coupon_code')) }}</div>
            @endif
            <div class="np-checkout__columns">
                <div class="np-checkout__body">{{ $slot }}</div>
                <x-storefront.checkout.summary-card :summary="$summary" :current-step="$currentStep" :order-idempotency-key="$orderIdempotencyKey" />
            </div>
        </div>
    </section>
</x-layouts.storefront>
