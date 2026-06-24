@props(['order'])
<article class="overflow-hidden rounded-[26px] border border-slate-200 bg-white shadow-card">
    <div class="flex flex-col justify-between gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-start">
        <div>
            <a href="{{ route('account.orders.show', $order) }}" class="text-lg font-black text-brand-blue hover:text-brand-red">Order {{ $order->order_number }}</a>
            <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs font-bold text-slate-500">
                <span>Placed {{ $order->placed_at?->format('M d, Y') }}</span>
                <span>{{ $order->total_quantity }} item{{ $order->total_quantity === 1 ? '' : 's' }}</span>
                <span>{{ $order->paymentStatusLabel() }}</span>
            </div>
        </div>
        <x-storefront.account.orders.status-pill :status="$order->status" />
    </div>
    <div class="grid gap-5 p-5 md:grid-cols-[1fr_auto] md:items-center">
        <div class="flex -space-x-3">
            @foreach($order->items->take(4) as $item)
                <img src="{{ \App\Support\PublicMedia::url(null, $item->image_url, '/images/product-placeholder.svg') }}" alt="{{ $item->product_name }}" class="h-14 w-14 rounded-xl border-2 border-white object-cover" loading="lazy">
            @endforeach
        </div>
        <div class="text-left md:text-right">
            <p class="text-xs font-black uppercase tracking-wide text-slate-400">Order total</p>
            <p class="mt-1 text-2xl font-black text-brand-ink">${{ number_format((float)$order->grand_total, 2) }}</p>
        </div>
    </div>
    <div class="grid gap-2 border-t border-slate-100 bg-slate-50 p-4 sm:flex sm:flex-wrap [&_.btn]:w-full sm:[&_.btn]:w-auto">
        @if($order->canPay())<a class="btn btn-red" href="{{ route('account.orders.pay', $order) }}">Pay now</a>@endif
        <a class="btn btn-white" href="{{ route('account.orders.show', $order) }}">View details</a>
        @if($order->shipments_count ?? $order->shipments?->count())<a class="btn btn-light" href="{{ route('account.orders.shipments', $order) }}">Shipments</a>@endif
        @if(in_array($order->status, ['delivered','completed'], true))<a class="btn btn-light" href="{{ route('account.orders.reorder', $order) }}">Order again</a>@endif
    </div>
</article>
