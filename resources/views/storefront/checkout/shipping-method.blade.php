@php
    $selectedShippingCode = $summary['shipping_method']['code'] ?? ($shippingMethods[0]['code'] ?? null);
    $methodPreview = collect($shippingMethods)->map(function ($method) use ($summary) {
        $total = max(0, ($summary['subtotal'] ?? 0) + ($summary['customization_total'] ?? 0) - ($summary['discount'] ?? 0) + ($method['price'] ?? 0) + ($summary['tax'] ?? 0));
        $ruralAmount = (float) data_get($method, 'rural_surcharge.amount', 0);

        return [
            'code' => $method['code'],
            'shipping_title' => (string) ($method['title'] ?? $method['name'] ?? 'Shipping'),
            'shipping' => '$'.number_format((float) ($method['price'] ?? 0), 2),
            'shipping_base' => '$'.number_format((float) ($method['base_price'] ?? 0), 2),
            'shipping_base_amount' => round((float) ($method['base_price'] ?? 0), 2),
            'rural' => '$'.number_format($ruralAmount, 2),
            'rural_amount' => round($ruralAmount, 2),
            'rural_postal_code' => (string) data_get($method, 'rural_surcharge.postal_code', ''),
            'total' => '$'.number_format($total, 2),
            'total_amount' => round($total, 2),
            'eta' => $method['eta'] ?? '',
            'quote_based' => (bool) ($method['quote_based'] ?? false),
        ];
    })->keyBy('code')->all();
@endphp

<x-storefront.checkout.shell :seo="$seo" :steps="$steps" :current-step="$currentStep" title="Shipping Method" description="Select a shipping method based on order timeline, production needs, and delivery urgency." :summary="$summary">
    <x-storefront.checkout.panel title="Shipping Method" description="Shipping options are loaded from admin settings and recalculated with your shipping ZIP, cart quantity, subtotal, and rural surcharge rules.">
        <form method="POST" action="{{ route('checkout.shipping-method.store') }}" class="grid gap-6" x-data="{ selected: @js($selectedShippingCode), previews: @js($methodPreview), current(){ return this.previews[this.selected] || {}; }, notify(){ this.$dispatch('checkout-shipping-preview', this.current()); }, init(){ this.$nextTick(() => this.notify()); } }">
            @csrf

            @if(empty($shippingMethods))
                <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-sm font-bold leading-6 text-red-800">
                    No shipping method is currently available for this cart and shipping address. Please contact support or ask the admin to enable a matching shipping method.
                </div>
            @else
                <div class="grid gap-4">
                    @foreach ($shippingMethods as $method)
                        <label class="cursor-pointer rounded-2xl border border-slate-200 bg-slate-50 p-5 transition hover:border-brand-red hover:bg-white has-[:checked]:border-brand-red has-[:checked]:bg-red-50">
                            <div class="grid gap-4 sm:grid-cols-[auto_1fr_auto] sm:items-start">
                                <input type="radio" name="shipping_method" value="{{ $method['code'] }}" class="mt-1" x-model="selected" x-on:change="notify()" @checked($selectedShippingCode === $method['code']) required>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-lg font-black text-brand-ink">{{ $method['title'] }}</h3>
                                        @if(!empty($method['is_default']))<span class="rounded-full bg-blue-50 px-2 py-1 text-[10px] font-black uppercase tracking-wide text-blue-700">Default</span>@endif
                                        @if(!empty($method['quote_based']))<span class="rounded-full bg-amber-50 px-2 py-1 text-[10px] font-black uppercase tracking-wide text-amber-700">Quote review</span>@endif
                                    </div>
                                    <p class="mt-1 text-sm font-semibold leading-6 text-slate-600">{{ $method['description'] }}</p>
                                    <small class="mt-2 inline-block text-xs font-black uppercase tracking-wide text-slate-500">{{ $method['eta'] }}</small>
                                    @if(!empty($method['rural_surcharge']))
                                        <span class="mt-2 block text-xs font-bold text-amber-700">Includes ${{ number_format($method['rural_surcharge']['amount'] ?? 0, 2) }} rural area surcharge for {{ $method['rural_surcharge']['postal_code'] ?? 'this ZIP' }}.</span>
                                    @endif
                                </div>
                                <strong class="font-display text-2xl font-bold text-brand-navy">{{ $method['display_price'] }}</strong>
                            </div>
                        </label>
                    @endforeach
                </div>

                <div class="grid gap-4 rounded-2xl border border-blue-100 bg-blue-50 p-5 text-sm font-bold text-brand-navy md:grid-cols-3">
                    <div>
                        <span class="block text-xs uppercase tracking-wide text-blue-500">Selected shipping</span>
                        <strong class="mt-1 block text-lg" x-text="current().shipping || '$0.00'"></strong>
                    </div>
                    <div>
                        <span class="block text-xs uppercase tracking-wide text-blue-500">Order total after shipping</span>
                        <strong class="mt-1 block text-lg" x-text="current().total || @js('$'.number_format($summary['total'] ?? 0, 2))"></strong>
                    </div>
                    <div>
                        <span class="block text-xs uppercase tracking-wide text-blue-500">Delivery estimate</span>
                        <strong class="mt-1 block text-sm leading-5" x-text="current().eta || 'After artwork approval'"></strong>
                    </div>
                </div>
            @endif

            @if(($summary['product_shipping_total'] ?? 0) > 0)
                <div class="rounded-2xl border border-slate-200 bg-white p-4 text-sm font-bold leading-6 text-slate-700">
                    <p class="text-brand-ink">Product-specific shipping already added automatically</p>
                    <p class="mt-1 text-xs font-semibold text-slate-500">These charges came from product-level shipping options selected on the product page and are already included in the configured item pricing, so they are not charged twice here.</p>
                    <div class="mt-3 grid gap-2">
                        @foreach(($summary['product_shipping_lines'] ?? []) as $line)
                            <div class="flex justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2 text-xs">
                                <span>{{ $line['product'] ?? 'Product' }} · {{ $line['method'] ?? 'Shipping' }}</span>
                                <strong>${{ number_format($line['amount'] ?? 0, 2) }}</strong>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-bold leading-6 text-amber-900">
                Rush delivery does not replace design proof approval. Custom production starts only after artwork, names, numbers, and sizes are confirmed. @if(!empty($summary['rural_surcharge_details'])) Rural surcharge is controlled by admin ZIP/postal rules and has already been included in the prices above. @endif
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
                <a class="btn btn-light" href="{{ route('checkout.billing-address') }}">Back</a>
                <button class="btn btn-red" type="submit" @disabled(empty($shippingMethods))>Continue to Payment</button>
            </div>
        </form>
    </x-storefront.checkout.panel>
</x-storefront.checkout.shell>
