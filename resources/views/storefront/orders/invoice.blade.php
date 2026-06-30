<x-layouts.storefront :seo="$seo">
    <section class="bg-slate-50 py-10 lg:py-14">
        <div class="site-container">
            <div class="mb-6 flex flex-col justify-between gap-3 rounded-[24px] border border-slate-200 bg-white p-5 shadow-card sm:flex-row sm:items-center print:hidden">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Secure invoice</p>
                    <h1 class="mt-1 text-3xl font-black text-brand-ink">Invoice Preview</h1>
                    <p class="mt-1 text-sm font-semibold text-slate-600">Print or save as PDF from your browser. Production PDF generation can later run through Laravel queues.</p>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <button type="button" onclick="window.print()" class="btn btn-red">Print / Save PDF</button>
                    <a href="{{ route('orders.details.legacy') }}" class="btn btn-white">Back to Order</a>
                </div>
            </div>

            <article class="mx-auto max-w-5xl rounded-[28px] border border-slate-200 bg-white p-6 shadow-card print:border-0 print:shadow-none lg:p-9">
                <div class="flex flex-col justify-between gap-6 border-b border-slate-200 pb-6 sm:flex-row">
                    <div>
                        <a href="{{ route('home') }}" class="flex items-center gap-3 font-display text-2xl font-bold uppercase text-brand-ink">
                            <span class="grid h-9 w-9 place-items-center rounded-lg border-[3px] border-brand-red text-brand-red">□</span>
                            <span>NextPlay <span class="text-brand-red">Sportswear</span></span>
                        </a>
                        <p class="mt-3 max-w-md text-sm font-semibold leading-6 text-slate-600">Custom sportswear, uniforms, jerseys, hoodies, caps, bags, and team gear.</p>
                    </div>
                    <div class="text-left sm:text-right">
                        <p class="text-xs font-black uppercase tracking-[.18em] text-slate-500">Invoice</p>
                        <strong class="mt-1 block text-2xl text-brand-ink">INV-{{ $order['order_number'] }}</strong>
                        <span class="mt-1 block text-sm font-bold text-slate-500">{{ $order['placed_display'] }}</span>
                    </div>
                </div>

                @php($shippingLines = app(\App\Services\Order\OrderExperienceService::class)->addressLines($order['shipping_address'] ?? []))
                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <x-storefront.order.info-box title="Billed To" class="bg-white">
                        {{ $order['customer_name'] }}<br>
                        {{ $order['customer_email'] }}<br>
                        @foreach ($shippingLines as $line)
                            {{ $line }}<br>
                        @endforeach
                    </x-storefront.order.info-box>
                    <x-storefront.order.info-box title="Invoice Details" class="bg-white">
                        Order: {{ $order['order_number'] }}<br>
                        Status: {{ \Illuminate\Support\Str::headline($order['payment_status']) }}<br>
                        Currency: USD<br>
                        Payment: {{ $order['payment_method']['label'] ?? 'Secure payment provider' }}
                    </x-storefront.order.info-box>
                </div>

                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full border-collapse text-left text-sm">
                        <thead>
                            <tr class="border-y border-slate-200 bg-slate-50 text-xs font-black uppercase tracking-wide text-slate-500">
                                <th class="px-4 py-3">Product</th>
                                <th class="px-4 py-3">Customization</th>
                                <th class="px-4 py-3">Qty</th>
                                <th class="px-4 py-3">Unit</th>
                                <th class="px-4 py-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order['items'] as $item)
                                <tr class="border-b border-slate-100 align-top">
                                    <td class="px-4 py-4 font-bold text-brand-ink">{{ $item['title'] }}</td>
                                    <td class="px-4 py-4 font-semibold leading-6 text-slate-600">
                                        {{ $item['customization']['design_option'] }} · {{ $item['customization']['size_summary'] }}
                                        @if ($item['customization']['notes'] !== '')
                                            <br>{{ $item['customization']['notes'] }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 font-bold text-slate-700">{{ $item['quantity'] }}</td>
                                    <td class="px-4 py-4 font-bold text-slate-700">${{ number_format($item['unit_price'], 2) }}</td>
                                    <td class="px-4 py-4 text-right font-black text-brand-ink">${{ number_format($item['line_total'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 ml-auto max-w-sm rounded-2xl bg-slate-50 p-5">
                    <div class="grid gap-2 text-sm font-bold text-slate-600">
                        <div class="flex justify-between"><span>Subtotal</span><strong class="text-brand-ink">${{ number_format($order['totals']['subtotal'], 2) }}</strong></div>
                        <div class="flex justify-between"><span>Discount @if(!empty($order['totals']['coupon_code']))<span class="text-xs font-black text-green-700">({{ $order['totals']['coupon_code'] }})</span>@endif</span><strong class="text-brand-red">-${{ number_format($order['totals']['discount'], 2) }}</strong></div>
                        <div class="flex justify-between"><span>Shipping</span><strong class="text-brand-ink">${{ number_format($order['totals']['shipping'], 2) }}</strong></div>
                        <div class="flex justify-between"><span>Tax</span><strong class="text-brand-ink">${{ number_format($order['totals']['tax'], 2) }}</strong></div>
                        <div class="mt-2 flex justify-between border-t border-slate-200 pt-4 text-lg text-brand-ink"><span>Total</span><strong>${{ number_format($order['totals']['total'], 2) }}</strong></div>
                    </div>
                </div>

                <div class="mt-6 rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm font-bold leading-6 text-blue-800 print:bg-white">
                    This invoice is generated from the secure order snapshot. In production, invoice PDFs should be generated from persisted order/payment records and stored privately or delivered through signed URLs.
                </div>
            </article>
        </div>
    </section>
</x-layouts.storefront>
