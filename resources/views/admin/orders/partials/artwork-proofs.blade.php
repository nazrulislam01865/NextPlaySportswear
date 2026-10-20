@php($proofsByItem = $order->artworkProofs->groupBy('order_item_id'))
<section class="mb-5 rounded-xl border border-slate-200 bg-white p-4 shadow-card" id="order-artwork-proofs" aria-labelledby="artwork-management-heading">
    <div class="border-b border-slate-200 pb-3">
        <div>
            <h2 id="artwork-management-heading" class="text-base font-semibold text-brand-ink">Customer Artwork Approvals</h2>
            <p class="mt-1 text-xs text-slate-500">Review customer feedback, reply to the customer, and publish updated artwork versions.</p>
        </div>
    </div>

    <div class="divide-y divide-slate-200">
        @foreach($order->items as $item)
            @php($itemProofs = ($proofsByItem->get($item->id) ?? collect())->sortByDesc('version')->values())
            @php($currentProof = $itemProofs->first())
            <section class="py-4 last:pb-0" aria-label="Artwork for {{ $item->product_name }}">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <h3 class="text-sm font-semibold text-brand-ink">{{ $item->product_name }} <span class="font-normal text-slate-500">· {{ $item->quantity }} items</span></h3>
                    @if($currentProof)
                        <span @class([
                            'inline-flex rounded-full px-3 py-1 text-xs font-semibold',
                            'bg-amber-100 text-amber-800' => $currentProof->status === 'pending_review',
                            'bg-rose-100 text-rose-800' => in_array($currentProof->status, ['needs_revision', 'rejected'], true),
                            'bg-emerald-100 text-emerald-800' => $currentProof->status === 'approved',
                        ])>{{ $currentProof->statusLabel() }} · v{{ $currentProof->version }}</span>
                    @endif
                </div>

                @if($currentProof)
                    @include('admin.orders.partials.artwork-proof-review', ['proof' => $currentProof])
                    @if($itemProofs->count() > 1)
                        <details class="mt-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            <summary class="cursor-pointer text-sm font-semibold text-brand-ink">Previous proof versions ({{ $itemProofs->count() - 1 }})</summary>
                            <div class="mt-3 divide-y divide-slate-200">
                                @foreach($itemProofs->slice(1) as $previous)
                                    <div class="flex flex-wrap items-start justify-between gap-3 py-3 text-sm">
                                        <div>
                                            <p class="font-semibold text-slate-800">Version {{ $previous->version }} · {{ $previous->statusLabel() }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ \App\Support\ViewerTime::render($previous->created_at, 'M j, Y · g:i A') }}</p>
                                            @if($previous->customer_feedback)<p class="mt-2 max-w-xl whitespace-pre-wrap break-words text-slate-700"><strong>Customer feedback:</strong> {{ $previous->customer_feedback }}</p>@endif
                                            @foreach($previous->messages as $message)
                                                <p class="mt-1 max-w-xl whitespace-pre-wrap break-words text-slate-600"><strong>{{ $message->sender_role === 'designer' ? 'Design Team' : 'Customer' }}:</strong> {{ $message->message }}</p>
                                            @endforeach
                                        </div>
                                        <a class="btn btn-white" href="{{ route('admin.orders.artwork-proofs.file', [$order, $previous]) }}" target="_blank" rel="noopener noreferrer">View v{{ $previous->version }}</a>
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @endif
                @else
                    <p class="mb-3 rounded-lg bg-slate-50 p-3 text-sm text-slate-600">No designer proof published yet. Review the customer's submitted artwork in Order Items below before uploading.</p>
                @endif

                @if($canManageOrders && !in_array($order->status, ['cancelled', 'completed'], true))
                    @include('admin.orders.partials.artwork-proof-upload', ['currentProof' => $currentProof])
                @endif
            </section>
        @endforeach
    </div>
</section>
