<x-layouts.storefront
    :seo="[
        'title' => 'Request a Bulk Quote | ' . config('storefront.name'),
        'description' => 'Request a bulk quote for team uniforms, school apparel, league orders, event apparel, promotional products, artwork, delivery dates, and shipping details.',
        'schema_type' => 'ContactPage',
    ]"
    :structured-data="$structuredData ?? []"
>
    <div class="bulk-quote-page">
        <header class="bulk-quote-intro site-container">
            <nav class="bulk-quote-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('bulk-ordering') }}">Bulk Orders</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">Request Quote</span>
            </nav>
            <h1>Request a Bulk Quote</h1>
            <p>Tell us about your team, products, and timeline. We'll get back to you with a custom quote.</p>
        </header>

        <div class="bulk-quote-main site-container" id="quote">
            <form
                class="bulk-quote-card"
                method="POST"
                action="{{ route('quote.request.store') }}"
                enctype="multipart/form-data"
                novalidate
            >
                @csrf

                <x-storefront.bulk-quote.stepper />

                @if(session('status'))
                    <div class="bulk-quote-alert bulk-quote-alert-success" role="status">{{ session('status') }}</div>
                @endif

                <div class="hidden" aria-hidden="true">
                    <label for="bulk-company">Company</label>
                    <input id="bulk-company" name="company" value="" tabindex="-1" autocomplete="off">
                </div>

                <x-storefront.bulk-quote.section
                    number="1"
                    title="Contact Information"
                    description="Let us know who you are and how to reach you."
                    icon="user"
                >
                    <div class="bulk-quote-grid bulk-quote-grid-two">
                        <div class="bulk-quote-field">
                            <label for="bulk-full-name">Full Name <span class="bulk-quote-req">*</span></label>
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
                                $selectedCallingCodeId = (string) old('phone_country_code_id', optional($countryCallingCodes->first())->id);
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
                                        <option value="{{ $callingCode->id }}" @selected($selectedCallingCodeId === (string) $callingCode->id)>
                                            {{ $callingCode->flagEmoji() }} {{ $callingCode->iso_code }} {{ $callingCode->dial_code }}
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
                                    placeholder="(555) 123-4567"
                                    aria-invalid="{{ $errors->has('phone') ? 'true' : 'false' }}"
                                >
                            </div>
                            <x-storefront.bulk-quote.field-error name="phone_country_code_id" />
                            <x-storefront.bulk-quote.field-error name="phone" />
                        </div>
                    </div>
                </x-storefront.bulk-quote.section>

                <x-storefront.bulk-quote.section
                    number="2"
                    title="Order Details"
                    description="Tell us about the products you need."
                    icon="box"
                >
                    <div class="bulk-quote-grid bulk-quote-grid-two">
                        <div class="bulk-quote-field">
                            <label for="bulk-product-type">Items Needed / Product Type <span class="bulk-quote-req">*</span></label>
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
                            <label for="bulk-quantity">Estimated Quantity <span class="bulk-quote-req">*</span></label>
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
                            <label for="bulk-sizes">Sizes Needed <span class="bulk-quote-req">*</span></label>
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
                            <label for="bulk-budget">Budget Range</label>
                            <select id="bulk-budget" name="budget_range" aria-invalid="{{ $errors->has('budget_range') ? 'true' : 'false' }}">
                                <option value="">Select budget range</option>
                                @foreach($budgetRanges as $budgetRange)
                                    <option value="{{ $budgetRange->value }}" @selected(old('budget_range') === $budgetRange->value)>{{ $budgetRange->label }}</option>
                                @endforeach
                            </select>
                            <x-storefront.bulk-quote.field-error name="budget_range" />
                        </div>

                        <div class="bulk-quote-field bulk-quote-full" x-data="{ count: {{ mb_strlen((string) old('artwork_details', '')) }} }">
                            <label for="bulk-artwork-details">Customization Details <span class="bulk-quote-req">*</span></label>
                            <div class="bulk-quote-control-wrap">
                                <textarea
                                    id="bulk-artwork-details"
                                    name="artwork_details"
                                    required
                                    minlength="10"
                                    maxlength="5000"
                                    placeholder="Describe colors, names, numbers, placement, or any other custom details..."
                                    aria-invalid="{{ $errors->has('artwork_details') ? 'true' : 'false' }}"
                                    @input="count = $event.target.value.length"
                                >{{ old('artwork_details') }}</textarea>
                                <div class="bulk-quote-counter" aria-hidden="true"><span x-text="count">{{ mb_strlen((string) old('artwork_details', '')) }}</span>/5000</div>
                            </div>
                            <x-storefront.bulk-quote.field-error name="artwork_details" />
                        </div>

                        <div class="bulk-quote-field bulk-quote-full">
                            <label>Customization Options</label>
                            <p class="bulk-quote-field-note">Select all that apply</p>
                            <div class="bulk-quote-custom-options">
                                <x-storefront.bulk-quote.customization-option value="logo" label="Logo" icon="logo" />
                                <x-storefront.bulk-quote.customization-option value="names-numbers" label="Names & Numbers" icon="numbers" />
                                <x-storefront.bulk-quote.customization-option value="embroidery" label="Embroidery" icon="embroidery" />
                                <x-storefront.bulk-quote.customization-option value="sublimation" label="Sublimation" icon="drop" />
                                <x-storefront.bulk-quote.customization-option value="full-custom-design" label="Full Custom Design" icon="custom" />
                            </div>
                            <x-storefront.bulk-quote.field-error name="customization_types" />
                            <x-storefront.bulk-quote.field-error name="customization_types.*" />
                        </div>
                    </div>
                </x-storefront.bulk-quote.section>

                <x-storefront.bulk-quote.section
                    number="3"
                    title="Shipping & Deadline"
                    description="Where should we ship and when do you need it?"
                    icon="truck"
                >
                    <div class="bulk-quote-grid bulk-quote-shipping-grid">
                        <div class="bulk-quote-field bulk-quote-full">
                            <label for="bulk-shipping-address">Shipping Address <span class="bulk-quote-req">*</span></label>
                            <input
                                id="bulk-shipping-address"
                                name="shipping_address"
                                value="{{ old('shipping_address') }}"
                                required
                                maxlength="500"
                                placeholder="Street address, city, state/province, ZIP/postal code, country"
                                aria-invalid="{{ $errors->has('shipping_address') ? 'true' : 'false' }}"
                            >
                            <x-storefront.bulk-quote.field-error name="shipping_address" />
                        </div>

                        <div class="bulk-quote-field bulk-quote-half">
                            <label for="bulk-country">Country <span class="bulk-quote-req">*</span></label>
                            <select id="bulk-country" name="country" required aria-invalid="{{ $errors->has('country') ? 'true' : 'false' }}">
                                <option value="">Select country</option>
                                @foreach(['United States', 'Canada', 'United Kingdom', 'Australia', 'Other'] as $country)
                                    <option value="{{ $country }}" @selected(old('country') === $country)>{{ $country }}</option>
                                @endforeach
                            </select>
                            <x-storefront.bulk-quote.field-error name="country" />
                        </div>

                        <div class="bulk-quote-field bulk-quote-half">
                            <label for="bulk-state">State / Province</label>
                            <input id="bulk-state" name="state_province" value="{{ old('state_province') }}" maxlength="120" placeholder="Select state or province" aria-invalid="{{ $errors->has('state_province') ? 'true' : 'false' }}">
                            <x-storefront.bulk-quote.field-error name="state_province" />
                        </div>

                        <div class="bulk-quote-field bulk-quote-quarter">
                            <label for="bulk-postal-code">ZIP / Postal Code</label>
                            <input id="bulk-postal-code" name="postal_code" value="{{ old('postal_code') }}" maxlength="40" placeholder="ZIP / Postal Code" aria-invalid="{{ $errors->has('postal_code') ? 'true' : 'false' }}">
                            <x-storefront.bulk-quote.field-error name="postal_code" />
                        </div>

                        <div class="bulk-quote-field bulk-quote-quarter bulk-quote-quarter-wide">
                            <label for="bulk-shipping-method">Preferred Shipping Method</label>
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
                            <label for="bulk-needed-by">Needed By / Delivery Date <span class="bulk-quote-req">*</span></label>
                            <input id="bulk-needed-by" type="date" name="needed_by" value="{{ old('needed_by') }}" required aria-invalid="{{ $errors->has('needed_by') ? 'true' : 'false' }}">
                            <x-storefront.bulk-quote.field-error name="needed_by" />
                        </div>

                        <div class="bulk-quote-field bulk-quote-quarter">
                            <label for="bulk-event-date">Event Date</label>
                            <input id="bulk-event-date" type="date" name="event_date" value="{{ old('event_date') }}" aria-invalid="{{ $errors->has('event_date') ? 'true' : 'false' }}">
                            <x-storefront.bulk-quote.field-error name="event_date" />
                        </div>
                    </div>
                </x-storefront.bulk-quote.section>

                <x-storefront.bulk-quote.section
                    number="4"
                    title="Attachments & Notes"
                    description="Add any files or additional information."
                    icon="attachment"
                >
                    <div class="bulk-quote-grid bulk-quote-grid-two">
                        <div class="bulk-quote-field" x-data="{ fileName: '' }">
                            <label for="bulk-attachment">Upload Files</label>
                            <label
                                class="bulk-quote-upload {{ $errors->has('attachment') ? 'is-invalid' : '' }}"
                                for="bulk-attachment"
                                @dragover.prevent
                                @drop.prevent="if ($event.dataTransfer.files.length) { $refs.attachment.files = $event.dataTransfer.files; fileName = $event.dataTransfer.files[0].name }"
                            >
                                <span class="bulk-quote-upload-icon"><x-storefront.bulk-quote.icon name="upload" :size="22" /></span>
                                <span class="bulk-quote-upload-copy">
                                    <strong x-text="fileName || 'Click to upload or drag and drop'">Click to upload or drag and drop</strong>
                                    <small>PDF, JPG, PNG. Max 10MB</small>
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
                            <label for="bulk-notes">Additional Notes</label>
                            <div class="bulk-quote-control-wrap">
                                <textarea id="bulk-notes" name="additional_notes" maxlength="5000" placeholder="Anything else we should know?" aria-invalid="{{ $errors->has('additional_notes') ? 'true' : 'false' }}" @input="count = $event.target.value.length">{{ old('additional_notes') }}</textarea>
                                <div class="bulk-quote-counter" aria-hidden="true"><span x-text="count">{{ mb_strlen((string) old('additional_notes', '')) }}</span>/5000</div>
                            </div>
                            <x-storefront.bulk-quote.field-error name="additional_notes" />
                        </div>
                    </div>
                </x-storefront.bulk-quote.section>

                <div class="bulk-quote-submit-row">
                    <p class="bulk-quote-privacy">
                        <span class="bulk-quote-privacy-icon" aria-hidden="true"><x-storefront.bulk-quote.icon name="shield-check" :size="17" /></span>
                        Your information is used only to prepare your quote and will not be shared.
                    </p>
                    <button class="btn btn-secondary btn-sm bulk-quote-submit" type="submit">
                        <span>REQUEST BULK QUOTE</span>
                        <x-storefront.bulk-quote.icon name="arrow-right" :size="16" />
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.storefront>
