                <x-storefront.bulk-quote.section
                    number="2"
                    title="Contact Information"
                    description="Let us know who you are and how to reach you."
                    icon="user"
                >
                    <div class="bulk-quote-grid nps-grid bulk-quote-grid-two">
                        <div class="bulk-quote-field nps-field">
                            <label for="bulk-full-name">Full name <span class="bulk-quote-req nps-required">*</span></label>
                            <input
                                id="bulk-full-name"
                                name="full_name"
                                value="{{ old('full_name', auth()->user()?->name) }}"
                                required
                                maxlength="120"
                                autocomplete="name"
                                placeholder="Your full name"
                                aria-invalid="{{ $errors->has('full_name') ? 'true' : 'false' }}"
                            >
                            <x-storefront.bulk-quote.field-error name="full_name" />
                        </div>

                        <div class="bulk-quote-field nps-field">
                            <label for="bulk-organization">Company / Team <span class="bulk-quote-req nps-required">*</span></label>
                            <input
                                id="bulk-organization"
                                name="organization"
                                value="{{ old('organization') }}"
                                required
                                maxlength="160"
                                placeholder="Organization or team name"
                                aria-invalid="{{ $errors->has('organization') ? 'true' : 'false' }}"
                            >
                            <x-storefront.bulk-quote.field-error name="organization" />
                        </div>

                        <div class="bulk-quote-field nps-field">
                            <label for="bulk-email">Email <span class="bulk-quote-req nps-required">*</span></label>
                            <input
                                id="bulk-email"
                                type="email"
                                name="email"
                                value="{{ old('email', auth()->user()?->email) }}"
                                required
                                maxlength="190"
                                autocomplete="email"
                                placeholder="you@example.com"
                                aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                            >
                            <x-storefront.bulk-quote.field-error name="email" />
                        </div>

                        <div class="bulk-quote-field nps-field">
                            <label for="bulk-phone">Phone / WhatsApp <span class="bulk-quote-req nps-required">*</span></label>
                            @php
                                $ukCallingCode = $countryCallingCodes->first(fn ($code) => in_array(strtoupper((string) $code->iso_code), ['GB', 'UK'], true));
                                $selectedCallingCodeId = (string) old('phone_country_code_id', $ukCallingCode?->id ?? $countryCallingCodes->first()?->id);
                                $phoneHasError = $errors->has('phone') || $errors->has('phone_country_code_id');
                            @endphp
                            <div class="bulk-quote-phone-control {{ $phoneHasError ? 'is-invalid' : '' }}">
                                <select
                                    id="bulk-phone-country-code"
                                    name="phone_country_code_id"
                                    class="bulk-quote-phone-code-select"
                                    required
                                    aria-label="Country calling code"
                                    aria-invalid="{{ $errors->has('phone_country_code_id') ? 'true' : 'false' }}"
                                >
                                    @foreach($countryCallingCodes as $callingCode)
                                        <option value="{{ $callingCode->id }}" data-country-name="{{ $callingCode->country_name }}" @selected($selectedCallingCodeId === (string) $callingCode->id)>
                                            {{ $callingCode->flagEmoji() }} {{ $callingCode->dial_code }}
                                        </option>
                                    @endforeach
                                </select>
                                <input
                                    id="bulk-phone"
                                    name="phone"
                                    value="{{ old('phone') }}"
                                    required
                                    maxlength="40"
                                    autocomplete="tel-national"
                                    placeholder="7123 456789"
                                    aria-invalid="{{ $errors->has('phone') ? 'true' : 'false' }}"
                                >
                            </div>
                            <x-storefront.bulk-quote.field-error name="phone_country_code_id" />
                            <x-storefront.bulk-quote.field-error name="phone" />
                        </div>
                    </div>
                </x-storefront.bulk-quote.section>

                <x-storefront.bulk-quote.section
                    number="3"
                    title="Delivery Requirements"
                    description="Where should we ship and when do you need it?"
                    icon="truck"
                >
                    <div class="bulk-quote-grid nps-grid bulk-quote-shipping-grid">
                        <div class="bulk-quote-field nps-field bulk-quote-full">
                            <label for="bulk-shipping-address">Shipping address <span class="bulk-quote-req nps-required">*</span></label>
                            <input
                                id="bulk-shipping-address"
                                name="shipping_address"
                                value="{{ old('shipping_address') }}"
                                required
                                maxlength="500"
                                placeholder="Street address, city, state/province, postcode, country"
                                aria-invalid="{{ $errors->has('shipping_address') ? 'true' : 'false' }}"
                            >
                            <x-storefront.bulk-quote.field-error name="shipping_address" />
                        </div>

                        <x-storefront.address-geography variant="bulk" country-field="country" region-field="state_province" region-label="County / State" :country-value="'United Kingdom'" />

                        <div class="bulk-quote-field nps-field bulk-quote-quarter">
                            <label for="bulk-postal-code">Postcode <span class="bulk-quote-optional">(optional)</span></label>
                            <input id="bulk-postal-code" name="postal_code" value="{{ old('postal_code') }}" maxlength="40" placeholder="e.g. SW1A 1AA" aria-invalid="{{ $errors->has('postal_code') ? 'true' : 'false' }}">
                            <x-storefront.bulk-quote.field-error name="postal_code" />
                        </div>

                        <div class="bulk-quote-field nps-field bulk-quote-quarter bulk-quote-quarter-wide">
                            <label for="bulk-shipping-method">Preferred shipping method <span class="bulk-quote-optional">(optional)</span></label>
                            <select id="bulk-shipping-method" name="preferred_shipping_method" aria-invalid="{{ $errors->has('preferred_shipping_method') ? 'true' : 'false' }}">
                                <option value="">Select shipping method</option>
                                @foreach([
                                    'standard' => 'Standard shipping',
                                    'express' => 'Express shipping',
                                    'rush' => 'Rush delivery if available',
                                    'recommend' => 'Recommend best option',
                                ] as $value => $label)
                                    <option value="{{ $value }}" @if(! empty($shippingOptionIcons[$value])) data-icon-url="{{ $shippingOptionIcons[$value] }}" @endif @selected(old('preferred_shipping_method') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-storefront.bulk-quote.field-error name="preferred_shipping_method" />
                        </div>

                        <div class="bulk-quote-field nps-field bulk-quote-quarter">
                            <label for="bulk-needed-by">Needed by / Delivery date <span class="bulk-quote-req nps-required">*</span></label>
                            <div class="bulk-quote-date-control">
                                <x-storefront.bulk-quote.icon name="calendar" :size="17" />
                                <input id="bulk-needed-by" type="date" lang="en-GB" name="needed_by" value="{{ old('needed_by') }}" required aria-invalid="{{ $errors->has('needed_by') ? 'true' : 'false' }}">
                            </div>
                            <x-storefront.bulk-quote.field-error name="needed_by" />
                        </div>

                        <div class="bulk-quote-field nps-field bulk-quote-quarter">
                            <label for="bulk-event-date">Event date <span class="bulk-quote-optional">(optional)</span></label>
                            <div class="bulk-quote-date-control">
                                <x-storefront.bulk-quote.icon name="calendar" :size="17" />
                                <input id="bulk-event-date" type="date" lang="en-GB" name="event_date" value="{{ old('event_date') }}" aria-invalid="{{ $errors->has('event_date') ? 'true' : 'false' }}">
                            </div>
                            <x-storefront.bulk-quote.field-error name="event_date" />
                        </div>
                    </div>
                </x-storefront.bulk-quote.section>

                @php
                    // CAPTCHA remains server-validated in non-local environments.
                    // Do not render external Google CAPTCHA scripts or widget on local.
                    // Keep the same server-side requirement on non-local environments.
                    $recaptchaRequired = ! app()->environment('local');
                    $recaptchaConfigured = filled(config('services.recaptcha.site_key'))
                        && filled(config('services.recaptcha.secret_key'));
                @endphp
                @if($recaptchaRequired)
                    <section class="bulk-quote-security-card" aria-labelledby="bulk-quote-security-title">
                        <h2 id="bulk-quote-security-title">Security Verification <span class="bulk-quote-req nps-required">*</span></h2>
                        @if($recaptchaConfigured)
                            <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
                            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                        @else
                            <p class="bulk-quote-field-error nps-error" role="alert">CAPTCHA is not configured. Quote requests are temporarily unavailable.</p>
                        @endif
                        <x-storefront.bulk-quote.field-error name="g-recaptcha-response" />
                    </section>
                @endif
                <div class="bulk-quote-submit-row nps-actions">
                    <p class="bulk-quote-privacy">Your details are used to prepare and manage your quotation.</p>
                    <button class="btn btn-primary btn-sm bulk-quote-submit nps-btn npq-quote-cta" type="submit" @disabled($recaptchaRequired && ! $recaptchaConfigured)>
                        <span>Submit Quote Request</span>
                        <x-storefront.bulk-quote.icon name="arrow-right" :size="16" />
                    </button>
                </div>
