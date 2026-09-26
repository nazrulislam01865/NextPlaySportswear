@props(['cart'])

@php
    $message = session('coupon_error') ?: ($cart['coupon_error'] ?? null);
    $couponApplied = filled($cart['coupon_code'] ?? null);
@endphp

<aside class="np-cart-summary" data-cart-summary-root>
    <h2>Order Summary</h2>

    <dl class="np-cart-summary-lines">
        <div>
            <dt>Items (<span data-cart-quantity>{{ $cart['quantity'] }}</span>)</dt>
            <dd data-cart-money="merchandise_total">${{ number_format((float) $cart['merchandise_total'], 2) }}</dd>
        </div>
        <div>
            <dt>Shipping</dt>
            <dd class="np-cart-summary-shipping">Calculated at checkout</dd>
        </div>
    </dl>

    <div class="np-cart-summary-total">
        <span>Estimated subtotal</span>
        <strong data-cart-money="estimated_subtotal">${{ number_format((float) ($cart['estimated_subtotal'] ?? max(0, $cart['merchandise_total'] - $cart['discount'])), 2) }}</strong>
    </div>

    <div class="np-cart-summary-actions">
        <a
            href="{{ route('checkout.index') }}"
            class="btn btn-primary w-full {{ $cart['checkout_ready'] ? '' : 'pointer-events-none opacity-50' }}"
            data-checkout-link
        >Proceed to Checkout</a>
        <a href="{{ route('products.index') }}" class="btn btn-outline w-full">Continue Shopping</a>
    </div>

    <details class="np-cart-promo" data-promo-details @if($message || $couponApplied) open @endif>
        <summary>
            <span>Have a promo code?</span>
            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                <path d="m6 8 4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </summary>

        <div class="np-cart-promo-content">
            <div class="np-cart-coupon-pill {{ $couponApplied ? '' : 'hidden' }}" data-coupon-pill>
                <span>Coupon: <strong data-coupon-code>{{ $cart['coupon_code'] }}</strong></span>
                @if (! $cart['is_preview'])
                    <form method="POST" action="{{ route('cart.coupon.destroy') }}" data-coupon-remove-form>
                        @csrf
                        @method('DELETE')
                        <button type="submit">Remove</button>
                    </form>
                @endif
            </div>

            <div
                class="np-cart-coupon-feedback {{ $message ? '' : 'hidden' }} {{ session('coupon_error') || ($cart['coupon_error'] ?? null) ? 'np-cart-coupon-feedback--error' : '' }}"
                data-coupon-feedback
            >
                {{ $message }}
            </div>

            @if (! $cart['is_preview'])
                <form method="POST" action="{{ route('cart.coupon.apply') }}" class="np-cart-coupon-form" data-coupon-form>
                    @csrf
                    <label for="coupon_code">Promo code</label>
                    <div>
                        <input
                            id="coupon_code"
                            name="coupon_code"
                            type="text"
                            placeholder="TEAM10"
                            value=""
                            data-coupon-input
                            autocomplete="off"
                            maxlength="60"
                        >
                        <button class="btn btn-light btn-sm" type="submit" data-coupon-apply-button>Apply</button>
                    </div>
                </form>
            @endif
        </div>
    </details>
</aside>

@if (! $cart['is_preview'])
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-cart-summary-root]').forEach((root) => {
            const form = root.querySelector('[data-coupon-form]');
            const removeForm = root.querySelector('[data-coupon-remove-form]');
            const feedback = root.querySelector('[data-coupon-feedback]');
            const pill = root.querySelector('[data-coupon-pill]');
            const couponCode = root.querySelector('[data-coupon-code]');
            const input = root.querySelector('[data-coupon-input]');
            const applyButton = root.querySelector('[data-coupon-apply-button]');
            const checkoutLink = root.querySelector('[data-checkout-link]');
            const promoDetails = root.querySelector('[data-promo-details]');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const money = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });

            const setFeedback = (message, type = 'success') => {
                if (!feedback) return;
                feedback.textContent = message || '';
                feedback.classList.toggle('hidden', !message);
                feedback.classList.toggle('np-cart-coupon-feedback--error', type !== 'success');
                if (message && promoDetails) promoDetails.open = true;
            };

            const setBusy = (busy) => {
                if (!applyButton) return;
                applyButton.disabled = busy;
                applyButton.classList.toggle('opacity-60', busy);
                applyButton.textContent = busy ? 'Checking...' : 'Apply';
            };

            const refreshTotals = (cart) => {
                if (!cart) return;
                root.querySelectorAll('[data-cart-money]').forEach((node) => {
                    const key = node.getAttribute('data-cart-money');
                    const prefix = node.getAttribute('data-cart-money-prefix') || '';
                    const value = Number(cart[key] || 0);
                    node.textContent = `${prefix}${money.format(value)}`;
                });

                const quantityNode = root.querySelector('[data-cart-quantity]');
                if (quantityNode) quantityNode.textContent = String(Number(cart.quantity || 0));

                if (pill) pill.classList.toggle('hidden', !cart.coupon_code);
                if (couponCode) couponCode.textContent = cart.coupon_code || '';
                if (cart.coupon_code && promoDetails) promoDetails.open = true;
                if (checkoutLink) {
                    checkoutLink.classList.toggle('pointer-events-none', !cart.checkout_ready);
                    checkoutLink.classList.toggle('opacity-50', !cart.checkout_ready);
                }
            };

            const request = async (url, options = {}) => {
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        ...(options.headers || {}),
                    },
                    ...options,
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) {
                    const message = payload.message || Object.values(payload.errors || {})?.[0]?.[0] || 'Unable to validate this promo code.';
                    const error = new Error(message);
                    error.payload = payload;
                    throw error;
                }
                return payload;
            };

            form?.addEventListener('submit', async (event) => {
                event.preventDefault();
                const code = String(input?.value || '').trim();
                if (!code) {
                    setFeedback('Enter a promo code before applying.', 'error');
                    input?.focus();
                    return;
                }

                setBusy(true);
                setFeedback('', 'success');

                try {
                    const payload = await request(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                    });
                    refreshTotals(payload.cart);
                    setFeedback(payload.message || 'Promo code applied successfully.', 'success');
                    if (input) input.value = '';
                } catch (error) {
                    if (error.payload?.cart) refreshTotals(error.payload.cart);
                    setFeedback(error.message || 'Unable to validate this promo code.', 'error');
                } finally {
                    setBusy(false);
                }
            });

            removeForm?.addEventListener('submit', async (event) => {
                event.preventDefault();
                setFeedback('', 'success');

                try {
                    const payload = await request(removeForm.action, {
                        method: 'DELETE',
                    });
                    refreshTotals(payload.cart);
                    setFeedback(payload.message || 'Promo code removed.', 'success');
                } catch (error) {
                    setFeedback(error.message || 'Unable to remove the promo code.', 'error');
                }
            });
        });
    });
</script>
@endif
