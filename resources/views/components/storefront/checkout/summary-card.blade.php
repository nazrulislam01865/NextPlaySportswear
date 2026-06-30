@props(['summary' => []])

@php
    $items = $summary['items'] ?? [];
    $initialShippingTitle = (string) data_get($summary, 'shipping_method.title', '');
    $initialShippingBase = '$'.number_format((float) ($summary['shipping_base'] ?? $summary['shipping'] ?? 0), 2);
    $initialRuralAmount = (float) ($summary['rural_surcharge'] ?? 0);
    $initialRural = '$'.number_format($initialRuralAmount, 2);
    $initialRuralPostalCode = (string) data_get($summary, 'rural_surcharge_details.postal_code', '');
    $initialTotal = '$'.number_format((float) ($summary['total'] ?? 0), 2);
    $initialEta = (string) data_get($summary, 'shipping_method.eta', '');
@endphp

<aside
    class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-card lg:sticky lg:top-24 lg:p-6"
    aria-label="Order summary"
    x-data="{
        shippingTitle: @js($initialShippingTitle),
        shippingBase: @js($initialShippingBase),
        ruralAmount: @js(round($initialRuralAmount, 2)),
        rural: @js($initialRural),
        ruralPostalCode: @js($initialRuralPostalCode),
        total: @js($initialTotal),
        eta: @js($initialEta),
        syncShippingPreview(detail) {
            if (!detail || Object.keys(detail).length === 0) return;
            this.shippingTitle = detail.shipping_title || this.shippingTitle || 'Shipping';
            this.shippingBase = detail.shipping_base || detail.shipping || this.shippingBase;
            this.ruralAmount = Number(detail.rural_amount || 0);
            this.rural = detail.rural || '$0.00';
            this.ruralPostalCode = detail.rural_postal_code || '';
            this.total = detail.total || this.total;
            this.eta = detail.eta || this.eta;
        }
    }"
    x-on:checkout-shipping-preview.window="syncShippingPreview($event.detail)"
>
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Order summary</p>
            <h2 class="mt-1 font-display text-3xl font-bold uppercase text-brand-ink">Secure Total</h2>
        </div>
        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ $summary['quantity'] ?? 0 }} item{{ ($summary['quantity'] ?? 0) === 1 ? '' : 's' }}</span>
    </div>

    <div class="mt-6 grid gap-4">
        @forelse ($items as $item)
            <div class="grid grid-cols-[68px_1fr] gap-3 rounded-2xl border border-slate-100 bg-slate-50 p-3">
                <img src="{{ $item['product']['image'] ?? '' }}" alt="{{ $item['product']['alt'] ?? $item['product']['title'] ?? 'Product' }}" class="h-16 w-16 rounded-xl object-cover">
                <div class="min-w-0">
                    <h3 class="line-clamp-2 text-sm font-black text-brand-ink">{{ $item['product']['short_title'] ?? $item['product']['title'] ?? 'Product' }}</h3>
                    <p class="mt-1 text-xs font-semibold text-slate-500">Qty {{ $item['quantity'] ?? 1 }} · {{ $item['customization']['design_option'] ?? 'Custom design' }}</p>
                    <p class="mt-1 text-sm font-black text-slate-900">${{ number_format($item['line_total'] ?? 0, 2) }}</p>
                </div>
            </div>
        @empty
            <div class="rounded-2xl bg-slate-50 p-4 text-sm font-bold text-slate-500">No cart items available.</div>
        @endforelse
    </div>

    <dl class="mt-6 grid gap-3 text-sm">
        <div class="flex justify-between gap-4"><dt class="font-semibold text-slate-500">Merchandise subtotal</dt><dd class="font-black text-slate-900">${{ number_format($summary['subtotal'] ?? 0, 2) }}</dd></div>
        <div class="flex justify-between gap-4"><dt class="font-semibold text-slate-500">Customization</dt><dd class="font-black text-slate-900">${{ number_format($summary['customization_total'] ?? 0, 2) }}</dd></div>
        @if(($summary['product_shipping_total'] ?? 0) > 0)
            <div class="flex justify-between gap-4"><dt class="font-semibold text-slate-500">Product shipping <span class="text-xs font-black text-slate-400">included</span></dt><dd class="font-black text-slate-900">${{ number_format($summary['product_shipping_total'] ?? 0, 2) }}</dd></div>
        @endif
        <div class="flex justify-between gap-4"><dt class="font-semibold text-slate-500">Discount @if(!empty($summary['coupon_code']))<span class="text-xs font-black text-green-700">({{ $summary['coupon_code'] }})</span>@endif</dt><dd class="font-black text-green-700">-${{ number_format($summary['discount'] ?? 0, 2) }}</dd></div>
        <div class="flex justify-between gap-4">
            <dt class="font-semibold text-slate-500">
                Shipping
                <span class="text-xs font-black text-slate-400" x-show="shippingTitle" x-cloak>(<span x-text="shippingTitle"></span>)</span>
            </dt>
            <dd class="font-black text-slate-900" x-text="shippingBase">{{ $initialShippingBase }}</dd>
        </div>
        <div class="flex justify-between gap-4" x-show="ruralAmount > 0" x-cloak>
            <dt class="font-semibold text-amber-700">
                Rural area surcharge
                <span class="text-xs font-black" x-show="ruralPostalCode">(<span x-text="ruralPostalCode"></span>)</span>
            </dt>
            <dd class="font-black text-amber-700" x-text="rural">{{ $initialRural }}</dd>
        </div>
        <div class="flex justify-between gap-4"><dt class="font-semibold text-slate-500">Estimated tax</dt><dd class="font-black text-slate-900">${{ number_format($summary['tax'] ?? 0, 2) }}</dd></div>
    </dl>

    <div class="mt-6 rounded-2xl bg-brand-navy p-4 text-white">
        <div class="flex items-end justify-between gap-4">
            <span class="text-sm font-black uppercase tracking-[.16em] text-blue-100">Total</span>
            <span class="font-display text-4xl font-bold" x-text="total">{{ $initialTotal }}</span>
        </div>
        <p class="mt-2 text-xs font-semibold leading-5 text-blue-100">Final price is recalculated by the Laravel backend before payment.</p>
    </div>

    <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-xs font-bold leading-5 text-slate-600" x-show="eta" x-cloak>
        Delivery estimate: <span class="text-brand-ink" x-text="eta">{{ $initialEta }}</span>
    </div>

    <div class="mt-5 rounded-2xl border border-blue-100 bg-blue-50 p-4 text-xs font-bold leading-5 text-brand-navy">
        🔒 Secure checkout · Raw card numbers and CVV are never stored in the application database.
    </div>
</aside>
