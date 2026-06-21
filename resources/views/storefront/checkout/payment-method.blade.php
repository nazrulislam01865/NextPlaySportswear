<x-storefront.checkout.shell :seo="$seo" :steps="$steps" :current-step="$currentStep" title="Payment Method" description="Choose a secure payment method for online order payment or bulk quote invoice handling." :summary="$summary">
    <x-storefront.checkout.panel title="Payment Method" description="Choose how you want to pay. Card processing must happen through a PCI-compliant provider, not through stored raw card fields.">
        <form method="POST" action="{{ route('checkout.payment-method.store') }}" class="grid gap-6">
            @csrf

            @if (count($savedPaymentMethods) > 0)
                <div>
                    <h3 class="text-sm font-black uppercase tracking-[.16em] text-brand-red">Saved payment methods</h3>
                    <div class="mt-3 grid gap-4 md:grid-cols-2">
                        @foreach ($savedPaymentMethods as $method)
                            <label class="cursor-pointer rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-brand-red hover:bg-white has-[:checked]:border-brand-red has-[:checked]:bg-red-50">
                                <div class="flex items-start gap-3">
                                    <input type="radio" name="payment_method" value="saved_card:{{ $method['id'] }}" class="mt-1" @checked(($state['payment_method']['saved_payment_method_id'] ?? null) === $method['id'])>
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <strong class="text-brand-ink">{{ $method['label'] }}</strong>
                                            @if ($method['is_default'])
                                                <span class="rounded-full bg-brand-red px-2 py-0.5 text-[10px] font-black uppercase text-white">Default</span>
                                            @endif
                                        </div>
                                        <p class="mt-2 text-sm font-semibold text-slate-600">{{ $method['brand'] }} ending in {{ $method['last_four'] }} · Expires {{ $method['expiry'] }}</p>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="grid gap-4">
                @foreach ($paymentOptions as $code => $option)
                    @continue($code === 'saved_card' && count($savedPaymentMethods) === 0)
                    @continue($code === 'saved_card')
                    <label class="cursor-pointer rounded-2xl border border-slate-200 bg-slate-50 p-5 transition hover:border-brand-red hover:bg-white has-[:checked]:border-brand-red has-[:checked]:bg-red-50">
                        <div class="grid gap-4 sm:grid-cols-[auto_1fr_auto] sm:items-start">
                            <input type="radio" name="payment_method" value="{{ $code }}" class="mt-1" @checked(($state['payment_method']['method'] ?? 'card') === $code)>
                            <div>
                                <h3 class="text-lg font-black text-brand-ink">{{ $option['title'] }}</h3>
                                <p class="mt-1 text-sm font-semibold leading-6 text-slate-600">{{ $option['description'] }}</p>
                            </div>
                            <strong class="rounded-full bg-white px-3 py-1 text-xs font-black uppercase tracking-wide text-brand-navy">{{ $option['badge'] }}</strong>
                        </div>
                    </label>
                @endforeach
            </div>

            <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5">
                <h3 class="text-sm font-black uppercase tracking-[.16em] text-brand-navy">Secure card processing</h3>
                <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">This checkout design is ready for Stripe/PayPal hosted fields. The application does not accept or store raw card numbers or CVV. After order review, Laravel should create a pending order and redirect to the secure provider checkout.</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-xl bg-white p-3 text-center text-xs font-black text-slate-500">Visa</div>
                    <div class="rounded-xl bg-white p-3 text-center text-xs font-black text-slate-500">Mastercard</div>
                    <div class="rounded-xl bg-white p-3 text-center text-xs font-black text-slate-500">PayPal</div>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
                <a class="btn btn-light" href="{{ route('checkout.shipping-method') }}">Back</a>
                <button class="btn btn-red" type="submit">Review Order</button>
            </div>
        </form>
    </x-storefront.checkout.panel>
</x-storefront.checkout.shell>
