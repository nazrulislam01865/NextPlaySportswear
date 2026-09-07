@php
    $defaultMethod = collect($paymentOptions)->firstWhere('is_default', true) ?: collect($paymentOptions)->first();
    $defaultCode = (string) ($defaultMethod['code'] ?? '');
@endphp

<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_330px] lg:items-start">
    <form method="POST" action="{{ route('account.orders.pay.store', $order) }}" class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-card md:p-7">
        @csrf
        <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">

        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-blue-800">
            <b>No card numbers are collected by NextPlay.</b> Online payments redirect to the registered provider. The order becomes paid only after a signed provider event passes server-side amount and currency verification.
        </div>

        @if($retryMode && $order->payments->first()?->failure_message)
            <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                <b>Latest payment issue:</b> {{ $order->payments->first()->failure_message }}
            </div>
        @endif

        <fieldset class="mt-6">
            <legend class="text-xl font-black">Choose Payment Method</legend>
            @error('payment_method')<p class="mt-2 text-sm font-bold text-brand-red">{{ $message }}</p>@enderror

            @if(empty($paymentOptions))
                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold leading-6 text-amber-800">
                    No payment gateway is currently configured for this order. Ask an administrator to configure Stripe or enable a manual method.
                </div>
            @else
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    @foreach($paymentOptions as $option)
                        <label class="order-choice">
                            <input type="radio" name="payment_method" value="{{ $option['code'] }}" class="peer sr-only" @checked(old('payment_method', $defaultCode) === $option['code'])>
                            <span class="block h-full rounded-2xl border border-slate-200 p-5 transition peer-checked:border-brand-blue peer-checked:bg-blue-50 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-blue">
                                <b class="block text-brand-ink">{{ $option['label'] }}</b>
                                <span class="mt-1 block text-sm text-slate-500">{{ $option['description'] }}</span>
                                @if($option['requires_provider_redirect'])
                                    <span class="mt-2 inline-block text-xs font-black uppercase tracking-wide text-brand-blue">Secure provider redirect</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
            @endif
        </fieldset>

        <button class="btn btn-red mt-6 w-full sm:w-auto" type="submit" @disabled(empty($paymentOptions))>
            {{ $retryMode ? 'Retry Secure Payment' : 'Continue to Secure Provider' }}
        </button>
    </form>

    <x-storefront.account.orders.summary :order="$order" />
</div>
