<x-storefront.account.orders.page :seo="$seo" :account="$account" :navigation="$navigation" :title="'Order '.$order->order_number" subtitle="Authenticated order details, status history, items, payment, shipment, documents, and available post-purchase actions." eyebrow="Authenticated order details">
    <x-slot:actions><a class="btn btn-outline" href="{{ route('account.orders.index') }}">Back to Orders</a></x-slot:actions>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_330px] lg:items-start">
        <div class="space-y-6">
            <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-card md:p-7">
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start"><div><p class="text-xs font-black uppercase tracking-wide text-slate-400">Placed {{ \App\Support\ViewerTime::render($order->placed_at, 'M d, Y · g:i A') }}</p>@php($showOperationalStatus = $order->flowtrack_operational_status && ! in_array($order->status, ['cancelled', 'refunded', 'delivered', 'completed'], true))
                    <h3 class="mt-2 text-2xl font-black">{{ $showOperationalStatus ? (($order->flowtrack_operational_stage ?: 'Production').' · '.str($order->flowtrack_operational_status)->headline()) : $order->statusLabel() }}</h3>
                    @if($showOperationalStatus)
                        <p class="mt-2 text-xs text-slate-500">Storefront order status: {{ $order->statusLabel() }}</p>
                    @endif<p class="mt-1 text-sm text-slate-500">Payment: {{ $order->paymentStatusLabel() }} · Fulfillment: {{ $order->fulfillmentStatusLabel() }}</p></div><x-storefront.account.orders.status-pill :status="$order->status" /></div>
                @if($order->awaitingQuotePrice())
                    <p class="mt-4 text-sm font-semibold text-slate-600">This order was created from an attached quotation. Sales will confirm the final invoice amount before payment is requested.</p>
                @endif
                <div class="mt-5 flex flex-wrap gap-2">
                    @if($order->canPay())<a class="btn btn-primary" href="{{ route('account.orders.pay',$order) }}">Pay for Order</a>@endif
                    @if($order->payment_status==='failed')<a class="btn btn-primary" href="{{ route('account.orders.payment.retry',$order) }}">Retry Payment</a>@endif
                    <a class="btn btn-outline" href="{{ route('account.orders.reorder',$order) }}">Order Again</a>
                    @if($order->canRequestChange())<a class="btn btn-light" href="{{ route('account.orders.change',$order) }}">Request Change</a>@endif
                    @if($order->canRequestCancellation())<a class="btn btn-light" href="{{ route('account.orders.cancel',$order) }}">Request Cancellation</a>@endif
                    @if($order->canRequestReturn())<a class="btn btn-light" href="{{ route('account.orders.returns.create',$order) }}">Start Return</a>@endif
                    @if($order->canRequestExchange())<a class="btn btn-light" href="{{ route('account.orders.exchanges.create',$order) }}">Start Exchange</a>@endif
                </div>
            </section>

            @if($order->flowtrack_operational_status)
                <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-card md:p-7" aria-label="Production progress">
                    <h3 class="text-lg font-black text-brand-ink">Production progress</h3>
                    <p class="mt-2 text-sm text-slate-700">{{ $order->flowtrack_operational_stage ?: str($order->flowtrack_operational_status)->headline() }}</p>
                    @if($order->flowtrack_operational_progress !== null)
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-valuenow="{{ $order->flowtrack_operational_progress }}" aria-valuemin="0" aria-valuemax="100"><div class="h-full bg-brand-red" style="width: {{ $order->flowtrack_operational_progress }}%"></div></div>
                    @endif
                    <p class="mt-2 text-xs text-slate-500">Updated from production workflow.</p>
                </section>
            @endif

            <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-card md:p-7"><h3 class="text-xl font-black">Order Items</h3><div class="mt-5 grid gap-4">@foreach($order->items as $item)<x-storefront.account.orders.item-row :item="$item" />@endforeach</div></section>

            <section class="grid gap-5 md:grid-cols-2">
                <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-card"><h3 class="font-black">Shipping Address</h3>@php($address=(array)data_get($order->shipping_address,'address',$order->shipping_address ?? []))<div class="mt-3 text-sm leading-6 text-slate-600"><p class="font-black text-brand-ink">{{ trim(($address['first_name']??'').' '.($address['last_name']??'')) }}</p>@foreach(['company_name','address_line_1','address_line_2'] as $field)@if(!empty($address[$field]))<p>{{ $address[$field] }}</p>@endif @endforeach<p>{{ $address['city']??'' }}{{ !empty($address['state']) ? ', '.$address['state'] : '' }} {{ $address['postal_code']??'' }}</p><p>{{ $address['country']??'' }}</p></div></div>
                <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-card"><h3 class="font-black">Payment & Delivery</h3><div class="mt-3 grid gap-3 text-sm text-slate-600"><p><b class="text-brand-ink">Payment:</b> {{ data_get($order->payment_method,'label','Not selected') }}</p><p><b class="text-brand-ink">Product shipping:</b> {{ data_get($order->shipping_method,'title','To be confirmed') }}</p><p><b class="text-brand-ink">Estimated delivery:</b> {{ $order->formattedEstimatedDelivery() ?: data_get($order->shipping_method,'eta','Updated after production') }}</p>@if($order->holiday_adjustment_applied)<div class="rounded-2xl border border-amber-300 bg-amber-50/90 p-4 text-xs text-amber-950 mt-2 shadow-sm"><div class="flex items-start gap-2.5"><svg class="w-4 h-4 text-[#CF5D38] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg><div><div class="flex items-center gap-1.5 mb-0.5"><strong class="font-extrabold text-amber-950">Holiday adjustment</strong><span class="inline-flex items-center rounded-full bg-amber-200/90 px-2 py-0.5 text-[10px] font-black uppercase text-amber-900">Holiday Adjusted</span></div><p class="font-medium text-amber-900 leading-relaxed">{{ $order->holiday_adjustment_reason }}</p></div></div></div>@endif</div></div>
            </section>

            <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-card md:p-7"><div class="flex items-center justify-between"><div><h3 class="text-xl font-black">Status Timeline</h3><p class="text-sm text-slate-500">Order activity recorded by the customer and administration team.</p></div></div><div class="mt-6"><x-storefront.account.orders.timeline :histories="$order->histories" /></div></section>

            @if($order->shipments->isNotEmpty())<section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-card"><div class="flex items-center justify-between"><h3 class="text-xl font-black">Shipments</h3><a class="text-sm font-black text-brand-red" href="{{ route('account.orders.shipments',$order) }}">View split shipment plan →</a></div><div class="mt-4 grid gap-3">@foreach($order->shipments as $shipment)<a href="{{ route('account.orders.shipments.show',[$order,$shipment]) }}" class="flex flex-col justify-between gap-3 rounded-2xl border border-slate-200 p-4 sm:flex-row sm:items-center"><div><p class="font-black">{{ $shipment->shipment_number }}</p><p class="text-sm text-slate-500">{{ $shipment->carrier ?: 'Carrier pending' }} · {{ $shipment->tracking_number ?: 'Tracking pending' }}</p></div><x-storefront.account.orders.status-pill :status="$shipment->status" /></a>@endforeach</div></section>@endif

            <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-card"><h3 class="text-xl font-black">Documents & Downloads</h3><div class="mt-4 flex flex-wrap gap-2">@unless($order->awaitingQuotePrice())<a class="btn btn-outline" href="{{ route('account.orders.invoice',$order) }}">Secure Invoice</a>@endunless@foreach($order->creditNotes as $note)<a class="btn btn-outline" href="{{ route('account.credit-notes.show',$note) }}">Credit Note {{ $note->credit_note_number }}</a>@endforeach @if($order->downloads->isNotEmpty())<a class="btn btn-outline" href="{{ route('account.downloads.index') }}">Order Downloads</a>@endif</div></section>
        </div>
        <x-storefront.account.orders.summary :order="$order" />
    </div>
</x-storefront.account.orders.page>
