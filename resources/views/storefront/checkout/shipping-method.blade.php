<x-storefront.checkout.shell :seo="$seo" :steps="$steps" :current-step="$currentStep" title="Shipping Method" description="Select a shipping method based on order timeline, production needs, and delivery urgency." :summary="$summary">
    <x-storefront.checkout.panel title="Shipping Method" description="Delivery timing depends on production approval, customization method, and selected shipping speed.">
        <form method="POST" action="{{ route('checkout.shipping-method.store') }}" class="grid gap-6">
            @csrf

            <div class="grid gap-4">
                @foreach ($shippingMethods as $method)
                    <label class="cursor-pointer rounded-2xl border border-slate-200 bg-slate-50 p-5 transition hover:border-brand-red hover:bg-white has-[:checked]:border-brand-red has-[:checked]:bg-red-50">
                        <div class="grid gap-4 sm:grid-cols-[auto_1fr_auto] sm:items-start">
                            <input type="radio" name="shipping_method" value="{{ $method['code'] }}" class="mt-1" @checked(($state['shipping_method']['code'] ?? 'standard') === $method['code'])>
                            <div>
                                <h3 class="text-lg font-black text-brand-ink">{{ $method['title'] }}</h3>
                                <p class="mt-1 text-sm font-semibold leading-6 text-slate-600">{{ $method['description'] }}</p>
                                <small class="mt-2 inline-block text-xs font-black uppercase tracking-wide text-slate-500">{{ $method['eta'] }}</small>
                            </div>
                            <strong class="font-display text-2xl font-bold text-brand-navy">{{ $method['display_price'] }}</strong>
                        </div>
                    </label>
                @endforeach
            </div>

            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-bold leading-6 text-amber-900">
                Rush delivery does not replace design proof approval. Custom production starts only after artwork, names, numbers, and sizes are confirmed.
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
                <a class="btn btn-light" href="{{ route('checkout.billing-address') }}">Back</a>
                <button class="btn btn-red" type="submit">Continue to Payment</button>
            </div>
        </form>
    </x-storefront.checkout.panel>
</x-storefront.checkout.shell>
