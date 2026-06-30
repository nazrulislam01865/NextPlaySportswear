<x-storefront.checkout.shell :seo="$seo" :steps="$steps" :current-step="$currentStep" title="Payment Method" description="Choose the admin-enabled payment method for the final checkout total." :summary="$summary">
    <x-storefront.checkout.panel title="Payment Method" description="The amount below comes from the selected shipping method, rural surcharge, discount, tax, and cart totals. Laravel recalculates it again before order placement.">
        <form method="POST" action="{{ route('checkout.payment-method.store') }}" class="grid gap-6">
            @csrf

            <div class="rounded-[24px] border border-brand-navy/10 bg-brand-navy p-5 text-white shadow-card">
                <p class="text-xs font-black uppercase tracking-[.18em] text-blue-100">Payable amount</p>
                <div class="mt-2 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h3 class="font-display text-4xl font-bold">${{ number_format((float) ($summary['total'] ?? 0), 2) }}</h3>
                        <p class="mt-2 text-sm font-bold text-blue-100">
                            Shipping: {{ $summary['shipping_method']['title'] ?? 'Selected method' }} · {{ $summary['shipping_method']['display_price'] ?? '$0.00' }}
                            @if(($summary['rural_surcharge'] ?? 0) > 0)
                                · Rural surcharge ${{ number_format((float) $summary['rural_surcharge'], 2) }}
                            @endif
                        </p>
                    </div>
                    <span class="rounded-full bg-white px-4 py-2 text-xs font-black uppercase tracking-wide text-brand-navy">Server total</span>
                </div>
            </div>

            @if (count($savedPaymentMethods) > 0 && !empty($savedCardGateway))
                <div>
                    <h3 class="text-sm font-black uppercase tracking-[.16em] text-brand-red">Saved payment methods</h3>
                    <p class="mt-1 text-sm font-semibold text-slate-500">Saved cards use the admin-enabled {{ $savedCardGateway['title'] ?? 'card' }} gateway and are still tokenized by the provider.</p>
                    <div class="mt-3 grid gap-4 md:grid-cols-2">
                        @foreach ($savedPaymentMethods as $method)
                            <label class="cursor-pointer rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-brand-red hover:bg-white has-[:checked]:border-brand-red has-[:checked]:bg-red-50">
                                <div class="flex items-start gap-3">
                                    <input type="radio" name="payment_method" value="saved_card:{{ $method['id'] }}" class="mt-1" @checked(($state['payment_method']['method'] ?? null) === 'saved_card' && (int)($state['payment_method']['saved_payment_method_id'] ?? 0) === (int)$method['id'])>
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <strong class="text-brand-ink">{{ $method['label'] }}</strong>
                                            @if ($method['is_default'])
                                                <span class="rounded-full bg-brand-red px-2 py-0.5 text-[10px] font-black uppercase text-white">Default</span>
                                            @endif
                                        </div>
                                        <p class="mt-2 text-sm font-semibold text-slate-600">{{ $method['brand'] }} ending in {{ $method['last_four'] }} · Expires {{ $method['expiry'] }}</p>
                                        <p class="mt-1 text-xs font-black text-slate-500">Amount: ${{ number_format((float) ($summary['total'] ?? 0), 2) }}</p>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="grid gap-4">
                <h3 class="text-sm font-black uppercase tracking-[.16em] text-brand-red">Available payment methods</h3>

                @forelse ($paymentOptions as $option)
                    <label class="cursor-pointer rounded-2xl border border-slate-200 bg-slate-50 p-5 transition hover:border-brand-red hover:bg-white has-[:checked]:border-brand-red has-[:checked]:bg-red-50">
                        <div class="grid gap-4 sm:grid-cols-[auto_1fr_auto] sm:items-start">
                            <input type="radio" name="payment_method" value="{{ $option['code'] }}" class="mt-1" @checked(($state['payment_method']['method'] ?? null) === ($option['code'] ?? null) || (empty($state['payment_method']['method']) && ($option['is_default'] ?? false)))>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-lg font-black text-brand-ink">{{ $option['title'] }}</h3>
                                    @if($option['is_default'] ?? false)
                                        <span class="rounded-full bg-brand-red px-2 py-0.5 text-[10px] font-black uppercase text-white">Default</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm font-semibold leading-6 text-slate-600">{{ $option['description'] }}</p>
                                @if(!empty($option['instructions']))
                                    <p class="mt-2 rounded-xl bg-white px-3 py-2 text-xs font-bold leading-5 text-slate-500">{{ $option['instructions'] }}</p>
                                @endif
                                <div class="mt-3 flex flex-wrap gap-2 text-[10px] font-black uppercase tracking-wide">
                                    @if($option['requires_provider_redirect'])<span class="rounded-full bg-blue-50 px-2 py-1 text-blue-700">Provider checkout</span>@endif
                                    @if($option['requires_manual_review'])<span class="rounded-full bg-amber-50 px-2 py-1 text-amber-700">Admin review</span>@endif
                                    @if($option['allows_saved_methods'])<span class="rounded-full bg-emerald-50 px-2 py-1 text-emerald-700">Saved cards enabled</span>@endif
                                </div>
                            </div>
                            <div class="flex flex-col items-start gap-2 sm:items-end">
                                <strong class="rounded-full bg-white px-3 py-1 text-xs font-black uppercase tracking-wide text-brand-navy">{{ $option['badge'] }}</strong>
                                <span class="text-sm font-black text-brand-ink">{{ $option['display_amount'] }}</span>
                            </div>
                        </div>
                    </label>
                @empty
                    <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-sm font-bold leading-6 text-red-800">
                        No active payment method is available for this order total. Please contact the store admin or enable a payment method from Admin Panel → Payment Methods.
                    </div>
                @endforelse
            </div>

            <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5">
                <h3 class="text-sm font-black uppercase tracking-[.16em] text-brand-navy">Secure payment handling</h3>
                <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">Payment options come from the admin backend. Raw card numbers and CVV are never accepted by this Laravel application; online methods should redirect to a PCI-compliant hosted provider.</p>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
                <a class="btn btn-light" href="{{ route('checkout.shipping-method') }}">Back</a>
                <button class="btn btn-red" type="submit" @disabled(count($paymentOptions) === 0)>Review Order</button>
            </div>
        </form>
    </x-storefront.checkout.panel>
</x-storefront.checkout.shell>
