@props(['order'])
<aside class="rounded-[26px] border border-slate-200 bg-white p-5 shadow-card lg:sticky lg:top-28">
    <h3 class="text-lg font-black text-brand-ink">Order Summary</h3>
    @if($order->awaitingQuotePrice())
        <p class="mt-4 text-sm leading-6 text-slate-600">This bulk quotation order is awaiting final invoice pricing from sales. No payment is due yet.</p>
        <div class="mt-4 flex justify-between gap-4 border-t border-slate-200 pt-4 text-sm"><span class="font-black">Order total</span><strong>Pending invoice</strong></div>
    @else
    <div class="mt-5 space-y-3 text-sm">
        <div class="flex justify-between gap-4"><span class="text-slate-500">Subtotal</span><strong>${{ number_format((float)$order->subtotal,2) }}</strong></div>
        <div class="flex justify-between gap-4"><span class="text-slate-500">Customization</span><strong>${{ number_format($order->displayCustomizationTotal(),2) }}</strong></div>
        <div class="flex justify-between gap-4"><span class="text-slate-500">Discount @if($order->coupon_code)<span class="text-xs font-black text-green-700">({{ $order->coupon_code }})</span>@endif</span><strong>−${{ number_format((float)$order->discount_total,2) }}</strong></div>
        <div class="flex justify-between gap-4"><span class="text-slate-500">Shipping charges</span><strong>${{ number_format($order->displayShippingTotal(),2) }}</strong></div>
        <div class="flex justify-between gap-4"><span class="text-slate-500">Tax</span><strong>${{ number_format((float)$order->tax_total,2) }}</strong></div>
        <div class="flex justify-between gap-4 border-t border-slate-200 pt-4 text-lg"><span class="font-black">Total</span><strong>${{ number_format((float)$order->grand_total,2) }}</strong></div>
    </div>
    @endif
    <div class="mt-5 grid gap-3 rounded-2xl bg-slate-50 p-4 text-sm">
        <div><span class="block text-xs font-black uppercase text-slate-400">Order status</span><span class="font-black">{{ $order->statusLabel() }}</span></div>
        <div><span class="block text-xs font-black uppercase text-slate-400">Payment status</span><span class="font-black">{{ $order->paymentStatusLabel() }}</span></div>
    </div>
</aside>
