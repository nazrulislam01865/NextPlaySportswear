<x-layouts.storefront
    :seo="[
        'title' => 'Request a Bulk Quote | ' . config('storefront.name'),
        'description' => 'Request a bulk quote for team uniforms, school apparel, league orders, event apparel, promotional products, artwork, delivery dates, and shipping details.',
        'schema_type' => 'ContactPage',
    ]"
    :structured-data="$structuredData ?? []"
>
    <div class="bulk-quote-page">
        <section class="bulk-quote-page-head">
            <div class="bulk-quote-page-head-wrap">
                <div class="bulk-quote-page-title">
                    <div class="bulk-quote-breadcrumb">
                        <a href="{{ route('home') }}">Home</a><span>/</span><a href="{{ route('bulk-ordering') }}">Bulk Orders</a><span>/</span><strong>Request Quote</strong>
                    </div>
                    <p class="eyebrow">Bulk Order Request</p>
                    <h1>Request a Bulk Quote</h1>
                    <p>Tell us what you need for your team, school, league, or event. Share your items, quantity, sizes, artwork, delivery date, and shipping details. We will review everything and prepare a clear quote.</p>
                </div>
                <div class="bulk-quote-page-head-actions">
                    <a class="bulk-page-btn bulk-page-btn-ghost" href="#faq">Read FAQ</a>
                    <a class="bulk-page-btn bulk-page-btn-red" href="#quote">Start Form →</a>
                </div>
            </div>
            <div class="bulk-quote-trust-strip">
                <div class="bulk-quote-trust-item"><span>10+</span> Team uniforms</div>
                <div class="bulk-quote-trust-item"><span>50+</span> Event apparel</div>
                <div class="bulk-quote-trust-item"><span>100+</span> Promotional items</div>
                <div class="bulk-quote-trust-item"><span>⏱</span> Quote review within 24 hours</div>
            </div>
        </section>

        <div class="bulk-quote-main">
            <div class="bulk-quote-form-shell" id="quote">
                <form class="bulk-page-card bulk-quote-form-card" method="POST" action="{{ route('quote.request.store') }}" enctype="multipart/form-data" novalidate>
                    @csrf
                    <div class="bulk-quote-form-head">
                        <div class="bulk-quote-form-icon">📋</div>
                        <div>
                            <h2>Bulk Quote Request</h2>
                            <p>Fields marked with <span class="bulk-quote-req">*</span> are required. Keep it simple. If you are unsure about any detail, write your best estimate.</p>
                        </div>
                    </div>

                    <div class="bulk-quote-form-body">
                        @if(session('status'))
                            <div class="bulk-quote-alert bulk-quote-alert-success" role="status">{{ session('status') }}</div>
                        @endif

                        @if($errors->any())
                            <div class="bulk-quote-alert bulk-quote-alert-error" role="alert">
                                <strong>Please correct the highlighted fields.</strong>
                                <ul>
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="hidden" aria-hidden="true">
                            <label for="bulk-company">Company</label>
                            <input id="bulk-company" name="company" value="" tabindex="-1" autocomplete="off">
                        </div>

                        <section class="bulk-quote-section">
                            <div class="bulk-quote-section-title"><span class="bulk-quote-step">1</span><h3>Contact Information</h3></div>
                            <p class="bulk-quote-section-note">We need this to contact you and confirm order details.</p>
                            <div class="bulk-quote-grid">
                                <div>
                                    <label for="bulk-full-name">Full Name <span class="bulk-quote-req">*</span></label>
                                    <input id="bulk-full-name" name="full_name" value="{{ old('full_name', auth()->user()?->name) }}" required maxlength="120" autocomplete="name" placeholder="Your full name" aria-invalid="{{ $errors->has('full_name') ? 'true' : 'false' }}">
                                </div>
                                <div>
                                    <label for="bulk-organization">Company / Team / School <span class="bulk-quote-req">*</span></label>
                                    <input id="bulk-organization" name="organization" value="{{ old('organization') }}" required maxlength="160" placeholder="Organization or team name" aria-invalid="{{ $errors->has('organization') ? 'true' : 'false' }}">
                                </div>
                                <div>
                                    <label for="bulk-email">Email <span class="bulk-quote-req">*</span></label>
                                    <input id="bulk-email" type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required maxlength="190" autocomplete="email" placeholder="you@example.com" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}">
                                </div>
                                <div>
                                    <label for="bulk-phone">Phone / WhatsApp <span class="bulk-quote-req">*</span></label>
                                    <input id="bulk-phone" name="phone" value="{{ old('phone') }}" required maxlength="40" autocomplete="tel" placeholder="+1 (555) 123-4567" aria-invalid="{{ $errors->has('phone') ? 'true' : 'false' }}">
                                </div>
                            </div>
                        </section>

                        <section class="bulk-quote-section">
                            <div class="bulk-quote-section-title"><span class="bulk-quote-step">2</span><h3>Order Details</h3></div>
                            <p class="bulk-quote-section-note">Add the main information about the items you want to order.</p>
                            <div class="bulk-quote-grid">
                                <div>
                                    <label for="bulk-product-type">Items Needed / Product Type <span class="bulk-quote-req">*</span></label>
                                    <input id="bulk-product-type" name="product_type" value="{{ old('product_type') }}" required maxlength="190" placeholder="e.g., Baseball jerseys, hoodies, caps" aria-invalid="{{ $errors->has('product_type') ? 'true' : 'false' }}">
                                </div>
                                <div>
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
                                </div>
                                <div>
                                    <label for="bulk-sizes">Sizes Needed <span class="bulk-quote-req">*</span></label>
                                    <input id="bulk-sizes" name="sizes_needed" value="{{ old('sizes_needed') }}" required maxlength="190" placeholder="e.g., S, M, L, XL, 2XL or size range" aria-invalid="{{ $errors->has('sizes_needed') ? 'true' : 'false' }}">
                                </div>
                                <div>
                                    <label for="bulk-budget">Budget Range</label>
                                    <select id="bulk-budget" name="budget_range">
                                        <option value="">Select budget range</option>
                                        @foreach([
                                            'under-500' => 'Under $500',
                                            '500-1500' => '$500–$1,500',
                                            '1500-5000' => '$1,500–$5,000',
                                            '5000-plus' => '$5,000+',
                                            'not-sure' => 'Not sure yet',
                                        ] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('budget_range') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="bulk-quote-full">
                                    <label for="bulk-artwork-details">Artwork / Logo / Customization Details <span class="bulk-quote-req">*</span></label>
                                    <textarea id="bulk-artwork-details" name="artwork_details" required minlength="10" maxlength="5000" placeholder="Describe logo, colors, names, numbers, print/embroidery placement, fabric choice, or other custom details..." aria-invalid="{{ $errors->has('artwork_details') ? 'true' : 'false' }}">{{ old('artwork_details') }}</textarea>
                                </div>
                                <div class="bulk-quote-full">
                                    <label>What kind of customization do you need?</label>
                                    <div class="bulk-quote-inline-checks">
                                        @foreach([
                                            'logo' => 'Logo',
                                            'names-numbers' => 'Names & numbers',
                                            'full-custom-design' => 'Full custom design',
                                            'embroidery' => 'Embroidery',
                                            'sublimation' => 'Sublimation',
                                            'not-sure' => 'Not sure',
                                        ] as $value => $label)
                                            <label class="bulk-quote-check-card"><input type="checkbox" name="customization_types[]" value="{{ $value }}" @checked(in_array($value, old('customization_types', []), true))> {{ $label }}</label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="bulk-quote-section">
                            <div class="bulk-quote-section-title"><span class="bulk-quote-step">3</span><h3>Shipping & Deadline</h3></div>
                            <p class="bulk-quote-section-note">This helps us check if your timeline is possible before we quote.</p>
                            <div class="bulk-quote-grid">
                                <div class="bulk-quote-full">
                                    <label for="bulk-shipping-address">Shipping Address <span class="bulk-quote-req">*</span></label>
                                    <input id="bulk-shipping-address" name="shipping_address" value="{{ old('shipping_address') }}" required maxlength="500" placeholder="Street address, city, state/province, ZIP/postal code, country" aria-invalid="{{ $errors->has('shipping_address') ? 'true' : 'false' }}">
                                </div>
                                <div>
                                    <label for="bulk-country">Country <span class="bulk-quote-req">*</span></label>
                                    <select id="bulk-country" name="country" required aria-invalid="{{ $errors->has('country') ? 'true' : 'false' }}">
                                        <option value="">Select country</option>
                                        @foreach(['United States', 'Canada', 'United Kingdom', 'Australia', 'Other'] as $country)
                                            <option value="{{ $country }}" @selected(old('country') === $country)>{{ $country }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="bulk-state">State / Province</label>
                                    <input id="bulk-state" name="state_province" value="{{ old('state_province') }}" maxlength="120" placeholder="State or province">
                                </div>
                                <div>
                                    <label for="bulk-postal-code">ZIP / Postal Code</label>
                                    <input id="bulk-postal-code" name="postal_code" value="{{ old('postal_code') }}" maxlength="40" placeholder="ZIP / Postal Code">
                                </div>
                                <div>
                                    <label for="bulk-shipping-method">Preferred Shipping Method</label>
                                    <select id="bulk-shipping-method" name="preferred_shipping_method">
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
                                </div>
                                <div>
                                    <label for="bulk-needed-by">Needed By / Delivery Date <span class="bulk-quote-req">*</span></label>
                                    <input id="bulk-needed-by" type="date" name="needed_by" value="{{ old('needed_by') }}" required aria-invalid="{{ $errors->has('needed_by') ? 'true' : 'false' }}">
                                </div>
                                <div>
                                    <label for="bulk-event-date">Event Date</label>
                                    <input id="bulk-event-date" type="date" name="event_date" value="{{ old('event_date') }}">
                                    <div class="bulk-quote-hint">Optional, but helpful for tournaments, school events, and league launches.</div>
                                </div>
                            </div>
                        </section>

                        <section class="bulk-quote-section">
                            <div class="bulk-quote-section-title"><span class="bulk-quote-step">4</span><h3>Attachments & Notes</h3></div>
                            <div class="bulk-quote-grid">
                                <div>
                                    <label for="bulk-attachment">Attachment Upload</label>
                                    <label class="bulk-quote-upload" for="bulk-attachment">
                                        <span>
                                            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                            <strong>Click to upload or drag and drop</strong>
                                            <em>PDF, PNG, JPG, AI, EPS. Max 10MB</em>
                                        </span>
                                    </label>
                                    <input id="bulk-attachment" type="file" name="attachment" accept=".pdf,.png,.jpg,.jpeg,.ai,.eps,.svg" class="bulk-quote-file-input">
                                </div>
                                <div>
                                    <label for="bulk-notes">Additional Notes</label>
                                    <textarea id="bulk-notes" name="additional_notes" maxlength="5000" placeholder="Anything else we should know?">{{ old('additional_notes') }}</textarea>
                                </div>
                            </div>
                        </section>

                        <div class="bulk-quote-submit-row">
                            <div class="bulk-quote-privacy">🛡️ Your details are used only to prepare your quote.</div>
                            <button class="bulk-page-btn bulk-page-btn-red" type="submit">Request Bulk Quote →</button>
                        </div>
                    </div>
                </form>

                <aside class="bulk-quote-side">
                    <div class="bulk-page-card bulk-quote-side-card">
                        <h3>What happens next?</h3>
                        <p>After you submit the form, our team checks your items, artwork, quantity, and delivery timeline.</p>
                        <div class="bulk-quote-mini-list">
                            <div class="bulk-quote-mini"><span class="bulk-quote-dot">1</span><div><b>Review</b><br>We review your request.</div></div>
                            <div class="bulk-quote-mini"><span class="bulk-quote-dot">2</span><div><b>Clarify</b><br>We contact you if details are missing.</div></div>
                            <div class="bulk-quote-mini"><span class="bulk-quote-dot">3</span><div><b>Quote</b><br>You receive pricing and timeline.</div></div>
                        </div>
                    </div>
                    <div class="bulk-page-card bulk-quote-side-card">
                        <h3>Helpful before submitting</h3>
                        <div class="bulk-quote-mini-list">
                            <div class="bulk-quote-mini"><span class="bulk-quote-dot">✓</span><div>Logo or artwork file</div></div>
                            <div class="bulk-quote-mini"><span class="bulk-quote-dot">✓</span><div>Quantity and size breakdown</div></div>
                            <div class="bulk-quote-mini"><span class="bulk-quote-dot">✓</span><div>Expected delivery date</div></div>
                            <div class="bulk-quote-mini"><span class="bulk-quote-dot">✓</span><div>Shipping address</div></div>
                        </div>
                    </div>
                    <div class="bulk-page-card bulk-quote-side-card bulk-quote-contact-box">
                        <h3>Need help?</h3>
                        <p>Send your question and we can guide you before you fill the full form.</p>
                        <a class="bulk-quote-contact-pill" href="https://wa.me/{{ preg_replace('/\D+/', '', config('storefront.whatsapp')) }}" rel="noopener">☎ {{ config('storefront.whatsapp') }}</a>
                        <a class="bulk-quote-contact-pill" href="mailto:{{ config('storefront.email') }}">✉ {{ config('storefront.email') }}</a>
                    </div>
                </aside>
            </div>

            <section class="bulk-page-card bulk-quote-faq" id="faq">
                <div class="bulk-quote-faq-head">
                    <p class="eyebrow">Common Questions</p>
                    <h2>Bulk Quote FAQ</h2>
                    <p>Simple answers before you submit your request.</p>
                </div>
                <div class="bulk-quote-faq-grid">
                    @foreach($faqItems as $item)
                        <div class="bulk-quote-faq-item">
                            <h3>{{ $item['question'] }}</h3>
                            <p>{{ $item['answer'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-layouts.storefront>
