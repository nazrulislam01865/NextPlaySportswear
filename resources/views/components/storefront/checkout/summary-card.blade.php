@props(['summary' => []])

@php
    $items = $summary['items'] ?? [];
@endphp

<aside class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-card lg:sticky lg:top-24 lg:p-6" aria-label="Order summary">
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
        <div class="flex justify-between gap-4"><dt class="font-semibold text-slate-500">Discount</dt><dd class="font-black text-green-700">-${{ number_format($summary['discount'] ?? 0, 2) }}</dd></div>
        <div class="flex justify-between gap-4"><dt class="font-semibold text-slate-500">Shipping</dt><dd class="font-black text-slate-900">${{ number_format($summary['shipping'] ?? 0, 2) }}</dd></div>
        <div class="flex justify-between gap-4"><dt class="font-semibold text-slate-500">Estimated tax</dt><dd class="font-black text-slate-900">${{ number_format($summary['tax'] ?? 0, 2) }}</dd></div>
    </dl>

    <div class="mt-6 rounded-2xl bg-brand-navy p-4 text-white">
        <div class="flex items-end justify-between gap-4">
            <span class="text-sm font-black uppercase tracking-[.16em] text-blue-100">Total</span>
            <span class="font-display text-4xl font-bold">${{ number_format($summary['total'] ?? 0, 2) }}</span>
        </div>
        <p class="mt-2 text-xs font-semibold leading-5 text-blue-100">Final price is recalculated by the Laravel backend before payment.</p>
    </div>

    <div class="mt-5 rounded-2xl border border-blue-100 bg-blue-50 p-4 text-xs font-bold leading-5 text-brand-navy">
        🔒 Secure checkout · Raw card numbers and CVV are never stored in the application database.
    </div>
</aside>
