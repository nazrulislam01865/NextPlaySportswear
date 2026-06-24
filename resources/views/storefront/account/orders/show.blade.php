<x-storefront.account.orders.page :seo="$seo" :account="$account" :navigation="$navigation" :title="'Order '.$order->order_number" subtitle="Authenticated order details, status history, items, payment, shipment, documents, and available post-purchase actions." eyebrow="Authenticated order details">
    <x-slot:actions><a class="btn btn-white" href="{{ route('account.orders.index') }}">Back to Orders</a></x-slot:actions>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_330px] lg:items-start">
        <div class="space-y-6">
            <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-card md:p-7">
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start"><div><p class="text-xs font-black uppercase tracking-wide text-slate-400">Placed {{ $order->placed_at?->format('M d, Y · g:i A') }}</p><h3 class="mt-2 text-2xl font-black">{{ $order->statusLabel() }}</h3><p class="mt-1 text-sm text-slate-500">Payment: {{ $order->paymentStatusLabel() }} · Fulfillment: {{ $order->fulfillmentStatusLabel() }}</p></div><x-storefront.account.orders.status-pill :status="$order->status" /></div>
                <div class="mt-5 flex flex-wrap gap-2">
                    @if($order->canPay())<a class="btn btn-red" href="{{ route('account.orders.pay',$order) }}">Pay for Order</a>@endif
                    @if($order->payment_status==='failed')<a class="btn btn-red" href="{{ route('account.orders.payment.retry',$order) }}">Retry Payment</a>@endif
                    <a class="btn btn-white" href="{{ route('account.orders.reorder',$order) }}">Order Again</a>
                    @if($order->canRequestChange())<a class="btn btn-light" href="{{ route('account.orders.change',$order) }}">Request Change</a>@endif
                    @if($order->canRequestCancellation())<a class="btn btn-light" href="{{ route('account.orders.cancel',$order) }}">Request Cancellation</a>@endif
                    @if($order->canRequestReturn())<a class="btn btn-light" href="{{ route('account.orders.returns.create',$order) }}">Start Return</a>@endif
                    @if($order->canRequestExchange())<a class="btn btn-light" href="{{ route('account.orders.exchanges.create',$order) }}">Start Exchange</a>@endif
                </div>
            </section>

            <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-card md:p-7"><h3 class="text-xl font-black">Order Items</h3><div class="mt-5 grid gap-4">@foreach($order->items as $item)<x-storefront.account.orders.item-row :item="$item" />@endforeach</div></section>

            <section class="grid gap-5 md:grid-cols-2">
                <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-card"><h3 class="font-black">Shipping Address</h3>@php($address=(array)data_get($order->shipping_address,'address',$order->shipping_address ?? []))<div class="mt-3 text-sm leading-6 text-slate-600"><p class="font-black text-brand-ink">{{ trim(($address['first_name']??'').' '.($address['last_name']??'')) }}</p>@foreach(['company_name','address_line_1','address_line_2'] as $field)@if(!empty($address[$field]))<p>{{ $address[$field] }}</p>@endif @endforeach<p>{{ $address['city']??'' }}{{ !empty($address['state']) ? ', '.$address['state'] : '' }} {{ $address['postal_code']??'' }}</p><p>{{ $address['country']??'' }}</p></div></div>
                <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-card"><h3 class="font-black">Payment & Delivery</h3><div class="mt-3 grid gap-3 text-sm text-slate-600"><p><b class="text-brand-ink">Payment:</b> {{ data_get($order->payment_method,'label','Not selected') }}</p><p><b class="text-brand-ink">Shipping:</b> {{ data_get($order->shipping_method,'title','To be confirmed') }}</p><p><b class="text-brand-ink">Estimate:</b> {{ data_get($order->shipping_method,'eta','Updated after production') }}</p></div></div>
            </section>

            <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-card md:p-7"><div class="flex items-center justify-between"><div><h3 class="text-xl font-black">Status Timeline</h3><p class="text-sm text-slate-500">Order activity recorded by the customer and administration team.</p></div></div><div class="mt-6"><x-storefront.account.orders.timeline :histories="$order->histories" /></div></section>

            @if($order->shipments->isNotEmpty())<section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-card"><div class="flex items-center justify-between"><h3 class="text-xl font-black">Shipments</h3><a class="text-sm font-black text-brand-red" href="{{ route('account.orders.shipments',$order) }}">View split shipment plan →</a></div><div class="mt-4 grid gap-3">@foreach($order->shipments as $shipment)<a href="{{ route('account.orders.shipments.show',[$order,$shipment]) }}" class="flex flex-col justify-between gap-3 rounded-2xl border border-slate-200 p-4 sm:flex-row sm:items-center"><div><p class="font-black">{{ $shipment->shipment_number }}</p><p class="text-sm text-slate-500">{{ $shipment->carrier ?: 'Carrier pending' }} · {{ $shipment->tracking_number ?: 'Tracking pending' }}</p></div><x-storefront.account.orders.status-pill :status="$shipment->status" /></a>@endforeach</div></section>@endif

            <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-card"><h3 class="text-xl font-black">Documents & Downloads</h3><div class="mt-4 flex flex-wrap gap-2"><a class="btn btn-white" href="{{ route('account.orders.invoice',$order) }}">Secure Invoice</a>@foreach($order->creditNotes as $note)<a class="btn btn-white" href="{{ route('account.credit-notes.show',$note) }}">Credit Note {{ $note->credit_note_number }}</a>@endforeach @if($order->downloads->isNotEmpty())<a class="btn btn-white" href="{{ route('account.downloads.index') }}">Order Downloads</a>@endif</div></section>
        </div>
        <x-storefront.account.orders.summary :order="$order" />
    </div>
</x-storefront.account.orders.page>
