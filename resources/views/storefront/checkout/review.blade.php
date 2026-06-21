<x-storefront.checkout.shell :seo="$seo" :steps="$steps" :current-step="$currentStep" title="Order Review" description="Review contact, shipping, billing, payment, customization, and total before placing the order." :summary="$summary">
    <x-storefront.checkout.panel title="Review Your Order" description="Check all details carefully before placing your custom sportswear order.">
        <form method="POST" action="{{ route('checkout.review.store') }}" class="grid gap-5">
            @csrf

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
                    <div class="flex items-center justify-between gap-4"><h3 class="font-black text-brand-ink">Shipping Method</h3><a class="text-sm font-black text-brand-red" href="{{ route('checkout.shipping-method') }}">Edit</a></div>
                    <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">{{ $summary['shipping_method']['title'] ?? 'Standard Shipping' }} · {{ $summary['shipping_method']['eta'] ?? '' }} · {{ $summary['shipping_method']['display_price'] ?? '$0.00' }}</p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="flex items-center justify-between gap-4"><h3 class="font-black text-brand-ink">Payment Method</h3><a class="text-sm font-black text-brand-red" href="{{ route('checkout.payment-method') }}">Edit</a></div>
                    <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">{{ $summary['payment_method']['label'] ?? 'Credit / Debit Card' }}</p>
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
                <input type="checkbox" name="confirm_details" value="1" class="mt-1">
                <span><strong>I confirm that names, numbers, sizes, logo placement, shipping address, and order notes are correct.</strong><br><span class="font-semibold">Custom products may not be refundable once production begins.</span></span>
            </label>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
                <a class="btn btn-light" href="{{ route('checkout.payment-method') }}">Back</a>
                <button class="btn btn-red" type="submit">Continue to Place Order</button>
            </div>
        </form>
    </x-storefront.checkout.panel>
</x-storefront.checkout.shell>
