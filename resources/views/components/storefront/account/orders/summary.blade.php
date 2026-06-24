@props(['order'])
<aside class="rounded-[26px] border border-slate-200 bg-white p-5 shadow-card lg:sticky lg:top-28">
    <h3 class="text-lg font-black text-brand-ink">Order Summary</h3>
    <div class="mt-5 space-y-3 text-sm">
        <div class="flex justify-between gap-4"><span class="text-slate-500">Subtotal</span><strong>${{ number_format((float)$order->subtotal,2) }}</strong></div>
        <div class="flex justify-between gap-4"><span class="text-slate-500">Customization</span><strong>${{ number_format((float)$order->customization_total,2) }}</strong></div>
        <div class="flex justify-between gap-4"><span class="text-slate-500">Discount</span><strong>−${{ number_format((float)$order->discount_total,2) }}</strong></div>
        <div class="flex justify-between gap-4"><span class="text-slate-500">Shipping</span><strong>${{ number_format((float)$order->shipping_total,2) }}</strong></div>
        <div class="flex justify-between gap-4"><span class="text-slate-500">Tax</span><strong>${{ number_format((float)$order->tax_total,2) }}</strong></div>
        <div class="flex justify-between gap-4 border-t border-slate-200 pt-4 text-lg"><span class="font-black">Total</span><strong>${{ number_format((float)$order->grand_total,2) }}</strong></div>
    </div>
    <div class="mt-5 grid gap-3 rounded-2xl bg-slate-50 p-4 text-sm">
        <div><span class="block text-xs font-black uppercase text-slate-400">Order status</span><span class="font-black">{{ $order->statusLabel() }}</span></div>
        <div><span class="block text-xs font-black uppercase text-slate-400">Payment status</span><span class="font-black">{{ $order->paymentStatusLabel() }}</span></div>
    </div>
</aside>
