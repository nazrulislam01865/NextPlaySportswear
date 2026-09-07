<x-layouts.storefront :seo="$seo">
    <x-storefront.account.shell
        title="Saved Payment Methods"
        subtitle="Payment credentials are handled by the payment provider, never by the NextPlay application server."
        :account="$account"
        :navigation="$navigation"
    >
        <div class="space-y-6">
            <div class="grid gap-5 md:grid-cols-3">
                <x-storefront.account.stat-card label="Saved References" :value="$wallet['total']" description="Provider-tokenized methods only" />
                <x-storefront.account.stat-card label="Default Method" :value="$wallet['default'] ? 'Set' : 'Not Set'" description="Used first when supported" />
                <x-storefront.account.stat-card label="Card Data" value="Provider Hosted" description="PAN and CVV never enter this app" />
            </div>

            <section class="rounded-[30px] border border-slate-200 bg-white p-5 shadow-card md:p-7">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.24em] text-brand-red">Secure payment vaulting</p>
                        <h2 class="mt-2 text-2xl font-black text-brand-ink">Raw card entry has been disabled</h2>
                        <p class="mt-2 max-w-3xl text-sm font-semibold leading-6 text-slate-600">
                            For PCI safety, this application no longer accepts card number, expiry, or CVV in a Laravel form. During checkout, card details are entered only on Stripe-hosted Checkout. A future saved-card flow can use Stripe Setup mode and store only Stripe references plus masked display metadata.
                        </p>
                    </div>
                    <span class="rounded-full px-4 py-2 text-xs font-black uppercase tracking-wide {{ $stripeReady ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                        {{ $stripeReady ? 'Stripe ready' : 'Stripe setup required' }}
                    </span>
                </div>
            </section>

            <section class="rounded-[30px] border border-slate-200 bg-white shadow-card">
                <div class="border-b border-slate-200 p-5 md:p-7">
                    <p class="text-xs font-black uppercase tracking-[0.24em] text-brand-red">Stored references</p>
                    <h2 class="mt-2 text-2xl font-black text-brand-ink">Existing tokenized payment methods</h2>
                    <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">Only keep references that were created by a real provider vault. Legacy placeholder references can be removed here.</p>
                </div>

                @if ($wallet['paymentMethods']->isNotEmpty())
                    <div class="grid gap-5 p-5 md:p-7 xl:grid-cols-2">
                        @foreach ($wallet['paymentMethods'] as $paymentMethod)
                            <x-storefront.account.payment-method-card :payment-method="$paymentMethod" />
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center text-sm font-semibold text-slate-600">
                        No provider-tokenized payment method is saved yet. You can still pay securely through Stripe Checkout when placing an order.
                    </div>
                @endif
            </section>
        </div>
    </x-storefront.account.shell>
</x-layouts.storefront>
