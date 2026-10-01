<x-layouts.storefront :seo="$seo">
    @php
        $address = (array) data_get($order, 'shipping_address.address', []);
        $referralDiscount = (float) data_get($order, 'totals.referral_discount', data_get($order, 'information.referral_offer.amount', 0));
    @endphp

    <section class="np-referral-confirmation-page">
        <div class="site-container">
            <nav class="np-referral-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span>/</span><a href="{{ route('cart.index') }}">Basket</a><span>/</span><span>Checkout</span><span>/</span><strong>Confirmation</strong></nav>

            <div class="np-referral-confirmation-top">
                <div class="np-referral-confirmation-message">
                    <span class="np-referral-confirmation-check"><x-storefront.referral.icon name="check" :size="48" /></span>
                    <div><h1>THANK YOU — YOUR ORDER<br>IS CONFIRMED</h1><p>We've emailed your order details to you.</p><div class="np-referral-confirmation-meta"><strong>Order #{{ $order['order_number'] }}</strong><span></span><p>Confirmation sent to<br>{{ $order['customer_email'] }}</p></div></div>
                </div>
                <x-storefront.referral.stepper :current="3" />
            </div>

            <div class="np-referral-confirmation-actions"><a href="{{ route('orders.track') }}" class="btn btn-primary">TRACK YOUR ORDER</a><a href="{{ route('products.index') }}" class="btn btn-outline">CONTINUE SHOPPING</a></div>

            <div class="np-referral-confirmation-grid">
                <div class="np-referral-confirmation-main">
                    <div class="np-referral-linked-banner"><span><x-storefront.referral.icon name="gift" :size="28" /></span><strong>Your £5 first-order reward was applied.</strong></div>

                    <section class="np-referral-confirmation-panel">
                        <h2>ITEMS IN YOUR ORDER</h2>
                        @foreach ($order['items'] as $item)
                            <div class="np-referral-confirmation-item">
                                <img src="{{ $item['image'] ?? asset('images/product-placeholder.svg') }}" alt="{{ $item['alt'] ?? $item['title'] }}" loading="lazy">
                                <div><strong>{{ $item['title'] }}</strong><p>{{ data_get($item, 'customization.size_summary') }}</p><p>{{ data_get($item, 'customization.notes') }}</p></div>
                                <div><b>${{ number_format((float) $item['line_total'], 2) }}</b><span>Qty: {{ $item['quantity'] }}</span></div>
                            </div>
                        @endforeach
                    </section>

                    <section class="np-referral-confirmation-panel">
                        <h2>WHAT HAPPENS NEXT</h2>
                        <div class="np-referral-next-list">
                            <div><span>1</span><div><strong>Order received</strong><p>We've got your order and it's being processed.</p></div></div>
                            <div><span>2</span><div><strong>Production updates</strong><p>We'll keep you updated by email as your order progresses.</p></div></div>
                            <div><span>3</span><div><strong>Delivery updates</strong><p>We'll let you know when your order is on its way.</p></div></div>
                        </div>
                    </section>
                </div>

                <aside class="np-referral-confirmation-side">
                    <section class="np-referral-confirmation-summary">
                        <h2>ORDER SUMMARY</h2>
                        <div><p><span>Items subtotal ({{ $order['totals']['quantity'] }} items)</span><strong>${{ number_format((float) $order['totals']['subtotal'] + (float) $order['totals']['customization_total'], 2) }}</strong></p><p class="is-reward"><span>Referral reward</span><strong>−${{ number_format($referralDiscount, 2) }}</strong></p><p><span>Delivery</span><strong>${{ number_format((float) $order['totals']['shipping'], 2) }}</strong></p><hr><p class="is-total"><span>Order total</span><strong>${{ number_format((float) $order['totals']['total'], 2) }}</strong></p></div>
                        <div class="np-referral-summary-info"><x-storefront.referral.icon name="info" :size="24" /><p><b>This is a confirmation of your order.</b><br>You'll receive email updates as your order progresses.</p></div>
                    </section>

                    <section class="np-referral-confirmation-panel np-referral-address-panel">
                        <h2>DELIVERY ADDRESS</h2>
                        <address>
                            {{ trim(($address['first_name'] ?? '').' '.($address['last_name'] ?? '')) }}<br>
                            {{ $address['address_line_1'] ?? '' }}<br>
                            @if (!empty($address['address_line_2'])){{ $address['address_line_2'] }}<br>@endif
                            {{ $address['city'] ?? '' }} {{ $address['postal_code'] ?? '' }}<br>
                            {{ $address['country'] ?? '' }}
                        </address>
                        <hr><p>Need to make changes? Contact our support team as soon as possible.</p><a href="{{ route('contact') }}">Email support</a>
                    </section>

                    <div class="np-referral-final-note"><x-storefront.referral.icon name="gift" :size="28" /><p>Your friend's referral reward will be issued after this eligible order is completed and the returns period has passed.</p></div>
                </aside>
            </div>
        </div>
    </section>
</x-layouts.storefront>
