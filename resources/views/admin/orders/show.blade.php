<x-layouts.admin :title="'Order '.$order->order_number">
    @php
        $shipping = (array) data_get($order->shipping_address, 'address', $order->shipping_address ?? []);
        $billing = (array) data_get($order->billing_address, 'address', $order->billing_address ?? []);
        $orderItemsById = $order->items->keyBy('id');
    @endphp

    <div class="mb-6 flex flex-col justify-between gap-4 rounded-3xl bg-brand-dark p-6 text-white shadow-card md:flex-row md:items-start">
        <div>
            <p class="text-xs font-black uppercase tracking-[.16em] text-brand-red">Placed {{ $order->placed_at?->format('M d, Y · g:i A') }}</p>
            <h2 class="mt-2 text-3xl font-black">{{ $order->customer_name }}</h2>
            <p class="mt-1 text-sm text-slate-300">{{ $order->customer_email }} · {{ $order->customer_phone ?: 'No phone' }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-storefront.account.orders.status-pill :status="$order->status" />
            <x-storefront.account.orders.status-pill :status="$order->payment_status" />
            <x-storefront.account.orders.status-pill :status="$order->fulfillment_status" />
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(340px,.8fr)] xl:items-start">
        <div class="space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card">
                <div class="flex items-center justify-between gap-4">
                    <div><h3 class="text-xl font-black">Order Items</h3><p class="text-sm text-slate-500">Immutable product and customization snapshots captured at checkout.</p></div>
                    <strong class="text-2xl">{{ $order->currency }} {{ number_format((float) $order->grand_total, 2) }}</strong>
                </div>
                <div class="mt-5 grid gap-4">@foreach($order->items as $item)<x-storefront.account.orders.item-row :item="$item" />@endforeach</div>
            </section>

            <section class="grid gap-5 md:grid-cols-2">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card">
                    <h3 class="text-lg font-black">Shipping Address</h3>
                    <div class="mt-3 text-sm leading-6 text-slate-600">
                        <p class="font-bold text-slate-900">{{ trim(($shipping['first_name'] ?? '').' '.($shipping['last_name'] ?? '')) ?: $order->customer_name }}</p>
                        @if(!empty($shipping['company_name']))<p>{{ $shipping['company_name'] }}</p>@endif
                        <p>{{ $shipping['address_line_1'] ?? 'Address not provided' }}</p>
                        @if(!empty($shipping['address_line_2']))<p>{{ $shipping['address_line_2'] }}</p>@endif
                        <p>{{ collect([$shipping['city'] ?? null, $shipping['state'] ?? null, $shipping['postal_code'] ?? null])->filter()->join(', ') }}</p>
                        <p>{{ $shipping['country'] ?? '' }}</p>
                    </div>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card">
                    <h3 class="text-lg font-black">Billing & Delivery</h3>
                    <div class="mt-3 text-sm leading-6 text-slate-600">
                        <p><b class="text-slate-900">Billing:</b> {{ data_get($order->billing_address, 'same_as_shipping') ? 'Same as shipping address' : (trim(($billing['address_line_1'] ?? '').' '.($billing['city'] ?? '')) ?: 'Not provided') }}</p>
                        <p><b class="text-slate-900">Shipping method:</b> {{ data_get($order->shipping_method, 'title', data_get($order->shipping_method, 'label', 'Not selected')) }}</p>
                        <p><b class="text-slate-900">Payment choice:</b> {{ data_get($order->payment_method, 'label', str(data_get($order->payment_method, 'method', 'pending'))->headline()) }}</p>
                        @if($order->customer_note)<p class="mt-2"><b class="text-slate-900">Customer note:</b> {{ $order->customer_note }}</p>@endif
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card">
                <div class="flex items-center justify-between gap-4"><div><h3 class="text-xl font-black">Payment Attempts</h3><p class="text-sm text-slate-500">Only provider references and safe metadata are stored. Raw card details are never stored.</p></div></div>
                <div class="mt-4 grid gap-3">
                    @forelse($order->payments as $payment)
                        <div class="flex flex-col justify-between gap-3 rounded-2xl border border-slate-200 p-4 sm:flex-row sm:items-center">
                            <div><p class="font-black">{{ str($payment->provider)->headline() }} · {{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}</p><p class="text-xs text-slate-500">{{ $payment->provider_reference ?: 'No provider reference' }} · {{ $payment->attempted_at?->format('M d, Y g:i A') }}</p>@if($payment->failure_message)<p class="mt-1 text-sm text-red-700">{{ $payment->failure_message }}</p>@endif</div>
                            <x-storefront.account.orders.status-pill :status="$payment->status" />
                        </div>
                    @empty
                        <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">No payment attempt has been recorded.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card">
                <h3 class="text-xl font-black">Shipments</h3>
                <div class="mt-4 grid gap-4">
                    @forelse($order->shipments as $shipment)
                        <form method="POST" action="{{ route('admin.orders.shipments.update', [$order, $shipment]) }}" class="rounded-2xl border border-slate-200 p-4">
                            @csrf @method('PATCH')
                            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                                <div><p class="font-black">{{ $shipment->shipment_number }}</p><p class="text-sm text-slate-500">{{ $shipment->items->sum('quantity') }} item(s) allocated · {{ $shipment->tracking_number ?: 'Tracking pending' }}</p></div>
                                <x-storefront.account.orders.status-pill :status="$shipment->status" />
                            </div>
                            <div class="mt-4 grid gap-3 md:grid-cols-2">
                                <label><span class="admin-label">Status</span><select class="admin-input" name="status">@foreach(collect($shipmentStatuses)->only(config('commerce.shipment_status_transitions.'.$shipment->status, [$shipment->status])) as $key => $label)<option value="{{ $key }}" @selected($shipment->status === $key)>{{ $label }}</option>@endforeach</select></label>
                                <label><span class="admin-label">Carrier</span><input class="admin-input" name="carrier" value="{{ $shipment->carrier }}" maxlength="100"></label>
                                <label><span class="admin-label">Service</span><input class="admin-input" name="service" value="{{ $shipment->service }}" maxlength="120"></label>
                                <label><span class="admin-label">Tracking number</span><input class="admin-input" name="tracking_number" value="{{ $shipment->tracking_number }}" maxlength="190"></label>
                                <label class="md:col-span-2"><span class="admin-label">Tracking URL</span><input class="admin-input" type="url" name="tracking_url" value="{{ $shipment->tracking_url }}" maxlength="2048"></label>
                                <label><span class="admin-label">Estimated delivery</span><input class="admin-input" type="datetime-local" name="estimated_delivery_at" value="{{ $shipment->estimated_delivery_at?->format('Y-m-d\TH:i') }}"></label>
                                <label><span class="admin-label">Internal shipment note</span><textarea class="admin-textarea min-h-[80px]" name="notes" maxlength="3000">{{ $shipment->notes }}</textarea></label>
                            </div>
                            <div class="mt-4 flex justify-end"><button class="btn btn-white" type="submit">Update Shipment</button></div>
                        </form>
                    @empty
                        <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">No shipment has been created.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card">
                <h3 class="text-xl font-black">Cancellation & Change Requests</h3>
                <div class="mt-4 grid gap-4">
                    @forelse($order->changeRequests as $change)
                        @php($requestedItems = collect(data_get($change->requested_changes, 'items', [])))
                        <form method="POST" action="{{ route('admin.orders.requests.update', [$order, $change]) }}" class="rounded-2xl border border-slate-200 p-4">
                            @csrf @method('PATCH')
                            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                                <div>
                                    <p class="font-black">{{ str($change->type)->headline() }} · {{ $change->request_number }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ str($change->reason_code)->headline() }} · {{ $change->scope === 'entire_order' ? 'Entire order' : 'Selected items' }}</p>
                                </div>
                                <x-storefront.account.orders.status-pill :status="$change->status" />
                            </div>
                            @if($change->reason)<p class="mt-3 rounded-xl bg-slate-50 p-3 text-sm leading-6 text-slate-700">{{ $change->reason }}</p>@endif
                            @if($requestedItems->isNotEmpty())
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach($requestedItems as $requestedItem)
                                        @php($sourceItem = $orderItemsById->get((int) data_get($requestedItem, 'id')))
                                        @if($sourceItem)<span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold">{{ $sourceItem->product_name }} × {{ data_get($requestedItem, 'quantity', 0) }}</span>@endif
                                    @endforeach
                                </div>
                            @endif
                            <div class="mt-4 grid gap-3 md:grid-cols-[220px_1fr_auto] md:items-end">
                                <label><span class="admin-label">Request status</span><select class="admin-input" name="status">@foreach(config('commerce.change_request_statuses', []) as $key => $label)<option value="{{ $key }}" @selected($change->status === $key)>{{ $label }}</option>@endforeach</select></label>
                                <label><span class="admin-label">Admin response</span><textarea class="admin-textarea min-h-[80px]" name="admin_note" maxlength="3000">{{ $change->admin_note }}</textarea></label>
                                <button class="btn btn-white mb-0.5" type="submit">Update Request</button>
                            </div>
                            @if($change->type === 'cancel')<p class="mt-3 text-xs leading-5 text-slate-500">Set to <b>Completed</b> only after the cancellation has been operationally handled. Unallocated quantities are then marked cancelled. Paid orders may still require a separate refund.</p>@endif
                        </form>
                    @empty
                        <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">No cancellation or change requests.</p>
                    @endforelse

                    @foreach($order->returnRequests as $return)
                        <a href="{{ route('admin.returns.show', $return) }}" class="rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
                            <div class="flex justify-between gap-3"><div><p class="font-black">{{ str($return->type)->headline() }} · {{ $return->return_number }}</p><p class="mt-1 text-sm text-slate-500">{{ str($return->reason_code)->headline() }}</p></div><x-storefront.account.orders.status-pill :status="$return->status" /></div>
                        </a>
                    @endforeach
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card">
                <h3 class="text-xl font-black">Private Order Downloads</h3>
                <div class="mt-4 grid gap-3">
                    @forelse($order->downloads as $download)
                        <div class="flex flex-col justify-between gap-3 rounded-2xl border border-slate-200 p-4 sm:flex-row sm:items-center">
                            <div><p class="font-black">{{ $download->title }}</p><p class="text-sm text-slate-500">{{ $download->original_name }} · {{ number_format(($download->file_size ?? 0) / 1024, 1) }} KB · {{ $download->download_count }} downloads</p></div>
                            <form method="POST" action="{{ route('admin.orders.downloads.destroy', [$order, $download]) }}" onsubmit="return confirm('Remove this private download?')">@csrf @method('DELETE')<button class="btn btn-white" type="submit">Remove</button></form>
                        </div>
                    @empty
                        <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">No digital files attached.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card"><h3 class="text-xl font-black">Status History</h3><div class="mt-5"><x-storefront.account.orders.timeline :histories="$order->histories" /></div></section>
        </div>

        <aside class="space-y-6 xl:sticky xl:top-28">
            <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card">
                @csrf @method('PATCH')
                <h3 class="text-lg font-black">Update Order</h3>
                <label class="mt-4 block"><span class="admin-label">Order status</span><select class="admin-input" name="status">@foreach($orderStatuses as $key => $label)<option value="{{ $key }}" @selected($order->status === $key)>{{ $label }}</option>@endforeach</select></label>
                <label class="mt-4 block"><span class="admin-label">Payment status</span><select class="admin-input" name="payment_status">@foreach($paymentStatuses as $key => $label)<option value="{{ $key }}" @selected($order->payment_status === $key)>{{ $label }}</option>@endforeach</select></label>
                <label class="mt-4 block"><span class="admin-label">Fulfillment status</span><select class="admin-input" name="fulfillment_status">@foreach($fulfillmentStatuses as $key => $label)<option value="{{ $key }}" @selected($order->fulfillment_status === $key)>{{ $label }}</option>@endforeach</select></label>
                <label class="mt-4 block"><span class="admin-label">Internal note</span><textarea class="admin-textarea" name="admin_note">{{ old('admin_note', $order->admin_note) }}</textarea></label>
                <button class="btn btn-red mt-5 w-full" type="submit">Update Order</button>
            </form>

            <form method="POST" action="{{ route('admin.orders.shipments.store', $order) }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card">
                @csrf
                <h3 class="text-lg font-black">Create Shipment</h3>
                <label class="mt-4 block"><span class="admin-label">Shipment status</span><select class="admin-input" name="status">@foreach($shipmentStatuses as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
                <div class="mt-4 grid gap-3 sm:grid-cols-2"><label><span class="admin-label">Carrier</span><input class="admin-input" name="carrier" maxlength="100"></label><label><span class="admin-label">Service</span><input class="admin-input" name="service" maxlength="120"></label></div>
                <label class="mt-4 block"><span class="admin-label">Tracking number</span><input class="admin-input" name="tracking_number" maxlength="190"></label>
                <label class="mt-4 block"><span class="admin-label">Tracking URL</span><input class="admin-input" type="url" name="tracking_url" maxlength="2048"></label>
                <label class="mt-4 block"><span class="admin-label">Estimated delivery</span><input class="admin-input" type="datetime-local" name="estimated_delivery_at"></label>
                <div class="mt-4"><span class="admin-label">Items in this package</span><div class="mt-2 grid gap-2">
                    @foreach($order->items as $item)
                        @php($available = $item->remainingFulfillableQuantity())
                        <div class="grid grid-cols-[1fr_90px] items-center gap-3 rounded-xl border border-slate-200 p-3 {{ $available < 1 ? 'opacity-60' : '' }}">
                            <div><p class="text-sm font-black">{{ $item->product_name }}</p><p class="text-xs text-slate-500">Unallocated {{ $available }}</p></div>
                            <div><input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $item->id }}"><input class="admin-input mt-0" type="number" name="items[{{ $loop->index }}][quantity]" min="0" max="{{ $available }}" value="0" @readonly($available < 1)></div>
                        </div>
                    @endforeach
                </div></div>
                <label class="mt-4 block"><span class="admin-label">Shipment note</span><textarea class="admin-textarea min-h-[80px]" name="notes" maxlength="1500"></textarea></label>
                <button class="btn btn-red mt-5 w-full" type="submit">Create Shipment</button>
            </form>

            <form method="POST" enctype="multipart/form-data" action="{{ route('admin.orders.downloads.store', $order) }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card">
                @csrf
                <h3 class="text-lg font-black">Add Private Download</h3>
                <label class="mt-4 block"><span class="admin-label">Title</span><input class="admin-input" name="title" required maxlength="220"></label>
                <label class="mt-4 block"><span class="admin-label">File</span><input class="admin-input h-auto py-3" type="file" name="file" required></label>
                <div class="mt-4 grid gap-3 sm:grid-cols-2"><label><span class="admin-label">Download limit</span><input class="admin-input" type="number" name="download_limit" min="1" max="1000"></label><label><span class="admin-label">Expires</span><input class="admin-input" type="datetime-local" name="expires_at"></label></div>
                <label class="mt-4 block"><span class="admin-label">License note</span><textarea class="admin-textarea" name="license_note" maxlength="2000"></textarea></label>
                <button class="btn btn-red mt-5 w-full" type="submit">Upload Secure File</button>
            </form>
        </aside>
    </div>
</x-layouts.admin>
