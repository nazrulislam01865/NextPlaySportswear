<x-storefront.checkout.shell :seo="$seo" :steps="$steps" :current-step="$currentStep" title="Place Order" description="Final secure order placement action with confirmation, protection, and processing state." :summary="$summary">
    <x-storefront.checkout.panel title="Place Your Secure Order" description="Click once to submit your order securely. After submission, payment will be handled by the selected provider or invoice workflow.">
        <div class="mx-auto max-w-2xl text-center">
            <div class="mx-auto grid h-20 w-20 place-items-center rounded-full bg-brand-navy text-4xl text-white shadow-hero">🔒</div>
            <p class="mt-6 text-xs font-black uppercase tracking-[.22em] text-brand-red">Final step</p>
            <h2 class="mt-2 font-display text-4xl font-bold uppercase text-brand-ink">Ready to Submit</h2>
            <p class="mt-3 text-sm font-semibold leading-7 text-slate-600">The final total below is calculated on the Laravel backend. The next production phase can connect this handoff to Stripe, PayPal, or invoice approval.</p>

            <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5 text-left">
                <div class="flex items-center justify-between gap-4">
                    <h3 class="font-black text-brand-ink">Final Amount</h3>
                    <a href="{{ route('checkout.review') }}" class="text-sm font-black text-brand-red">Review Again</a>
                </div>
                <p class="mt-2 text-sm font-semibold leading-6 text-slate-600"><strong class="text-brand-ink">${{ number_format($summary['total'] ?? 0, 2) }}</strong> including selected shipping method. Tax may vary later if tax integration rules change.</p>
            </div>

            <form method="POST" action="{{ route('checkout.place-order.submit') }}" class="mt-6 grid gap-5" x-data="{processing:false}" @submit="processing=true">
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ $orderIdempotencyKey }}">
                <label class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-left text-sm font-bold leading-6 text-amber-900">
                    <input type="checkbox" name="terms" value="1" class="mt-1">
                    <span><strong>I agree to the Terms, Privacy Policy, and Custom Product Production Policy.</strong><br><span class="font-semibold">This action uses double-submit protection through an idempotency key.</span></span>
                </label>

                <button class="btn btn-red mx-auto w-full max-w-sm" type="submit" x-bind:disabled="processing" x-bind:class="processing ? 'opacity-70 pointer-events-none' : ''">
                    <span x-show="!processing">Place Secure Order</span>
                    <span x-cloak x-show="processing">Processing Securely...</span>
                </button>
            </form>

            <div class="mt-6 rounded-2xl border border-blue-100 bg-blue-50 p-4 text-left text-sm font-bold leading-6 text-brand-navy">
                After success, the system redirects to order confirmation. If payment fails in the payment integration phase, it should redirect to a payment failed page with retry options.
            </div>
        </div>
    </x-storefront.checkout.panel>
</x-storefront.checkout.shell>
