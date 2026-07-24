<x-storefront.checkout.shell :seo="$seo" :steps="$steps" :current-step="$currentStep" title="Review & Place Order" description="Review every order detail and submit your order once from this final page." :summary="$summary">
    <x-storefront.checkout.panel title="Review & Place Your Order" description="Check the information below, confirm once, and place your custom sportswear order securely.">
        <form
            data-single-submit
            method="POST"
            action="{{ route('checkout.place-order.submit') }}"
            class="grid gap-5"
            x-data="{ processing: false }"
            @submit="processing = true"
        >
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ $orderIdempotencyKey }}">
            <input type="hidden" name="confirm_details" value="1">

            <div class="grid gap-4">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="flex items-center justify-between gap-4"><h3 class="font-black text-brand-ink">Contact Information</h3><a class="text-sm font-black text-brand-red" href="{{ route('checkout.information') }}">Edit</a></div>
                    <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">{{ trim(($state['information']['first_name'] ?? '') . ' ' . ($state['information']['last_name'] ?? '')) }} · {{ $state['information']['email'] ?? 'Not provided' }} · {{ $state['information']['phone'] ?? 'No phone' }}</p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="flex items-center justify-between gap-4"><h3 class="font-black text-brand-ink">Shipping Address</h3><a class="text-sm font-black text-brand-red" href="{{ route('checkout.shipping-address') }}">Edit</a></div>
                    @php($ship = $state['shipping_address']['address'] ?? [])
                    <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">{{ $state['shipping_address']['label'] ?? 'Shipping recipient' }}, {{ $ship['address_line_1'] ?? '' }}, {{ $ship['city'] ?? '' }}, {{ $ship['state'] ?? '' }} {{ $ship['postal_code'] ?? '' }}, {{ $ship['country'] ?? '' }}</p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="flex items-center justify-between gap-4"><h3 class="font-black text-brand-ink">Billing Address</h3><a class="text-sm font-black text-brand-red" href="{{ route('checkout.billing-address') }}">Edit</a></div>
                    @if (($state['billing_address']['same_as_shipping'] ?? true))
                        <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">Same as shipping address.</p>
                    @else
                        @php($bill = $state['billing_address']['address'] ?? [])
                        <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">{{ $state['billing_address']['label'] ?? 'Billing recipient' }}, {{ $bill['address_line_1'] ?? '' }}, {{ $bill['city'] ?? '' }}, {{ $bill['state'] ?? '' }} {{ $bill['postal_code'] ?? '' }}, {{ $bill['country'] ?? '' }}</p>
                    @endif
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="flex items-center justify-between gap-4"><h3 class="font-black text-brand-ink">Production &amp; Shipping</h3><a class="text-sm font-black text-brand-red" href="{{ route('cart.index') }}">Edit products</a></div>
                    <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">These methods were selected while each product was configured and are carried into the saved order automatically.</p>
                    <div class="mt-4 grid gap-3">
                        @foreach(($summary['fulfillment_lines'] ?? []) as $line)
                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <p class="font-black text-brand-ink">{{ $line['product'] ?? 'Product' }} <span class="text-xs text-slate-400">× {{ $line['quantity'] ?? 1 }}</span></p>
                                <div class="mt-2 grid gap-1 text-sm font-semibold leading-6 text-slate-600">
                                    <p><strong class="text-brand-ink">Production:</strong> {{ data_get($line, 'production.label', 'Standard production') }} · {{ data_get($line, 'production.display_amount', 'Included') }}</p>
                                    <p><strong class="text-brand-ink">Shipping:</strong> {{ data_get($line, 'shipping.label', 'Included shipping') }} · {{ data_get($line, 'shipping.display_amount', 'Included') }}</p>
                                    @if(($line['estimated_minimum_days'] ?? 0) > 0 || ($line['estimated_maximum_days'] ?? 0) > 0)
                                        <p><strong class="text-brand-ink">Estimate:</strong> {{ $line['estimated_minimum_days'] ?? 0 }}–{{ $line['estimated_maximum_days'] ?? $line['estimated_minimum_days'] ?? 0 }} business days</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4 grid gap-2 rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm font-bold text-brand-navy sm:grid-cols-2">
                        <p>Shipping charges: <strong>${{ number_format((float) ($summary['shipping'] ?? 0), 2) }}</strong></p>
                        @if(($summary['rural_surcharge'] ?? 0) > 0)
                            <p>Includes rural surcharge: <strong>${{ number_format((float) $summary['rural_surcharge'], 2) }}</strong></p>
                        @endif
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="flex items-center justify-between gap-4"><h3 class="font-black text-brand-ink">Payment Method</h3><a class="text-sm font-black text-brand-red" href="{{ route('checkout.payment-method') }}">Edit</a></div>
                    <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">
                        {{ $summary['payment_method']['label'] ?? 'Payment method not selected' }}
                        @if(!empty($summary['payment_method']['gateway_label']))
                            · {{ $summary['payment_method']['gateway_label'] }}
                        @endif
                        · Payable {{ $summary['payment_method']['display_amount'] ?? '$'.number_format((float)($summary['total'] ?? 0), 2) }}
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="flex items-center justify-between gap-4"><h3 class="font-black text-brand-ink">Customization Details</h3><a class="text-sm font-black text-brand-red" href="{{ route('cart.index') }}">Edit</a></div>
                    <div class="mt-3 grid gap-3">
                        @foreach (($summary['items'] ?? []) as $item)
                            <p class="text-sm font-semibold leading-6 text-slate-600"><strong class="text-brand-ink">{{ $item['product']['short_title'] ?? $item['product']['title'] ?? 'Product' }}:</strong> {{ $item['customization']['design_option'] ?? 'Custom design' }} · {{ $item['customization']['size_summary'] ?? 'Sizes pending' }}</p>
                        @endforeach
                    </div>
                </div>
            </div>

            <label class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-bold leading-6 text-amber-900">
                <input type="checkbox" name="terms" value="1" class="mt-1" required>
                <span><strong>I confirm the order details are correct and agree to the Terms, Privacy Policy, and Custom Product Production Policy.</strong><br><span class="font-semibold">Custom products may not be refundable once production begins.</span></span>
            </label>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
                <a class="btn btn-light" href="{{ route('checkout.payment-method') }}">Back</a>
                <button class="btn btn-red" type="submit" x-bind:disabled="processing" x-bind:class="processing ? 'pointer-events-none opacity-70' : ''">
                    <span x-show="!processing">Place Secure Order</span>
                    <span x-cloak x-show="processing">Processing Securely...</span>
                </button>
            </div>
        </form>
    </x-storefront.checkout.panel>
</x-storefront.checkout.shell>
