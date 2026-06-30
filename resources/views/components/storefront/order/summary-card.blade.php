@props(['summary'])

<aside class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-card lg:sticky lg:top-32" aria-label="Order summary">
    <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <h2 class="text-xl font-black text-brand-ink">Order Summary</h2>
            <p class="mt-1 text-sm font-semibold text-slate-500">{{ $summary['order_number'] }}</p>
        </div>
        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ count($summary['items']) }} items</span>
    </div>

    <div class="mt-4 grid gap-3">
        @foreach ($summary['items'] as $item)
            <x-storefront.order.mini-item :item="$item" />
        @endforeach
    </div>

    <div class="mt-5 grid gap-2 border-t border-slate-200 pt-4 text-sm font-bold text-slate-600">
        <div class="flex justify-between gap-3"><span>Subtotal</span><strong class="text-brand-ink">${{ number_format($summary['totals']['subtotal'], 2) }}</strong></div>
        @if (($summary['totals']['customization_total'] ?? 0) > 0)
            <div class="flex justify-between gap-3"><span>Customization</span><strong class="text-brand-ink">${{ number_format($summary['totals']['customization_total'], 2) }}</strong></div>
        @endif
        <div class="flex justify-between gap-3"><span>Discount @if(!empty($summary['totals']['coupon_code']))<span class="text-xs font-black text-green-700">({{ $summary['totals']['coupon_code'] }})</span>@endif</span><strong class="text-brand-red">-${{ number_format($summary['totals']['discount'], 2) }}</strong></div>
        <div class="flex justify-between gap-3"><span>Shipping</span><strong class="text-brand-ink">${{ number_format($summary['totals']['shipping'], 2) }}</strong></div>
        <div class="flex justify-between gap-3"><span>Estimated tax</span><strong class="text-brand-ink">${{ number_format($summary['totals']['tax'], 2) }}</strong></div>
        <div class="mt-2 flex justify-between gap-3 border-t border-slate-200 pt-4 text-lg text-brand-ink">
            <span>Total</span><strong>${{ number_format($summary['totals']['total'], 2) }}</strong>
        </div>
    </div>

    <div class="mt-5 rounded-2xl bg-slate-50 p-4 text-xs font-bold leading-5 text-slate-600">
        🔒 Order details are protected. Payment status must be confirmed by secure provider webhook or admin verification.
    </div>
</aside>
