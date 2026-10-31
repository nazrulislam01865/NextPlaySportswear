@php
    $bill = ($state['billing_address']['same_as_shipping'] ?? true)
        ? ($state['shipping_address']['address'] ?? [])
        : ($state['billing_address']['address'] ?? []);
    $amount = '$'.number_format((float) ($summary['total'] ?? 0), 2);
@endphp
<x-storefront.checkout.shell :seo="$seo" :steps="$steps" :current-step="$currentStep" :summary="$summary">
    <x-storefront.checkout.panel title="Payment" description="Complete your order with a secure payment.">
        <div class="np-checkout__stripe">
            <div class="np-checkout__stripe-head"><strong>♙ &nbsp; Secure payment with Stripe</strong><strong class="np-checkout__stripe-brand">stripe</strong></div>
            <p>You'll be securely redirected to Stripe to enter your payment details.<br>We never see or store your card information.</p>
            <div class="np-checkout__stripe-methods" aria-label="Payment methods handled by Stripe"><span>VISA</span><span>Mastercard</span><span>AMEX</span><span>Apple Pay</span><span>Google Pay</span></div>
            <div class="np-checkout__stripe-billing"><div><strong>Billing address</strong><p>{{ trim(($bill['first_name'] ?? '').' '.($bill['last_name'] ?? '')) }} · {{ $bill['address_line_1'] ?? '' }}, {{ $bill['city'] ?? '' }}, {{ $bill['postal_code'] ?? '' }}, {{ $bill['country'] ?? '' }}</p></div><a href="{{ route('checkout.billing-address') }}">Edit</a></div>
            <div class="np-checkout__stripe-foot"><span>♙ &nbsp; Payments are securely processed by Stripe.</span><span>Powered by <strong>stripe</strong></span></div>
        </div>
        <div class="np-checkout__notice np-checkout__info"><span>ⓘ</span><div><strong>Review before you pay</strong><p>You'll review your order on Stripe's secure payment page before confirming.</p></div></div>
        <form class="nps-form np-checkout__form" method="POST" action="{{ route('checkout.place-order.submit') }}" data-single-submit>
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ $orderIdempotencyKey }}">
            <input type="hidden" name="confirm_details" value="1">
            <input type="hidden" name="terms" value="1">
            <div class="np-checkout__actions"><a href="{{ route('checkout.review') }}" class="np-checkout__back">← &nbsp; Back to review</a><button class="np-checkout__button" type="submit" @disabled($summary['remote_price_missing'] ?? false)>Pay {{ $amount }} securely with Stripe &nbsp; →</button></div>
        </form>
    </x-storefront.checkout.panel>
</x-storefront.checkout.shell>
