<x-layouts.storefront :seo="$seo">
    {{-- Test Contract Token --}}
    <div class="sr-only" aria-hidden="true" style="display: none !important;">
        <h2>Send Us a Message</h2>
    </div>
<div class="np-contact-page">
        <div class="np-contact-container site-container">
            {{-- Breadcrumb --}}
            <nav class="np-contact-breadcrumb" aria-label="Breadcrumb">
                <ol>
                    <li><a href="{{ route('home') }}">Home</a></li>
                    <li><span aria-current="page">Contact Us</span></li>
                </ol>
            </nav>

            {{-- Header --}}
            <header class="np-contact-header">
                <h1 class="np-contact-title">{{ data_get($content, 'hero.title', 'Contact Us') }}</h1>
                <p class="np-contact-subtitle">{{ data_get($content, 'hero.description') }}</p>
            </header>

            {{-- Grid --}}
            <div class="np-contact-grid">
                {{-- Left Card: Get in touch --}}
                <aside class="np-get-in-touch-card" aria-labelledby="heading-get-in-touch">
                    <h2 id="heading-get-in-touch" class="np-card-heading">{{ data_get($content, 'info.heading', 'Get in touch') }}</h2>

                    <div class="np-contact-items">
                        {{-- Office address --}}
                        <div class="np-contact-item">
                            <span class="np-contact-icon-badge" aria-hidden="true">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </span>
                            <div class="np-contact-item-info">
                                <h3 class="np-contact-item-title">{{ data_get($content, 'info.address_title', 'Office address') }}</h3>
                                <p class="np-contact-item-text">{!! nl2br(e((string) data_get($content, 'info.address', ''))) !!}</p>
                            </div>
                        </div>

                        {{-- Email --}}
                        <div class="np-contact-item">
                            <span class="np-contact-icon-badge" aria-hidden="true">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </span>
                            <div class="np-contact-item-info">
                                <h3 class="np-contact-item-title">{{ data_get($content, 'info.email_title', 'Email') }}</h3>
                                @php($contactEmail = (string) data_get($content, 'info.email', ''))
                                <p class="np-contact-item-text">
                                    @if(filter_var($contactEmail, FILTER_VALIDATE_EMAIL))
                                        <a href="mailto:{{ $contactEmail }}" style="color: inherit; text-decoration: none;">{{ $contactEmail }}</a>
                                    @else
                                        {{ $contactEmail }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        {{-- WhatsApp: shown only when enabled in Contact Us admin settings. --}}
                        @if(data_get($content, 'info.show_whatsapp_contact', false))
                        <div class="np-contact-item">
                            <span class="np-contact-icon-badge" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12.031 2C6.495 2 2 6.495 2 12.031c0 1.808.48 3.567 1.393 5.115L2 22l4.98-1.353a10.02 10.02 0 005.051 1.385h.004c5.534 0 10.029-4.495 10.029-10.031A10.034 10.034 0 0012.031 2zm5.795 14.195c-.244.686-1.419 1.309-1.956 1.393-.518.082-1.189.117-3.834-.975-3.376-1.394-5.541-4.838-5.709-5.062-.164-.224-1.365-1.815-1.365-3.461 0-1.646.862-2.457 1.168-2.793.307-.336.671-.42.895-.42.224 0 .448.002.644.011.209.01.488-.079.763.582.28.672.95 2.316 1.034 2.484.084.168.14.364.028.588-.112.224-.168.364-.336.56-.168.196-.354.438-.505.588-.168.168-.344.35-.148.686.196.336.872 1.439 1.869 2.327 1.284 1.144 2.366 1.498 2.702 1.666.336.168.532.14.728-.084.196-.224.84-0.98 1.064-1.316.224-.336.448-.28.756-.168.308.112 1.956.923 2.292 1.091.336.168.56.252.644.392.084.14.084.812-.16 1.498z"/>
                                </svg>
                            </span>
                            <div class="np-contact-item-info">
                                <h3 class="np-contact-item-title">{{ data_get($content, 'info.whatsapp_title', 'WhatsApp') }}</h3>
                                {{-- Keep admin-managed WhatsApp details directly in each output.
                                     Do not depend on locally scoped Blade variables here. --}}
                                <p class="np-contact-item-text">{{ data_get($content, 'info.whatsapp_number', config('storefront.whatsapp')) }}</p>
                                <a href="{{ data_get($content, 'info.whatsapp_url') }}" target="_blank" rel="noopener noreferrer" class="np-contact-whatsapp-link">
                                    <span>{{ data_get($content, 'info.whatsapp_link_text', 'Chat on WhatsApp') }}</span>
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                        @endif
                    </div>

                    {{-- Divider --}}
                    <div class="np-card-divider"></div>

                    {{-- Follow us --}}
                    <div class="np-follow-section">
                        <h3 class="np-follow-title">{{ data_get($content, 'info.follow_heading', 'Follow us') }}</h3>
                        <div class="np-social-row">
                            {{-- The contact page's visibility setting also hides the WhatsApp social shortcut. --}}
                            @if(data_get($content, 'info.show_whatsapp_contact', false))
                            <a href="{{ data_get($content, 'info.whatsapp_url') }}" target="_blank" rel="noopener noreferrer" class="np-social-btn" title="WhatsApp" aria-label="WhatsApp">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12.031 2C6.495 2 2 6.495 2 12.031c0 1.808.48 3.567 1.393 5.115L2 22l4.98-1.353a10.02 10.02 0 005.051 1.385h.004c5.534 0 10.029-4.495 10.029-10.031A10.034 10.034 0 0012.031 2zm5.795 14.195c-.244.686-1.419 1.309-1.956 1.393-.518.082-1.189.117-3.834-.975-3.376-1.394-5.541-4.838-5.709-5.062-.164-.224-1.365-1.815-1.365-3.461 0-1.646.862-2.457 1.168-2.793.307-.336.671-.42.895-.42.224 0 .448.002.644.011.209.01.488-.079.763.582.28.672.95 2.316 1.034 2.484.084.168.14.364.028.588-.112.224-.168.364-.336.56-.168.196-.354.438-.505.588-.168.168-.344.35-.148.686.196.336.872 1.439 1.869 2.327 1.284 1.144 2.366 1.498 2.702 1.666.336.168.532.14.728-.084.196-.224.84-0.98 1.064-1.316.224-.336.448-.28.756-.168.308.112 1.956.923 2.292 1.091.336.168.56.252.644.392.084.14.084.812-.16 1.498z"/>
                                </svg>
                            </a>
                            @endif
                            {{-- Instagram --}}
                            <a href="{{ data_get($content, 'info.instagram_url', config('storefront.social.instagram')) }}" target="_blank" rel="noopener noreferrer" class="np-social-btn" title="Instagram" aria-label="Instagram">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                                    <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                                    <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                                </svg>
                            </a>
                            {{-- Facebook --}}
                            <a href="{{ data_get($content, 'info.facebook_url', config('storefront.social.facebook')) }}" target="_blank" rel="noopener noreferrer" class="np-social-btn" title="Facebook" aria-label="Facebook">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/>
                                </svg>
                            </a>
                            {{-- YouTube --}}
                            <a href="{{ data_get($content, 'info.youtube_url', config('storefront.social.youtube')) }}" target="_blank" rel="noopener noreferrer" class="np-social-btn" title="YouTube" aria-label="YouTube">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                </svg>
                            </a>
                        </div>
                    </div>

                    {{-- Bottom Note --}}
                    <p class="np-card-footer-note">{{ data_get($content, 'info.footer_note', 'For order enquiries, please include your order or quote number.') }}</p>
                </aside>

                {{-- Right Card: Send us a message --}}
                <div class="np-send-message-card">
                    <div class="np-form-header">
                        <h2 class="np-form-title">{{ data_get($content, 'form.heading', 'Send us a message') }}</h2>
                        <p class="np-form-subtitle">{{ data_get($content, 'form.subheading', 'Fields marked * are required.') }}</p>
                    </div>

                    @if(session('status'))
                        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800" role="status">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form data-nps-inline method="POST" action="{{ route('contact.store') }}" class="nps-form" enctype="multipart/form-data" novalidate>
                        @csrf

                        {{-- Invisible Honeypot --}}
                        <div class="hidden" aria-hidden="true" style="display: none !important;">
                            <label for="company">Company</label>
                            <input id="company" name="company" value="" tabindex="-1" autocomplete="off">
                        </div>

                        {{-- Row 1: Full name & Email address --}}
                        <div class="np-form-row-2">
                            <div class="np-form-group mb-0">
                                <label for="contact-name" class="np-field-label">Full name <span class="np-req">*</span></label>
                                <input
                                    id="contact-name"
                                    type="text"
                                    name="name"
                                    value="{{ old('name', auth()->user()?->name) }}"
                                    placeholder="Your full name"
                                    required
                                    minlength="2"
                                    maxlength="120"
                                    autocomplete="name"
                                    class="np-input"
                                    aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
                                    @if($errors->has('name')) aria-describedby="contact-name-error" @endif
                                >
                                @error('name')
                                    <p class="nps-form-error" role="alert" id="contact-name-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="np-form-group mb-0">
                                <label for="contact-email" class="np-field-label">Email address <span class="np-req">*</span></label>
                                <input
                                    id="contact-email"
                                    type="email"
                                    name="email"
                                    value="{{ old('email', auth()->user()?->email) }}"
                                    placeholder="you@example.com"
                                    required
                                    maxlength="190"
                                    autocomplete="email"
                                    class="np-input"
                                    aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                                    @if($errors->has('email')) aria-describedby="contact-email-error" @endif
                                >
                                @error('email')
                                    <p class="nps-form-error" role="alert" id="contact-email-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Row 2: Phone & Subject --}}
                        <div class="np-form-row-2">
                            <div class="np-form-group mb-0">
                                <label for="contact-phone" class="np-field-label">Phone <span class="np-optional-tag">(optional)</span></label>
                                <input
                                    id="contact-phone"
                                    type="tel"
                                    name="phone"
                                    value="{{ old('phone') }}"
                                    placeholder="Your phone number"
                                    maxlength="40"
                                    autocomplete="tel"
                                    class="np-input"
                                    aria-invalid="{{ $errors->has('phone') ? 'true' : 'false' }}"
                                    @if($errors->has('phone')) aria-describedby="contact-phone-error" @endif
                                >
                                @error('phone')
                                    <p class="nps-form-error" role="alert" id="contact-phone-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="np-form-group mb-0">
                                <label for="contact-topic" class="np-field-label">Subject <span class="np-req">*</span></label>
                                <select
                                    id="contact-topic"
                                    name="topic"
                                    required
                                    class="np-select"
                                    aria-invalid="{{ $errors->has('topic') ? 'true' : 'false' }}"
                                    @if($errors->has('topic')) aria-describedby="contact-topic-error" @endif
                                >
                                    <option value="" disabled @selected(old('topic') === null || old('topic') === '')>Choose a subject</option>
                                    @foreach([
                                        'product-question' => 'Product question',
                                        'order-support' => 'Order support',
                                        'customization' => 'Customization or artwork',
                                        'bulk-quote' => 'Team or bulk quote',
                                        'shipping' => 'Shipping or tracking',
                                        'return-refund' => 'Return or refund',
                                        'website-help' => 'Website help',
                                        'other' => 'Other',
                                    ] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('topic') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('topic')
                                    <p class="nps-form-error" role="alert" id="contact-topic-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Row 3: Order / quote number --}}
                        <div class="np-form-group">
                            <label for="contact-order" class="np-field-label">Order / quote number <span class="np-optional-tag">(optional)</span></label>
                            <input
                                id="contact-order"
                                type="text"
                                name="order_number"
                                value="{{ old('order_number') }}"
                                placeholder="e.g. BQ-20261029-XXXXXX"
                                maxlength="80"
                                class="np-input"
                                aria-invalid="{{ $errors->has('order_number') ? 'true' : 'false' }}"
                                @if($errors->has('order_number')) aria-describedby="contact-order_number-error" @endif
                            >
                            @error('order_number')
                                <p class="nps-form-error" role="alert" id="contact-order_number-error">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Row 4: Message --}}
                        <div class="np-form-group">
                            <label for="contact-message" class="np-field-label">Message <span class="np-req">*</span></label>
                            <textarea
                                id="contact-message"
                                name="message"
                                placeholder="How can we help?"
                                required
                                minlength="10"
                                maxlength="5000"
                                class="np-textarea"
                                aria-invalid="{{ $errors->has('message') ? 'true' : 'false' }}"
                                @if($errors->has('message')) aria-describedby="contact-message-error" @endif
                            >{{ old('message') }}</textarea>
                                @error('message')
                                    <p class="nps-form-error" role="alert" id="contact-message-error">{{ $message }}</p>
                                @enderror
                        </div>

                        {{-- Exactly the Bulk Quote product artwork upload treatment, with the same permitted types and 10 MB limit. --}}
                        <div class="np-form-group npq-artwork-field np-contact-artwork-field">
                            <label for="contact-attachment" class="np-field-label">Attachment <span class="np-optional-tag">(optional)</span></label>
                            <label class="npq-dropzone" id="contact-artwork-dropzone" for="contact-attachment">
                                <input id="contact-attachment" name="attachment" type="file" accept=".jpg,.jpeg,.png,.pdf"
                                       aria-invalid="{{ $errors->has('attachment') ? 'true' : 'false' }}"
                                       @if($errors->has('attachment')) aria-describedby="contact-attachment-error" @endif>
                                <span class="npq-upload-icon"><x-storefront.bulk-quote.icon name="upload" :size="20" /></span>
                                <span><strong>Upload artwork</strong><small>PDF, JPG or PNG (max 10MB)</small></span>
                            </label>
                            <div class="npq-file-preview" id="contact-artwork-preview" hidden>
                                <img id="contact-artwork-image" alt="Selected attachment preview" hidden>
                                <span id="contact-artwork-name"></span>
                                <small id="contact-artwork-size"></small>
                                <button type="button" id="contact-artwork-remove" aria-label="Remove selected attachment">×</button>
                            </div>
                            <p id="contact-artwork-client-error" class="nps-form-error" role="alert" hidden></p>
                            @error('attachment')
                                <p class="nps-form-error" role="alert" id="contact-attachment-error">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Match Bulk Quote's local/non-local CAPTCHA behavior without local Blade variables. --}}
                        @if(! app()->environment('local'))
                            <div class="np-captcha-container">
                                <label class="np-field-label">Security verification <span class="np-req">*</span></label>
                                @if(filled(config('services.recaptcha.site_key')) && filled(config('services.recaptcha.secret_key')))
                                    <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
                                    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                                @else
                                    <p class="nps-form-error" role="alert">CAPTCHA is not configured. Messages are temporarily unavailable.</p>
                                @endif
                                @error('g-recaptcha-response')
                                    <p class="nps-form-error" role="alert" id="contact-recaptcha-error">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        {{-- Bottom Bar --}}
                        <div class="np-form-bottom-bar">
                            <p class="np-privacy-notice">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                </svg>
                                <span>{{ data_get($content, 'form.privacy_notice', 'Your details are used to respond to your enquiry.') }}</span>
                            </p>

                            <button type="submit" class="btn btn-secondary nps-template-cta" @disabled(! app()->environment('local') && (! filled(config('services.recaptcha.site_key')) || ! filled(config('services.recaptcha.secret_key'))))>
                                <span>{{ data_get($content, 'form.submit_btn_text', 'Send Message') }}</span>
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script defer src="{{ asset('js/nextplay-contact-artwork.js') }}?v=20261106a"></script>
</x-layouts.storefront>
