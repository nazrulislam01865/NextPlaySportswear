@php
    $info = $state['information'] ?? [];
    $name = trim(($info['first_name'] ?? '').' '.($info['last_name'] ?? ''));
    if ($name === '' && auth()->user()) {
        $name = (string) auth()->user()->name;
    }
    $nameParts = preg_split('/\s+/', trim($name), 2);
    $currentPhone = old('phone', $info['phone'] ?? auth()->user()?->phone ?? '');
    $callingCodes = \App\Models\CountryCallingCode::query()->active()->ordered()->get(['iso_code', 'country_name', 'dial_code']);
    $plan = old('shipment_plan', $info['shipment_plan'] ?? 'separate');
    $lines = $summary['fulfillment_lines'] ?? [];
    $latestProductionDays = collect($lines)->max(fn ($line) => (int) data_get($line, 'production.minimum_days', 0));
@endphp
<x-storefront.checkout.shell :seo="$seo" :steps="$steps" :current-step="$currentStep" :summary="$summary">
    <form class="nps-form np-checkout__form np-checkout__stage-form" data-single-submit method="POST" action="{{ route('checkout.information.store') }}" novalidate x-data="{ plan: @js($plan) }">
        @csrf
        <input type="hidden" name="contact_choice" value="new">
        <x-storefront.checkout.panel title="Main order contact" description="Receives all order updates and payment confirmation.">
            <div class="nps-grid np-checkout__contact-grid">
                <x-storefront.checkout.form-field label="First name" name="first_name" placeholder="First name" autocomplete="given-name" :value="$info['first_name'] ?? ($nameParts[0] ?? '')" required />
                <x-storefront.checkout.form-field label="Last name" name="last_name" placeholder="Last name" autocomplete="family-name" :value="$info['last_name'] ?? ($nameParts[1] ?? '')" required />
                <x-storefront.checkout.form-field label="Email address" name="email" type="email" autocomplete="email" :value="$info['email'] ?? auth()->user()?->email" full required />
                <div class="nps-field nps-full">
                    <label class="nps-label" for="checkout-phone-local">Phone number <span class="nps-required">*</span></label>
                    <x-storefront.checkout.phone-control name="phone" id="checkout-phone-local" :value="$currentPhone" :calling-codes="$callingCodes" />
                </div>
            </div>
        </x-storefront.checkout.panel>

        <x-storefront.checkout.panel title="How would you like your order delivered?">
            <div class="np-checkout__plan-options" role="radiogroup" aria-label="Delivery plan">
                <label class="np-checkout__plan" :class="{ 'is-selected': plan === 'separate' }">
                    <input type="radio" name="shipment_plan" value="separate" x-model="plan">
                    <span class="np-checkout__plan-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 7h12v10H2zM14 10h4l4 4v3h-8z"/><circle cx="6" cy="19" r="2"/><circle cx="18" cy="19" r="2"/></svg></span>
                    <span><strong>Ship separately as products are ready</strong><small>Each product can have its own recipient, address and delivery method.</small></span>
                </label>
                <label class="np-checkout__plan" :class="{ 'is-selected': plan === 'together' }">
                    <input type="radio" name="shipment_plan" value="together" x-model="plan">
                    <span class="np-checkout__plan-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m4 7 8-4 8 4v10l-8 4-8-4V7zm0 0 8 4 8-4m-8 4v10"/></svg></span>
                    <span><strong>Ship everything together</strong><small>Same address, one dispatch after all products are ready.</small></span>
                </label>
            </div>
            @error('shipment_plan')<p class="nps-error" role="alert">{{ $message }}</p>@enderror
            <div class="np-checkout__tiny-info"><span aria-hidden="true">ⓘ</span> Separate shipments can go to the same address.</div>
            <h3 class="np-checkout__list-title">Your products and estimated readiness dates</h3>
            <div class="np-checkout__readiness-list">
                @foreach(($summary['items'] ?? []) as $index => $item)
                    @php
                        $line = $lines[$index] ?? [];
                        $readyDays = max(0, (int) data_get($line, 'production.minimum_days', 0));
                        $travelMin = max(0, (int) ($line['estimated_minimum_days'] ?? 0));
                        $travelMax = max($travelMin, (int) ($line['estimated_maximum_days'] ?? 0));
                    @endphp
                    <div class="np-checkout__readiness-row">
                        <img src="{{ $item['product']['image'] ?? asset('images/product-placeholder.svg') }}" alt="{{ $item['product']['title'] ?? 'Product' }}" loading="lazy">
                        <div class="np-checkout__readiness-product">
                            <strong>{{ $item['product']['short_title'] ?? $item['product']['title'] ?? 'Custom product' }}</strong>
                            <p>{{ (int) ($item['quantity'] ?? 1) }} pieces · {{ $item['customization']['design_option'] ?? 'Custom design' }}</p>
                        </div>
                        <div class="np-checkout__readiness-date">
                            <div class="np-checkout__date-caption"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 2v6m10-6v6M3 10h18"/></svg>Estimated ready for dispatch</div>
                            <strong>{{ now()->addWeekdays($readyDays)->format('j M Y') }}</strong>
                            <p>Estimated arrival: {{ now()->addWeekdays($travelMin)->format('j M') }}–{{ now()->addWeekdays($travelMax)->format('j M Y') }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="np-checkout__info-line"><span aria-hidden="true">ⓘ</span><div><strong x-text="plan === 'together' ? 'If you choose to ship everything together' : 'Each product can dispatch independently'"></strong><p x-show="plan === 'together'">Dispatch waits for the latest-ready product (estimated {{ now()->addWeekdays((int) $latestProductionDays)->format('j M Y') }}). Shipping is calculated from your configured products.</p><p x-show="plan === 'separate'">Each item has its own shipment details and estimated delivery. This is one parent order with a single payment.</p></div></div>
            <div class="np-checkout__form-end"><button type="submit" class="np-checkout__button nps-btn-accent">Continue to Delivery Details <span aria-hidden="true">→</span></button></div>
        </x-storefront.checkout.panel>
    </form>
</x-storefront.checkout.shell>
