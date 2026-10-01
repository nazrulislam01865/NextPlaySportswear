<x-layouts.storefront :seo="$seo">
    @php
        $offer = (array) ($cart['referral_offer'] ?? []);
        $rewardApplied = (bool) ($offer['applied'] ?? false);
        $checkoutUrl = route('checkout.referral');
    @endphp

    <section class="np-referral-cart-page">
        <div class="site-container">
            <nav class="np-referral-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span>/</span><strong>Basket</strong></nav>

            <div class="np-referral-cart-top">
                <div><h1>YOUR BASKET</h1><span>{{ $cart['quantity'] }} item{{ $cart['quantity'] === 1 ? '' : 's' }}</span></div>
                <x-storefront.referral.stepper :current="1" />
            </div>

            @if (session('status'))<div class="np-referral-form-alert np-referral-form-alert--success">{{ session('status') }}</div>@endif
            @if ($errors->any())<div class="np-referral-form-alert">{{ $errors->first() }}</div>@endif

            @if ($cart['is_empty'])
                <div class="np-referral-empty-cart">
                    <span><x-storefront.referral.icon name="cart" :size="52" /></span>
                    <h2>Your basket is empty</h2>
                    <a href="{{ route('products.index') }}" class="btn btn-secondary">SHOP NEXTPLAY</a>
                </div>
            @else
                <div class="np-referral-cart-grid">
                    <div class="np-referral-cart-main">
                        <div class="np-referral-linked-banner">
                            <span><x-storefront.referral.icon name="gift" :size="26" /></span>
                            <strong>
                                @if (($offer['customer_status'] ?? '') === 'ineligible')
                                    This referral offer is for new customers only.
                                @elseif ($rewardApplied)
                                    Your friend's £5 offer is linked to this visit.
                                @else
                                    Your friend's £5 offer is linked. Spend at least £50 on eligible items to activate it.
                                @endif
                            </strong>
                        </div>

                        <div class="np-referral-cart-items">
                            @foreach ($cart['items'] as $item)
                                @php
                                    $product = (array) ($item['product'] ?? []);
                                    $quantity = max(1, (int) ($item['quantity'] ?? 1));
                                    $min = max(1, (int) ($item['quantity_min'] ?? 1));
                                    $max = max($min, (int) ($item['quantity_max'] ?? 999));
                                    $lineTotal = (float) ($item['line_total'] ?? 0);
                                    $customization = (array) ($item['customization'] ?? []);
                                    $sizeSummary = (string) ($customization['size_summary'] ?? data_get($customization, 'configuration.size', ''));
                                @endphp
                                <article class="np-referral-cart-item">
                                    <a href="{{ $product['url'] ?? route('products.index') }}" class="np-referral-cart-item__image"><img src="{{ $product['image'] ?? asset('images/product-placeholder.svg') }}" alt="{{ $product['alt'] ?? $product['title'] ?? 'Product' }}" loading="lazy" width="260" height="260"></a>
                                    <div class="np-referral-cart-item__details">
                                        <h2><a href="{{ $product['url'] ?? route('products.index') }}">{{ $product['title'] ?? 'NEXTPLAY product' }}</a></h2>
                                        @if ($sizeSummary !== '')<p>Size: {{ $sizeSummary }}</p>@endif
                                        <p>Customisation details saved</p>
                                        <a href="{{ $product['url'] ?? route('products.index') }}" class="np-referral-cart-item__edit">Edit customisation</a>
                                    </div>
                                    <strong class="np-referral-cart-item__price">${{ number_format($lineTotal, 2) }}</strong>
                                    <div class="np-referral-cart-item__actions">
                                        <div class="np-referral-qty">
                                            <form method="POST" action="{{ route('cart.items.update', $item['key']) }}">@csrf @method('PATCH')<input type="hidden" name="quantity" value="{{ max($min, $quantity - 1) }}"><button type="submit" aria-label="Decrease quantity" @disabled($quantity <= $min)>−</button></form>
                                            <span>{{ $quantity }}</span>
                                            <form method="POST" action="{{ route('cart.items.update', $item['key']) }}">@csrf @method('PATCH')<input type="hidden" name="quantity" value="{{ min($max, $quantity + 1) }}"><button type="submit" aria-label="Increase quantity" @disabled($quantity >= $max)>+</button></form>
                                        </div>
                                        <form method="POST" action="{{ route('cart.items.destroy', $item['key']) }}">@csrf @method('DELETE')<button type="submit" class="np-referral-remove">Remove</button></form>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <a href="{{ route('products.index') }}" class="btn btn-outline np-referral-continue"><x-storefront.referral.icon name="arrow-left" :size="20" /> CONTINUE SHOPPING</a>
                    </div>

                    <aside class="np-referral-cart-summary">
                        <h2>ORDER SUMMARY</h2>
                        <div class="np-referral-cart-summary__body">
                            <div class="np-referral-summary-row"><span>Items subtotal ({{ $cart['quantity'] }} items)</span><strong>${{ number_format((float) ($cart['configured_items_total'] ?? $cart['merchandise_total']), 2) }}</strong></div>
                            <div class="np-referral-summary-row np-referral-summary-row--reward"><span>Friend referral reward<small>First eligible order of £50 or more.<br><a href="{{ route('terms') }}">Offer terms</a></small></span><strong>{{ $rewardApplied ? '−$'.number_format((float) $cart['referral_discount'], 2) : '$0.00' }}</strong></div>
                            <div class="np-referral-summary-row"><span>Delivery</span><span>Calculated at checkout</span></div>
                            <div class="np-referral-summary-divider"></div>
                            <div class="np-referral-summary-total"><span>Estimated total<small>before delivery</small></span><strong>${{ number_format((float) ($cart['estimated_subtotal'] ?? $cart['total']), 2) }}</strong></div>
                            <div class="np-referral-summary-info"><x-storefront.referral.icon name="info" :size="24" /><p>The reward applies while your eligible items total at least £50. If your total falls below £50, the reward will be removed.</p></div>
                            <a href="{{ $checkoutUrl }}" class="btn btn-secondary np-referral-checkout-button">PROCEED TO CHECKOUT <x-storefront.referral.icon name="arrow-right" :size="20" /></a>
                        </div>
                    </aside>
                </div>
            @endif
        </div>
    </section>
</x-layouts.storefront>
