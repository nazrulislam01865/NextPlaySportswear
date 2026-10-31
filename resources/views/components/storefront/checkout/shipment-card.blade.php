@props(['item' => [], 'line' => [], 'index' => 0, 'count' => 1, 'address' => [], 'info' => [], 'countries' => [], 'callingCodes' => [], 'savedAddresses' => [], 'selectedSavedAddressId' => null, 'together' => false, 'shippingLines' => []])
@php
    $primary = $index === 0;
    $prefix = $primary ? '' : 'shipments['.$index.'].';
    $field = fn (string $name) => $primary ? $name : 'shipments['.$index.']['.$name.']';
    $errorField = fn (string $name) => $primary ? $name : 'shipments.'.$index.'.'.$name;
    $saved = $address['address'] ?? $address;
    $recipient = trim(($saved['first_name'] ?? $info['first_name'] ?? '').' '.($saved['last_name'] ?? $info['last_name'] ?? ''));
    $recipient = trim($recipient);
    $contactName = trim(($info['first_name'] ?? '').' '.($info['last_name'] ?? ''));
    $sameContact = $recipient === $contactName
        && ($saved['email'] ?? $info['email'] ?? '') === ($info['email'] ?? '')
        && ($saved['phone'] ?? $info['phone'] ?? '') === ($info['phone'] ?? '');
    $readyDays = max(0, (int) data_get($line, 'production.minimum_days', 0));
    $minimumDays = max(0, (int) ($line['estimated_minimum_days'] ?? 0));
    $maximumDays = max($minimumDays, (int) ($line['estimated_maximum_days'] ?? 0));
    $shipping = (array) ($line['shipping'] ?? []);
    $selectedCountry = old($errorField('country'), $saved['country'] ?? 'United Kingdom');
@endphp
<section class="np-checkout__card np-checkout__shipment" data-shipment="{{ $index }}">
    <header class="np-checkout__shipment-header">
        <h2>Shipment {{ $index + 1 }} of {{ $count }} – {{ $together ? 'Ship everything together' : ($item['product']['short_title'] ?? $item['product']['title'] ?? 'Custom product') }}</h2>
        <strong>{{ (int) ($item['quantity'] ?? 1) }} pieces</strong>
    </header>
    <div class="np-checkout__shipment-product">
        <img src="{{ $item['product']['image'] ?? asset('images/product-placeholder.svg') }}" alt="{{ $item['product']['title'] ?? 'Product' }}" loading="lazy">
        <div><strong>{{ $item['product']['short_title'] ?? $item['product']['title'] ?? 'Custom product' }}</strong><p>{{ $item['customization']['design_option'] ?? 'Custom design' }}</p><p>{{ $together ? 'One combined delivery for all products' : ((int) ($item['quantity'] ?? 1)).' pieces · '.($item['customization']['fabric'] ?? 'Custom product') }}</p></div>
    </div>
    <div class="np-checkout__shipment-divider"></div>
    <div class="np-checkout__shipment-layout">
        <div class="np-checkout__shipment-fields">
            <div class="np-checkout__shipment-details-head"><h3>Delivery details</h3>
                @unless($primary)<button type="button" class="np-checkout__copy" data-copy-shipment="{{ $index }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/></svg>Copy details from Shipment 1</button>@endunless
            </div>
            @if($primary)
                <label class="np-checkout__checkbox np-checkout__small-checkbox"><input type="checkbox" data-same-contact @checked($sameContact)><span><strong>Use main contact details ({{ trim(($info['first_name'] ?? '').' '.($info['last_name'] ?? '')) }})</strong><small>Main contact will also receive all order updates. Recipient will receive shipment updates for this item.</small></span></label>
            @else
                <label class="np-checkout__checkbox np-checkout__small-checkbox"><input type="checkbox" value="1" name="{{ $field('same_as_first') }}" data-same-shipment @checked((bool) old($errorField('same_as_first'), $address['same_as_first'] ?? false))><span><strong>Same address as Shipment 1</strong><small>Main contact will also receive all order updates. Recipient will receive shipment updates for this item.</small></span></label>
            @endif
            <div class="np-checkout__address-fields" data-address-fields>
                @if($primary)
                    <div class="nps-field np-checkout__saved-address">
                        <label class="nps-label" for="shipment-{{ $index }}-saved">Use a saved address</label>
                        <select id="shipment-{{ $index }}-saved" data-saved-address-select data-nps-dropdown="address">
                            <option value="new">Add a new address</option>
                            @foreach($savedAddresses as $savedAddress)
                                <option value="saved:{{ $savedAddress['id'] }}" @selected((int) ($selectedSavedAddressId ?? 0) === (int) $savedAddress['id'])>{{ $savedAddress['label'] }} — {{ $savedAddress['city'] }} {{ $savedAddress['postal_code'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <script type="application/json" data-saved-address-data>@json($savedAddresses, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
                @endif
                <div class="np-checkout__two-fields">
                    <div class="nps-field"><label class="nps-label" for="shipment-{{ $index }}-recipient">Recipient name <span class="nps-required">*</span></label><input id="shipment-{{ $index }}-recipient" name="{{ $field('recipient_name') }}" type="text" autocomplete="name" data-address-name="recipient_name" value="{{ old($errorField('recipient_name'), $recipient) }}" required aria-invalid="{{ $errors->has($errorField('first_name')) || $errors->has($errorField('last_name')) ? 'true' : 'false' }}">
                        @if($errors->has($errorField('first_name')) || $errors->has($errorField('last_name')))<p class="nps-error" role="alert">Enter the recipient's first and last name.</p>@endif
                    </div>
                    <div class="nps-field"><label class="nps-label" for="shipment-{{ $index }}-email">Email address <span class="nps-required">*</span></label><input id="shipment-{{ $index }}-email" name="{{ $field('email') }}" type="email" data-address-name="email" value="{{ old($errorField('email'), $saved['email'] ?? $info['email'] ?? '') }}" required>@error($errorField('email'))<p class="nps-error" role="alert">{{ $message }}</p>@enderror</div>
                </div>
                <div class="nps-field"><label class="nps-label" for="shipment-{{ $index }}-phone">Phone number <span class="nps-required">*</span></label><x-storefront.checkout.phone-control :name="$field('phone')" :id="'shipment-'.$index.'-phone'" :value="old($errorField('phone'), $saved['phone'] ?? $info['phone'] ?? '')" :calling-codes="$callingCodes" :error-name="$errorField('phone')" data-address-name="phone" /></div>
                <h3 class="np-checkout__address-title">Delivery address</h3>
                <input type="hidden" name="{{ $field('address_choice') }}" value="new" data-address-choice>
                <div class="nps-field"><label class="nps-label" for="shipment-{{ $index }}-company">Company <span class="nps-optional">(optional)</span></label><input id="shipment-{{ $index }}-company" name="{{ $field('company_name') }}" data-address-name="company_name" type="text" autocomplete="organization" value="{{ old($errorField('company_name'), $saved['company_name'] ?? '') }}">@error($errorField('company_name'))<p class="nps-error" role="alert">{{ $message }}</p>@enderror</div>
                <div class="nps-field"><label class="nps-label" for="shipment-{{ $index }}-street">Address line 1 <span class="nps-required">*</span></label><input id="shipment-{{ $index }}-street" name="{{ $field('address_line_1') }}" data-address-name="address_line_1" type="text" autocomplete="address-line1" value="{{ old($errorField('address_line_1'), $saved['address_line_1'] ?? '') }}" required>@error($errorField('address_line_1'))<p class="nps-error" role="alert">{{ $message }}</p>@enderror</div>
                <div class="nps-field"><label class="nps-label" for="shipment-{{ $index }}-street2">Address line 2 <span class="nps-optional">(optional)</span></label><input id="shipment-{{ $index }}-street2" name="{{ $field('address_line_2') }}" data-address-name="address_line_2" type="text" autocomplete="address-line2" value="{{ old($errorField('address_line_2'), $saved['address_line_2'] ?? '') }}">@error($errorField('address_line_2'))<p class="nps-error" role="alert">{{ $message }}</p>@enderror</div>
                <div class="np-checkout__three-fields">
                    <div class="nps-field"><label class="nps-label" for="shipment-{{ $index }}-city">City <span class="nps-required">*</span></label><input id="shipment-{{ $index }}-city" name="{{ $field('city') }}" data-address-name="city" type="text" value="{{ old($errorField('city'), $saved['city'] ?? '') }}" required>@error($errorField('city'))<p class="nps-error" role="alert">{{ $message }}</p>@enderror</div>
                    <div class="nps-field"><label class="nps-label" for="shipment-{{ $index }}-postal">Postcode <span class="nps-required">*</span></label><input id="shipment-{{ $index }}-postal" name="{{ $field('postal_code') }}" data-address-name="postal_code" type="text" value="{{ old($errorField('postal_code'), $saved['postal_code'] ?? '') }}" required>@error($errorField('postal_code'))<p class="nps-error" role="alert">{{ $message }}</p>@enderror</div>
                    <div class="nps-field"><label class="nps-label" for="shipment-{{ $index }}-country">Country <span class="nps-required">*</span></label><select id="shipment-{{ $index }}-country" name="{{ $field('country') }}" data-address-name="country" data-nps-dropdown="country" required><option value="">Select country</option>@foreach($countries as $country)<option value="{{ $country->country_name }}" @selected(old($errorField('country'), $saved['country'] ?? 'United Kingdom') === $country->country_name)>{{ $country->country_name }}</option>@endforeach</select>@error($errorField('country'))<p class="nps-error" role="alert">{{ $message }}</p>@enderror</div>
                </div>
                <input type="hidden" name="{{ $field('state') }}" data-address-name="state" value="{{ old($errorField('state'), $saved['state'] ?? '') }}">

                @if($primary && auth()->check())
                    <label class="np-checkout__checkbox np-checkout__save-address"><input type="checkbox" name="save_to_account" value="1" @checked((bool) old('save_to_account', false))><span><strong>Save this address to my account</strong></span></label>
                @endif
            </div>
        </div>
        <aside class="np-checkout__shipment-shipping">
            <h3>Shipping method</h3>
            @if($together && count($shippingLines) > 0)
                <div class="np-checkout__configured-methods" aria-label="Shipping configured for each product">
                    @foreach($shippingLines as $shippingLine)
                        @php $itemShipping = (array) ($shippingLine['shipping'] ?? []); @endphp
                        <div class="np-checkout__shipping-selected"><span class="np-checkout__radio-dot" aria-hidden="true"></span><span><strong>{{ $itemShipping['label'] ?? 'Configured shipping' }}</strong><small>{{ $shippingLine['product'] ?? 'Product' }}</small></span><b>${{ number_format((float) ($itemShipping['amount'] ?? 0), 2) }}</b></div>
                    @endforeach
                </div>
            @else
                <div class="np-checkout__shipping-selected"><span class="np-checkout__radio-dot" aria-hidden="true"></span><strong>{{ $shipping['label'] ?? 'Configured shipping' }}</strong><b>{{ $shipping['display_amount'] ?? '$'.number_format((float) ($shipping['amount'] ?? 0), 2) }}</b></div>
            @endif
            <div class="np-checkout__shipment-date"><small>Estimated dispatch</small><strong>{{ now()->addWeekdays($readyDays)->format('j M Y') }}</strong><small>Estimated arrival</small><strong>{{ now()->addWeekdays($minimumDays)->format('j') }}–{{ now()->addWeekdays($maximumDays)->format('j M Y') }}</strong></div>
            <div class="nps-field"><label class="nps-label" for="shipment-{{ $index }}-notes">Delivery notes <span class="nps-optional">(optional)</span></label><textarea id="shipment-{{ $index }}-notes" name="{{ $field('delivery_instruction') }}" rows="2" placeholder="E.g. gate code, preferred delivery time...">{{ old($errorField('delivery_instruction'), $address['shipping_notes'] ?? '') }}</textarea>@error($errorField('delivery_instruction'))<p class="nps-error" role="alert">{{ $message }}</p>@enderror</div>
        </aside>
    </div>
</section>
