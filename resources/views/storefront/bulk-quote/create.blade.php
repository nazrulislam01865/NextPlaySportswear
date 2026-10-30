<x-layouts.storefront
    :seo="[
        'title' => 'Request a Bulk Quote | ' . config('storefront.name'),
        'description' => 'Request a bulk quote for team uniforms, school apparel, league orders, event apparel, promotional products, artwork, delivery dates, and shipping details.',
        'schema_type' => 'ContactPage',
    ]"
    :structured-data="$structuredData ?? []"
>
    @php
        $whatsappDigits = preg_replace('/\D+/', '', (string) config('storefront.whatsapp'));
        $whatsappUrl = config('storefront.whatsapp_url') ?: ($whatsappDigits ? 'https://wa.me/'.$whatsappDigits : null);
    @endphp
    <div class="bulk-quote-page">
        <header class="bulk-quote-intro site-container">
            <nav class="bulk-quote-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('bulk-ordering') }}">Bulk Orders</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">Request a Quote</span>
            </nav>
            <div class="bulk-quote-intro-heading">
                <div>
                    <h1>Request a Bulk Quote</h1>
                    <p>Tell us what you need. We'll prepare a quote for your team or business.</p>
                </div>
                <p class="bulk-quote-required-hint">Required fields are marked <span class="bulk-quote-req">*</span></p>
            </div>
        </header>

        <div class="bulk-quote-main site-container" id="quote">
            <div class="bulk-quote-layout">
                <form
                    class="bulk-quote-card"
                    id="bulk-quote-request-form"
                    method="POST"
                    action="{{ route('quote.request.store') }}"
                    enctype="multipart/form-data"
                    novalidate
                >
                    @csrf
                    @if(session('status'))
                        <div class="bulk-quote-alert bulk-quote-alert-success" role="status">{{ session('status') }}</div>
                    @endif
                    @if($errors->any())
                        <div class="bulk-quote-alert bulk-quote-alert-error" role="alert">
                            Please check the highlighted fields and try again.
                        </div>
                    @endif
                    <div class="hidden" aria-hidden="true">
                        <label for="bulk-company">Company</label>
                        <input id="bulk-company" name="company" value="" tabindex="-1" autocomplete="off">
                    </div>

                    <x-storefront.bulk-quote.section
                        number="1"
                        title="Product & Customisation"
                        description="Tell us about the products you need and how you'd like to customise them."
                        icon="box"
                    >
                        {{-- Keep the optional product picker visibly above the general product fields.
                             Its component owns the search and selection UI; never wrap it in a hidden conditional. --}}
                        <div class="bulk-quote-product-general">
                            <x-storefront.bulk-quote.product-information :selected-product="$selectedProduct" />
                            <div class="bulk-quote-grid bulk-quote-grid-two">
                        <div class="bulk-quote-field">
                            <label for="bulk-product-type">Items needed / Product type <span class="bulk-quote-req">*</span></label>
                            <input
                                id="bulk-product-type"
                                name="product_type"
                                value="{{ old('product_type') }}"
                                required
                                maxlength="190"
                                placeholder="e.g., Jerseys, Hoodies, Caps"
                                aria-invalid="{{ $errors->has('product_type') ? 'true' : 'false' }}"
                            >
                            <x-storefront.bulk-quote.field-error name="product_type" />
                        </div>

                        <div class="bulk-quote-field">
                            <label for="bulk-quantity">Estimated quantity <span class="bulk-quote-req">*</span></label>
                            <select id="bulk-quantity" name="estimated_quantity" required aria-invalid="{{ $errors->has('estimated_quantity') ? 'true' : 'false' }}">
                                <option value="">Select quantity</option>
                                @foreach([
                                    '10-49' => '10–49 pieces',
                                    '50-99' => '50–99 pieces',
                                    '100-499' => '100–499 pieces',
                                    '500-999' => '500–999 pieces',
                                    '1000-plus' => '1,000+ pieces',
                                ] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('estimated_quantity') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-storefront.bulk-quote.field-error name="estimated_quantity" />
                        </div>

                        <div class="bulk-quote-field">
                            <label for="bulk-sizes">Sizes needed <span class="bulk-quote-req">*</span></label>
                            <input
                                id="bulk-sizes"
                                name="sizes_needed"
                                value="{{ old('sizes_needed') }}"
                                required
                                maxlength="190"
                                placeholder="e.g., S, M, L, XL, 2XL, or size range"
                                aria-invalid="{{ $errors->has('sizes_needed') ? 'true' : 'false' }}"
                            >
                            <x-storefront.bulk-quote.field-error name="sizes_needed" />
                        </div>

                        <div class="bulk-quote-field">
                            <label for="bulk-budget">Budget range <span class="bulk-quote-optional">Optional</span></label>
                            <select id="bulk-budget" name="budget_range" aria-invalid="{{ $errors->has('budget_range') ? 'true' : 'false' }}">
                                <option value="">Select budget range</option>
                                @foreach($budgetRanges as $budgetRange)
                                    <option value="{{ $budgetRange->value }}" @selected(old('budget_range') === $budgetRange->value)>{{ $budgetRange->label }}</option>
                                @endforeach
                            </select>
                            <x-storefront.bulk-quote.field-error name="budget_range" />
                        </div>

                        <div class="bulk-quote-field bulk-quote-full bulk-quote-customization-details" x-data="{ count: {{ mb_strlen((string) old('artwork_details', '')) }} }">
                            <label for="bulk-artwork-details">Customisation details <span class="bulk-quote-req">*</span></label>
                            <div class="bulk-quote-control-wrap">
                                <textarea
                                    id="bulk-artwork-details"
                                    name="artwork_details"
                                    required
                                    minlength="10"
                                    maxlength="5000"
                                    placeholder="Tell us about colours, artwork, names, numbers or placement..."
                                    aria-invalid="{{ $errors->has('artwork_details') ? 'true' : 'false' }}"
                                    @input="count = $event.target.value.length"
                                >{{ old('artwork_details') }}</textarea>
                                <div class="bulk-quote-counter" aria-hidden="true"><span x-text="count">{{ mb_strlen((string) old('artwork_details', '')) }}</span>/5000</div>
                            </div>
                            <x-storefront.bulk-quote.field-error name="artwork_details" />
                        </div>

                        <div class="bulk-quote-field bulk-quote-full bulk-quote-customization-choices">
                            <label>Customisation options</label>
                            <p class="bulk-quote-field-note">Select all that apply</p>
                            <div class="bulk-quote-custom-options">
                                <x-storefront.bulk-quote.customization-option value="logo" label="Logo" icon="logo" />
                                <x-storefront.bulk-quote.customization-option value="names-numbers" label="Names & numbers" icon="numbers" />
                                <x-storefront.bulk-quote.customization-option value="embroidery" label="Embroidery" icon="embroidery" />
                                <x-storefront.bulk-quote.customization-option value="sublimation" label="Sublimation" icon="drop" />
                                <x-storefront.bulk-quote.customization-option value="full-custom-design" label="Full custom design" icon="custom" />
                            </div>
                            <x-storefront.bulk-quote.field-error name="customization_types" />
                            <x-storefront.bulk-quote.field-error name="customization_types.*" />
                        </div>
                            </div>
                        </div>
                    </x-storefront.bulk-quote.section>

                <x-storefront.bulk-quote.section
                    number="2"
                    title="Contact Information"
                    description="Let us know who you are and how to reach you."
                    icon="user"
                >
                    <div class="bulk-quote-grid bulk-quote-grid-two">
                        <div class="bulk-quote-field">
                            <label for="bulk-full-name">Full name <span class="bulk-quote-req">*</span></label>
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

                        <div class="bulk-quote-field">
                            <label for="bulk-organization">Company / Team <span class="bulk-quote-req">*</span></label>
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

                        <div class="bulk-quote-field">
                            <label for="bulk-email">Email <span class="bulk-quote-req">*</span></label>
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

                        <div class="bulk-quote-field">
                            <label for="bulk-phone">Phone / WhatsApp <span class="bulk-quote-req">*</span></label>
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
                    <div class="bulk-quote-grid bulk-quote-shipping-grid">
                        <div class="bulk-quote-field bulk-quote-full">
                            <label for="bulk-shipping-address">Shipping address <span class="bulk-quote-req">*</span></label>
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

                        <div class="bulk-quote-field bulk-quote-quarter">
                            <label for="bulk-postal-code">Postcode <span class="bulk-quote-req" aria-hidden="true">*</span></label>
                            <input id="bulk-postal-code" name="postal_code" value="{{ old('postal_code') }}" maxlength="40" placeholder="e.g. SW1A 1AA" aria-invalid="{{ $errors->has('postal_code') ? 'true' : 'false' }}">
                            <x-storefront.bulk-quote.field-error name="postal_code" />
                        </div>

                        <div class="bulk-quote-field bulk-quote-quarter bulk-quote-quarter-wide">
                            <label for="bulk-shipping-method">Preferred shipping method</label>
                            <select id="bulk-shipping-method" name="preferred_shipping_method" aria-invalid="{{ $errors->has('preferred_shipping_method') ? 'true' : 'false' }}">
                                <option value="">Select shipping method</option>
                                @foreach([
                                    'standard' => 'Standard shipping',
                                    'express' => 'Express shipping',
                                    'rush' => 'Rush delivery if available',
                                    'recommend' => 'Recommend best option',
                                ] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('preferred_shipping_method') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-storefront.bulk-quote.field-error name="preferred_shipping_method" />
                        </div>

                        <div class="bulk-quote-field bulk-quote-quarter">
                            <label for="bulk-needed-by">Needed by / Delivery date <span class="bulk-quote-req">*</span></label>
                            <div class="bulk-quote-date-control">
                                <x-storefront.bulk-quote.icon name="calendar" :size="17" />
                                <input id="bulk-needed-by" type="date" lang="en-GB" name="needed_by" value="{{ old('needed_by') }}" required aria-invalid="{{ $errors->has('needed_by') ? 'true' : 'false' }}">
                            </div>
                            <x-storefront.bulk-quote.field-error name="needed_by" />
                        </div>

                        <div class="bulk-quote-field bulk-quote-quarter">
                            <label for="bulk-event-date">Event date <span class="bulk-quote-optional">Optional</span></label>
                            <div class="bulk-quote-date-control">
                                <x-storefront.bulk-quote.icon name="calendar" :size="17" />
                                <input id="bulk-event-date" type="date" lang="en-GB" name="event_date" value="{{ old('event_date') }}" aria-invalid="{{ $errors->has('event_date') ? 'true' : 'false' }}">
                            </div>
                            <x-storefront.bulk-quote.field-error name="event_date" />
                        </div>
                    </div>
                </x-storefront.bulk-quote.section>

                    @php
                        // Keep the existing CAPTCHA policy: production requires verification;
                        // local development does not make external CAPTCHA requests mandatory.
                        $recaptchaRequired = ! app()->environment('local');
                        $recaptchaConfigured = $recaptchaRequired
                            && filled(config('services.recaptcha.site_key'))
                            && filled(config('services.recaptcha.secret_key'));
                    @endphp
                    <div class="bulk-quote-attachments-card {{ $recaptchaRequired ? 'has-captcha' : 'without-captcha' }}">
                <x-storefront.bulk-quote.section
                    number="4"
                    title="Attachments & Notes"
                    description="Add any files or additional information."
                    icon="attachment"
                >
                    <div class="bulk-quote-grid bulk-quote-grid-two">
                        <div class="bulk-quote-field" x-data="{ fileName: '' }">
                            <label for="bulk-attachment">Upload artwork or reference files</label>
                            <label
                                class="bulk-quote-upload {{ $errors->has('attachment') ? 'is-invalid' : '' }}"
                                for="bulk-attachment"
                                @dragover.prevent
                                @drop.prevent="if ($event.dataTransfer.files.length) { $refs.attachment.files = $event.dataTransfer.files; fileName = $event.dataTransfer.files[0].name }"
                            >
                                <span class="bulk-quote-upload-icon"><x-storefront.bulk-quote.icon name="upload" :size="22" /></span>
                                <span class="bulk-quote-upload-copy">
                                    <strong><span x-show="!fileName">Drag files here or <span class="bulk-quote-browse-text">browse files</span></span><span x-show="fileName" x-text="fileName" x-cloak></span></strong>
                                    <small>PDF, JPG, PNG · Max 10 MB per file</small>
                                </span>
                            </label>
                            <input
                                x-ref="attachment"
                                id="bulk-attachment"
                                type="file"
                                name="attachment"
                                accept=".pdf,.png,.jpg,.jpeg,.ai,.eps,.svg"
                                class="bulk-quote-file-control"
                                @change="fileName = $event.target.files?.[0]?.name || ''"
                                aria-invalid="{{ $errors->has('attachment') ? 'true' : 'false' }}"
                            >
                            <x-storefront.bulk-quote.field-error name="attachment" />
                        </div>

                        <div class="bulk-quote-field" x-data="{ count: {{ mb_strlen((string) old('additional_notes', '')) }} }">
                            <label for="bulk-notes">Additional notes</label>
                            <div class="bulk-quote-control-wrap">
                                <textarea id="bulk-notes" name="additional_notes" maxlength="5000" placeholder="Anything else we should know?" aria-invalid="{{ $errors->has('additional_notes') ? 'true' : 'false' }}" @input="count = $event.target.value.length">{{ old('additional_notes') }}</textarea>
                                <div class="bulk-quote-counter" aria-hidden="true"><span x-text="count">{{ mb_strlen((string) old('additional_notes', '')) }}</span>/5000</div>
                            </div>
                            <x-storefront.bulk-quote.field-error name="additional_notes" />
                        </div>
                        @if($recaptchaRequired)
                            <div class="bulk-quote-field bulk-quote-security-field">
                                <p class="bulk-quote-label">Security Verification <span class="bulk-quote-req">*</span></p>
                                @if($recaptchaConfigured)
                                    <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
                                @else
                                    <p class="bulk-quote-field-error" role="alert">Quote requests are temporarily unavailable. Please try again later.</p>
                                @endif
                                <x-storefront.bulk-quote.field-error name="g-recaptcha-response" />
                            </div>
                        @endif
                    </div>
                </x-storefront.bulk-quote.section>
                <div class="bulk-quote-submit-row">
                    <p class="bulk-quote-privacy">
                        <span class="bulk-quote-privacy-icon" aria-hidden="true"><x-storefront.bulk-quote.icon name="shield-check" :size="17" /></span>
                        Your details are used to prepare and manage your quotation.
                    </p>
                    <button class="btn btn-secondary btn-sm bulk-quote-submit" type="submit" @disabled($recaptchaRequired && ! $recaptchaConfigured)>
                        <span>Submit Quote Request</span>
                        <x-storefront.bulk-quote.icon name="arrow-right" :size="16" />
                    </button>
                </div>
                    </div>
                </form>

                <aside class="bulk-quote-sidebar" aria-label="Quote request assistance">
                    <section class="bulk-quote-side-card bulk-quote-request-summary" aria-labelledby="bulk-quote-summary-heading">
                        <div class="bulk-quote-side-heading">
                            <span class="bulk-quote-side-icon" aria-hidden="true"><x-storefront.bulk-quote.icon name="file-text" :size="20" /></span>
                            <h2 id="bulk-quote-summary-heading">Your Request</h2>
                        </div>
                        <p class="bulk-quote-side-lead">Your selections will appear here.</p>
                        <dl class="bulk-quote-summary-rows" aria-live="polite" aria-atomic="false">
                            <div class="bulk-quote-summary-row">
                                <dt><x-storefront.bulk-quote.icon name="box" :size="18" /> <span>Products</span></dt>
                                <dd id="bulk-quote-summary-product">—</dd>
                            </div>
                            <div class="bulk-quote-summary-row">
                                <dt><x-storefront.bulk-quote.icon name="users" :size="18" /> <span>Quantity</span></dt>
                                <dd id="bulk-quote-summary-quantity">—</dd>
                            </div>
                            <div class="bulk-quote-summary-row">
                                <dt><x-storefront.bulk-quote.icon name="calendar" :size="18" /> <span>Delivery date</span></dt>
                                <dd id="bulk-quote-summary-date">—</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="bulk-quote-side-card" aria-labelledby="bulk-quote-next-heading">
                        <div class="bulk-quote-side-heading">
                            <span class="bulk-quote-side-icon" aria-hidden="true"><x-storefront.bulk-quote.icon name="settings" :size="20" /></span>
                            <h2 id="bulk-quote-next-heading">What happens next?</h2>
                        </div>
                        <ol class="bulk-quote-next-steps">
                            <li><span class="bulk-quote-next-num">1</span><div><strong>We review your request</strong><p>Our team will check your requirements and get back to you.</p></div></li>
                            <li><span class="bulk-quote-next-num">2</span><div><strong>You receive a tailored quotation</strong><p>We'll send you a detailed quote, including lead times.</p></div></li>
                            <li><span class="bulk-quote-next-num">3</span><div><strong>You approve artwork before production</strong><p>We'll share a design proof for your approval before we go into production.</p></div></li>
                        </ol>
                    </section>

                    <section class="bulk-quote-side-card" aria-labelledby="bulk-quote-help-heading">
                        <div class="bulk-quote-side-heading">
                            <span class="bulk-quote-side-icon" aria-hidden="true"><x-storefront.bulk-quote.icon name="message-circle" :size="20" /></span>
                            <h2 id="bulk-quote-help-heading">Need a hand?</h2>
                        </div>
                        <p class="bulk-quote-side-lead">Not sure about materials, sizes or artwork? Our team is here to help.</p>
                        @if($whatsappUrl)
                            <a class="bulk-quote-whatsapp-link" href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer">
                                <x-storefront.bulk-quote.icon name="whatsapp" :size="19" /> Chat on WhatsApp
                            </a>
                        @else
                            <a class="bulk-quote-whatsapp-link" href="{{ route('contact') }}">Contact our team</a>
                        @endif
                    </section>

                    <div class="bulk-quote-security-note">
                        <x-storefront.bulk-quote.icon name="lock" :size="21" />
                        <p><strong>No payment required to request a quote.</strong> You'll only receive a quotation. Payment is requested later, after you approve the design.</p>
                    </div>
                </aside>
            </div>
            @if($recaptchaConfigured)
                <script src="https://www.google.com/recaptcha/api.js" async defer></script>
            @endif
            <script src="{{ asset('js/bulk-quote-summary.js') }}?v=20261030a" defer></script>
            <script src="{{ asset('js/bulk-quote-selects.js') }}?v=20261030b" defer></script>
        </div>
    </div>
</x-layouts.storefront>
