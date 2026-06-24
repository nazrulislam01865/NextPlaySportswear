<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_330px] lg:items-start">
    <form method="POST" action="{{ route('account.orders.pay.store', $order) }}" class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-card md:p-7">
        @csrf
        <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">

        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-blue-800">
            <b>No card numbers are collected by NextPlay.</b> Selecting card, PayPal, or a saved token creates a payment attempt for the configured provider. The order is marked paid only after verified provider confirmation or an authorized administrator update.
        </div>

        @if($retryMode && $order->payments->first()?->failure_message)
            <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                <b>Latest payment issue:</b> {{ $order->payments->first()->failure_message }}
            </div>
        @endif

        <fieldset class="mt-6">
            <legend class="text-xl font-black">Choose Payment Method</legend>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                @foreach([
                    'card' => ['Credit / Debit Card', 'Provider-hosted secure checkout'],
                    'paypal' => ['PayPal', 'Continue through PayPal'],
                    'invoice' => ['Request Invoice', 'For approved team, school, or bulk orders'],
                ] as $value => $copy)
                    <label class="order-choice">
                        <input type="radio" name="payment_method" value="{{ $value }}" class="peer sr-only" @checked(old('payment_method', 'card') === $value)>
                        <span class="block h-full rounded-2xl border border-slate-200 p-5 transition peer-checked:border-brand-blue peer-checked:bg-blue-50 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-blue">
                            <b class="block text-brand-ink">{{ $copy[0] }}</b>
                            <span class="mt-1 block text-sm text-slate-500">{{ $copy[1] }}</span>
                        </span>
                    </label>
                @endforeach

                @if($savedPaymentMethods->isNotEmpty())
                    <label class="order-choice md:col-span-2">
                        <input type="radio" name="payment_method" value="saved_card" class="peer sr-only" @checked(old('payment_method') === 'saved_card')>
                        <span class="block rounded-2xl border border-slate-200 p-5 transition peer-checked:border-brand-blue peer-checked:bg-blue-50 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-blue">
                            <b class="block text-brand-ink">Saved payment method</b>
                            <span class="mt-1 block text-sm text-slate-500">Use a tokenized payment method already connected to your account.</span>
                            <select class="admin-input mt-4 bg-white" name="saved_payment_method_id" aria-label="Saved payment method">
                                @foreach($savedPaymentMethods as $method)
                                    <option value="{{ $method->id }}" @selected((string) old('saved_payment_method_id') === (string) $method->id)>
                                        {{ strtoupper($method->brand) }} •••• {{ $method->last_four }} · expires {{ $method->expiryLabel() }}
                                    </option>
                                @endforeach
                            </select>
                        </span>
                    </label>
                @endif
            </div>
        </fieldset>

        <button class="btn btn-red mt-6 w-full sm:w-auto" type="submit">
            {{ $retryMode ? 'Retry Secure Payment' : 'Continue to Secure Provider' }}
        </button>
    </form>

    <x-storefront.account.orders.summary :order="$order" />
</div>
