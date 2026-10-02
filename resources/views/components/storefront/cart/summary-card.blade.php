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
        <div data-cart-discount-row class="{{ (float) ($cart['discount'] ?? 0) > 0 ? '' : 'hidden' }}">
            <dt>Discounts</dt>
            <dd class="font-black text-emerald-700">−<span data-cart-money="discount">${{ number_format((float) $cart['discount'], 2) }}</span></dd>
        </div>
        <div>
            <dt>Shipping</dt>
            @if ((float) ($cart['product_shipping_total'] ?? 0) > 0)
                <dd class="np-cart-summary-shipping">
                    <span class="block text-sm font-bold text-brand-ink" data-cart-money="product_shipping_total">${{ number_format((float) $cart['product_shipping_total'], 2) }}</span>
                    <span class="mt-0.5 block text-[10px] font-medium text-slate-500">Selected with products</span>
                </dd>
            @else
                <dd class="np-cart-summary-shipping">Calculated at checkout</dd>
            @endif
        </div>
    </dl>

    <div class="np-cart-summary-total">
        <span>Estimated subtotal</span>
        <strong data-cart-money="estimated_subtotal">${{ number_format((float) ($cart['estimated_subtotal'] ?? max(0, $cart['merchandise_total'] + ($cart['product_shipping_total'] ?? 0) - $cart['discount'])), 2) }}</strong>
    </div>

    @if(auth('web')->check() && (((float) data_get($cart, 'reward.available', 0)) > 0 || (bool) data_get($cart, 'reward.applied', false)))
        <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.14em] text-emerald-700">My Rewards</p>
                    <p class="mt-1 text-sm font-bold leading-5 text-emerald-900">£{{ number_format((float) data_get($cart, 'reward.available', 0), 2) }} available</p>
                    @if((bool) data_get($cart, 'reward.applied', false))
                        <p class="mt-1 text-xs font-semibold text-emerald-700">£{{ number_format((float) data_get($cart, 'reward.amount', 0), 2) }} will be used on this order.</p>
                    @else
                        <p class="mt-1 text-xs font-semibold text-emerald-700">Apply your available balance to this upcoming order.</p>
                    @endif
                </div>
                @if(! $cart['is_preview'])
                    @if((bool) data_get($cart, 'reward.applied', false))
                        <form method="POST" action="{{ route('cart.rewards.destroy') }}">@csrf @method('DELETE')<button type="submit" class="text-xs font-black text-emerald-800 underline">Remove</button></form>
                    @else
                        <form method="POST" action="{{ route('cart.rewards.apply') }}">@csrf<button type="submit" class="btn btn-light btn-sm">Use rewards</button></form>
                    @endif
                @endif
            </div>
        </div>
    @endif

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

                const discountRow = root.querySelector('[data-cart-discount-row]');
                if (discountRow) discountRow.classList.toggle('hidden', Number(cart.discount || 0) <= 0);

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
