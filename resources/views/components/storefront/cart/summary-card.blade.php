@props(['cart'])

<aside class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-card lg:sticky lg:top-28">
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Order summary</p>
            <h2 class="mt-1 font-display text-3xl font-bold uppercase leading-none text-brand-ink">Cart Total</h2>
        </div>
        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ $cart['quantity'] }} item{{ $cart['quantity'] === 1 ? '' : 's' }}</span>
    </div>

    <dl class="mt-5 divide-y divide-slate-100 text-sm">
        <div class="flex items-center justify-between gap-4 py-2">
            <dt class="font-semibold text-slate-500">Merchandise subtotal</dt>
            <dd class="font-black text-slate-900">${{ number_format($cart['subtotal'], 2) }}</dd>
        </div>
        <div class="flex items-center justify-between gap-4 py-2">
            <dt class="font-semibold text-slate-500">Customization charges</dt>
            <dd class="font-black text-slate-900">${{ number_format($cart['customization_total'], 2) }}</dd>
        </div>
        <div class="flex items-center justify-between gap-4 py-2">
            <dt class="font-semibold text-slate-500">Discount</dt>
            <dd class="font-black text-green-700">-${{ number_format($cart['discount'], 2) }}</dd>
        </div>
        <div class="flex items-center justify-between gap-4 py-2">
            <dt class="font-semibold text-slate-500">Estimated shipping</dt>
            <dd class="font-black text-slate-900">${{ number_format($cart['shipping'], 2) }}</dd>
        </div>
        <div class="flex items-center justify-between gap-4 py-2">
            <dt class="font-semibold text-slate-500">Estimated tax</dt>
            <dd class="font-black text-slate-900">${{ number_format($cart['tax'], 2) }}</dd>
        </div>
    </dl>

    @if ($cart['coupon_code'])
        <div class="mt-4 flex items-center justify-between gap-3 rounded-2xl border border-green-200 bg-green-50 p-3 text-sm">
            <span class="font-black text-green-800">Coupon: {{ $cart['coupon_code'] }}</span>
            @if (! $cart['is_preview'])
                <form method="POST" action="{{ route('cart.coupon.destroy') }}">
                    @csrf
                    @method('DELETE')
                    <button class="text-xs font-black text-green-800 underline" type="submit">Remove</button>
                </form>
            @endif
        </div>
    @endif

    @if (! $cart['is_preview'])
        <form method="POST" action="{{ route('cart.coupon.apply') }}" class="mt-4">
            @csrf
            <label for="coupon_code" class="text-sm font-black text-brand-ink">Promo code</label>
            <div class="mt-2 flex gap-2">
                <input
                    id="coupon_code"
                    name="coupon_code"
                    type="text"
                    placeholder="TEAM10"
                    class="h-11 min-w-0 flex-1 rounded-xl border border-slate-300 bg-slate-50 px-3 text-sm font-bold uppercase text-slate-800 outline-none focus:border-brand-blue"
                >
                <button class="btn btn-light min-h-11 px-4 py-2" type="submit">Apply</button>
            </div>
        </form>
    @endif

    <div class="mt-5 rounded-2xl bg-brand-navy p-4 text-white">
        <div class="flex items-end justify-between gap-4">
            <span class="text-sm font-black uppercase tracking-[.16em] text-blue-100">Grand total</span>
            <span class="font-display text-4xl font-bold leading-none">${{ number_format($cart['total'], 2) }}</span>
        </div>
        <p class="mt-2 text-xs font-semibold leading-5 text-blue-100">Final totals are recalculated before payment.</p>
    </div>

    <div class="mt-5 grid gap-3">
        <a href="{{ route('checkout.index') }}" class="btn btn-red w-full {{ $cart['checkout_ready'] ? '' : 'pointer-events-none opacity-50' }}">Proceed to Checkout</a>
        <a href="{{ route('products.index') }}" class="btn btn-white w-full">Continue Shopping</a>
        <a href="{{ route('quote.request') }}" class="btn btn-light w-full">Request Bulk Quote</a>
    </div>

    <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
        @foreach ($cart['payment_badges'] as $badge)
            <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[10px] font-black uppercase tracking-wide text-slate-500">{{ $badge }}</span>
        @endforeach
    </div>
</aside>
