@props(['cart'])

@php
    $message = session('coupon_error') ?: ($cart['coupon_error'] ?? null);
@endphp

<aside class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-card lg:sticky lg:top-28" data-cart-summary-root>
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Order summary</p>
            <h2 class="mt-1 font-display text-3xl font-bold uppercase leading-none text-brand-ink">Cart Total</h2>
        </div>
        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600" data-cart-quantity>{{ $cart['quantity'] }} item{{ $cart['quantity'] === 1 ? '' : 's' }}</span>
    </div>

    <dl class="mt-5 divide-y divide-slate-100 text-sm">
        <div class="flex items-center justify-between gap-4 py-2">
            <dt class="font-semibold text-slate-500">Merchandise subtotal</dt>
            <dd class="font-black text-slate-900" data-cart-money="subtotal">${{ number_format($cart['subtotal'], 2) }}</dd>
        </div>
        <div class="flex items-center justify-between gap-4 py-2">
            <dt class="font-semibold text-slate-500">Customization charges</dt>
            <dd class="font-black text-slate-900" data-cart-money="customization_total">${{ number_format($cart['customization_total'], 2) }}</dd>
        </div>
        <div class="flex items-center justify-between gap-4 py-2">
            <dt class="font-semibold text-slate-500">Discount</dt>
            <dd class="font-black text-green-700" data-cart-money="discount" data-cart-money-prefix="-">-${{ number_format($cart['discount'], 2) }}</dd>
        </div>
        <div class="flex items-center justify-between gap-4 py-2">
            <dt class="font-semibold text-slate-500">Estimated shipping</dt>
            <dd class="font-black text-slate-900" data-cart-money="shipping">${{ number_format($cart['shipping'], 2) }}</dd>
        </div>
        <div class="flex items-center justify-between gap-4 py-2">
            <dt class="font-semibold text-slate-500">Estimated tax</dt>
            <dd class="font-black text-slate-900" data-cart-money="tax">${{ number_format($cart['tax'], 2) }}</dd>
        </div>
    </dl>

    <div
        class="mt-4 {{ $cart['coupon_code'] ? '' : 'hidden' }} flex items-center justify-between gap-3 rounded-2xl border border-green-200 bg-green-50 p-3 text-sm"
        data-coupon-pill
    >
        <span class="font-black text-green-800">Coupon: <span data-coupon-code>{{ $cart['coupon_code'] }}</span></span>
        @if (! $cart['is_preview'])
            <form method="POST" action="{{ route('cart.coupon.destroy') }}" data-coupon-remove-form>
                @csrf
                @method('DELETE')
                <button class="text-xs font-black text-green-800 underline" type="submit">Remove</button>
            </form>
        @endif
    </div>

    <div
        class="mt-3 rounded-2xl border px-4 py-3 text-sm font-bold {{ $message ? '' : 'hidden' }} {{ session('coupon_error') || ($cart['coupon_error'] ?? null) ? 'border-red-200 bg-red-50 text-red-800' : 'border-green-200 bg-green-50 text-green-800' }}"
        data-coupon-feedback
    >
        {{ $message }}
    </div>

    @if (! $cart['is_preview'])
        <form method="POST" action="{{ route('cart.coupon.apply') }}" class="mt-4" data-coupon-form>
            @csrf
            <label for="coupon_code" class="text-sm font-black text-brand-ink">Promo code</label>
            <div class="mt-2 flex gap-2">
                <input
                    id="coupon_code"
                    name="coupon_code"
                    type="text"
                    placeholder="TEAM10"
                    value=""
                    class="h-11 min-w-0 flex-1 rounded-xl border border-slate-300 bg-slate-50 px-3 text-sm font-bold uppercase text-slate-800 outline-none focus:border-brand-blue"
                    data-coupon-input
                    autocomplete="off"
                    maxlength="60"
                >
                <button class="btn btn-light min-h-11 px-4 py-2" type="submit" data-coupon-apply-button>Apply</button>
            </div>
            <p class="mt-2 text-xs font-semibold leading-5 text-slate-500">Promo codes are checked instantly and validated again before order placement.</p>
        </form>
    @endif

    <div class="mt-5 rounded-2xl bg-brand-navy p-4 text-white">
        <div class="flex items-end justify-between gap-4">
            <span class="text-sm font-black uppercase tracking-[.16em] text-blue-100">Grand total</span>
            <span class="font-display text-4xl font-bold leading-none" data-cart-money="total">${{ number_format($cart['total'], 2) }}</span>
        </div>
        <p class="mt-2 text-xs font-semibold leading-5 text-blue-100">Final totals are recalculated before payment.</p>
    </div>

    <div class="mt-5 grid gap-3">
        <a href="{{ route('checkout.index') }}" class="btn btn-red w-full {{ $cart['checkout_ready'] ? '' : 'pointer-events-none opacity-50' }}" data-checkout-link>
            @auth
                Proceed to Checkout
            @else
                Sign In to Checkout
            @endauth
        </a>
        @guest
            <p class="rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2 text-center text-xs font-bold leading-5 text-amber-800">
                Customer login is required before checkout for secure order ownership and saved cart protection.
            </p>
        @endguest
        <a href="{{ route('products.index') }}" class="btn btn-white w-full">Continue Shopping</a>
        <a href="{{ route('quote.request') }}" class="btn btn-light w-full">Request Bulk Quote</a>
    </div>

    <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
        @foreach ($cart['payment_badges'] as $badge)
            <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[10px] font-black uppercase tracking-wide text-slate-500">{{ $badge }}</span>
        @endforeach
    </div>
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
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const money = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });

            const setFeedback = (message, type = 'success') => {
                if (!feedback) return;
                feedback.textContent = message || '';
                feedback.classList.toggle('hidden', !message);
                feedback.classList.toggle('border-green-200', type === 'success');
                feedback.classList.toggle('bg-green-50', type === 'success');
                feedback.classList.toggle('text-green-800', type === 'success');
                feedback.classList.toggle('border-red-200', type !== 'success');
                feedback.classList.toggle('bg-red-50', type !== 'success');
                feedback.classList.toggle('text-red-800', type !== 'success');
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
                if (quantityNode) {
                    const quantity = Number(cart.quantity || 0);
                    quantityNode.textContent = `${quantity} item${quantity === 1 ? '' : 's'}`;
                }

                if (pill) pill.classList.toggle('hidden', !cart.coupon_code);
                if (couponCode) couponCode.textContent = cart.coupon_code || '';
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
