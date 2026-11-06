<x-layouts.admin
    title="Contact Us Page"
    :storefront-url="route('contact')"
>
    @php
        $fieldClass = 'admin-input';
        $textareaClass = 'admin-input np-content-textarea';
    @endphp

    <form method="POST" action="{{ route('admin.contact-page.update') }}" class="np-storefront-editor space-y-6">
        @csrf
        @method('PUT')

        <x-admin.storefront-editor-toolbar
            description="Manage office address, support email, WhatsApp number & link, social media profiles, enquiry notices, and SEO on the Contact Us page."
            :preview-url="route('contact')"
            preview-label="Preview Contact Page"
            save-label="Save Contact Us Page"
            :can-manage="$canManage"
        />

        @if(session('status'))
            <div data-admin-flash class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
        @endif

        @if($errors->has('contact_page'))
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{{ $errors->first('contact_page') }}</div>
        @endif

        @if($errors->any() && ! $errors->has('contact_page'))
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-900">Please correct the highlighted fields and save again.</div>
        @endif

        {{-- Page Header --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Header</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Page Header</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">The main title and subtitle displayed at the top of the Contact Us page.</p>
            </div>
            <div class="grid gap-5 p-5 sm:p-6 lg:grid-cols-2">
                <div>
                    <label class="admin-label" for="header-title">Page Title</label>
                    <input id="header-title" class="{{ $fieldClass }}" name="hero[title]" value="{{ old('hero.title', data_get($content, 'hero.title', 'Contact Us')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="header-desc">Subtitle</label>
                    <input id="header-desc" class="{{ $fieldClass }}" name="hero[description]" value="{{ old('hero.description', data_get($content, 'hero.description', 'Questions about a product or order? Send us a message.')) }}" maxlength="1000" required @disabled(! $canManage)>
                </div>
            </div>
        </section>

        {{-- Left Card: Get in touch Information --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Contact Information</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Get in Touch Details</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">Physical address, support email, WhatsApp connection, and social channels.</p>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="info-heading">Card Section Heading</label>
                    <input id="info-heading" class="{{ $fieldClass }}" name="info[heading]" value="{{ old('info.heading', data_get($content, 'info.heading', 'Get in touch')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>

                {{-- Office address --}}
                <div class="rounded-2xl border border-slate-200 p-4">
                    <div class="grid gap-4 lg:grid-cols-3">
                        <div>
                            <label class="admin-label" for="info-addr-title">Address Label</label>
                            <input id="info-addr-title" class="{{ $fieldClass }}" name="info[address_title]" value="{{ old('info.address_title', data_get($content, 'info.address_title', 'Office address')) }}" maxlength="255" required @disabled(! $canManage)>
                        </div>
                        <div class="lg:col-span-2">
                            <label class="admin-label" for="info-address">Full Postal Address (supports line breaks)</label>
                            <textarea id="info-address" class="{{ $textareaClass }} h-20" name="info[address]" maxlength="1000" required @disabled(! $canManage)>{{ old('info.address', data_get($content, 'info.address')) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Email & WhatsApp --}}
                <div class="rounded-2xl border border-slate-200 p-4 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="admin-label" for="info-email-title">Email Label</label>
                            <input id="info-email-title" class="{{ $fieldClass }}" name="info[email_title]" value="{{ old('info.email_title', data_get($content, 'info.email_title', 'Email')) }}" maxlength="255" required @disabled(! $canManage)>
                        </div>
                        <div>
                            <label class="admin-label" for="info-email">Support Email Address</label>
                            <input id="info-email" class="{{ $fieldClass }}" type="email" name="info[email]" value="{{ old('info.email', data_get($content, 'info.email')) }}" maxlength="255" required @disabled(! $canManage)>
                        </div>
                    </div>

                    {{-- Only controls the left-side WhatsApp contact entry on the public Contact Us page. --}}
                    <div class="flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <input type="hidden" name="info[show_whatsapp_contact]" value="0" @disabled(! $canManage)>
                        <input id="info-show-wa-contact" type="checkbox" name="info[show_whatsapp_contact]" value="1"
                               class="mt-1 h-4 w-4 accent-brand-red" @checked((bool) old('info.show_whatsapp_contact', data_get($content, 'info.show_whatsapp_contact', false)))
                               @disabled(! $canManage)>
                        <div>
                            <label class="admin-label mb-1 cursor-pointer" for="info-show-wa-contact">Show WhatsApp in contact information</label>
                            <p class="text-sm text-slate-500">Uncheck to hide the WhatsApp contact block and WhatsApp social shortcut on the public Contact Us page. Other social links and site-wide WhatsApp settings are unchanged.</p>
                            @error('info.show_whatsapp_contact')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-4">
                        <div>
                            <label class="admin-label" for="info-wa-title">WhatsApp Label</label>
                            <input id="info-wa-title" class="{{ $fieldClass }}" name="info[whatsapp_title]" value="{{ old('info.whatsapp_title', data_get($content, 'info.whatsapp_title', 'WhatsApp')) }}" maxlength="255" required @disabled(! $canManage)>
                        </div>
                        <div>
                            <label class="admin-label" for="info-wa-num">Displayed Number</label>
                            <input id="info-wa-num" class="{{ $fieldClass }}" name="info[whatsapp_number]" value="{{ old('info.whatsapp_number', data_get($content, 'info.whatsapp_number')) }}" maxlength="100" required @disabled(! $canManage)>
                        </div>
                        <div>
                            <label class="admin-label" for="info-wa-text">Link Text</label>
                            <input id="info-wa-text" class="{{ $fieldClass }}" name="info[whatsapp_link_text]" value="{{ old('info.whatsapp_link_text', data_get($content, 'info.whatsapp_link_text', 'Chat on WhatsApp')) }}" maxlength="100" required @disabled(! $canManage)>
                        </div>
                        <div>
                            <label class="admin-label" for="info-wa-url">WhatsApp Direct URL</label>
                            <input id="info-wa-url" class="{{ $fieldClass }}" name="info[whatsapp_url]" value="{{ old('info.whatsapp_url', data_get($content, 'info.whatsapp_url')) }}" maxlength="255" required @disabled(! $canManage)>
                        </div>
                    </div>
                </div>

                {{-- Social Channels --}}
                <div class="rounded-2xl border border-slate-200 p-4 space-y-4">
                    <div>
                        <label class="admin-label" for="info-follow-head">Social Section Heading</label>
                        <input id="info-follow-head" class="{{ $fieldClass }}" name="info[follow_heading]" value="{{ old('info.follow_heading', data_get($content, 'info.follow_heading', 'Follow us')) }}" maxlength="255" required @disabled(! $canManage)>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label class="admin-label" for="info-ig">Instagram Profile URL</label>
                            <input id="info-ig" class="{{ $fieldClass }}" name="info[instagram_url]" value="{{ old('info.instagram_url', data_get($content, 'info.instagram_url')) }}" maxlength="255" @disabled(! $canManage)>
                        </div>
                        <div>
                            <label class="admin-label" for="info-fb">Facebook Profile URL</label>
                            <input id="info-fb" class="{{ $fieldClass }}" name="info[facebook_url]" value="{{ old('info.facebook_url', data_get($content, 'info.facebook_url')) }}" maxlength="255" @disabled(! $canManage)>
                        </div>
                        <div>
                            <label class="admin-label" for="info-yt">YouTube Channel URL</label>
                            <input id="info-yt" class="{{ $fieldClass }}" name="info[youtube_url]" value="{{ old('info.youtube_url', data_get($content, 'info.youtube_url')) }}" maxlength="255" @disabled(! $canManage)>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="admin-label" for="info-footer-note">Footer Reminder / Note</label>
                    <textarea id="info-footer-note" class="{{ $textareaClass }} h-20" name="info[footer_note]" maxlength="1000" required @disabled(! $canManage)>{{ old('info.footer_note', data_get($content, 'info.footer_note')) }}</textarea>
                </div>
            </div>
        </section>

        {{-- Right Card: Send Us a Message Form Settings --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Contact Form</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Form Content & Notices</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">Labels, submit button text, and privacy notice shown on the enquiry form.</p>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="admin-label" for="form-heading">Form Heading</label>
                        <input id="form-heading" class="{{ $fieldClass }}" name="form[heading]" value="{{ old('form.heading', data_get($content, 'form.heading', 'Send us a message')) }}" maxlength="255" required @disabled(! $canManage)>
                    </div>
                    <div>
                        <label class="admin-label" for="form-subheading">Form Subtitle / Requirement Notice</label>
                        <input id="form-subheading" class="{{ $fieldClass }}" name="form[subheading]" value="{{ old('form.subheading', data_get($content, 'form.subheading', 'Fields marked * are required.')) }}" maxlength="500" required @disabled(! $canManage)>
                    </div>
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="admin-label" for="form-btn">Submit Button Text</label>
                        <input id="form-btn" class="{{ $fieldClass }}" name="form[submit_btn_text]" value="{{ old('form.submit_btn_text', data_get($content, 'form.submit_btn_text', 'Send message')) }}" maxlength="100" required @disabled(! $canManage)>
                    </div>
                    <div>
                        <label class="admin-label" for="form-privacy">Privacy Notice Text</label>
                        <input id="form-privacy" class="{{ $fieldClass }}" name="form[privacy_notice]" value="{{ old('form.privacy_notice', data_get($content, 'form.privacy_notice', 'We only use your details to respond to your enquiry. See our Privacy Policy.')) }}" maxlength="1000" required @disabled(! $canManage)>
                    </div>
                </div>
            </div>
        </section>

        {{-- SEO Settings --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Search Engines</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">SEO Settings</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">Meta tags for Google and social previews.</p>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="seo-title">Meta Title</label>
                    <input id="seo-title" class="{{ $fieldClass }}" name="seo[meta_title]" value="{{ old('seo.meta_title', data_get($content, 'seo.meta_title', 'Contact Us | NextPlay Sportswear')) }}" maxlength="255" @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="seo-desc">Meta Description</label>
                    <textarea id="seo-desc" class="{{ $textareaClass }} h-20" name="seo[meta_description]" maxlength="500" @disabled(! $canManage)>{{ old('seo.meta_description', data_get($content, 'seo.meta_description', 'Questions about a product or order? Send us a message.')) }}</textarea>
                </div>
            </div>
        </section>

        <div class="flex items-center justify-end gap-3 pb-8">
            <a href="{{ route('contact') }}" target="_blank" rel="noopener noreferrer" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">
                Preview
            </a>
            @if($canManage)
                <button type="submit" class="rounded-xl bg-brand-red px-6 py-2.5 text-sm font-bold text-white hover:bg-brand-red-dark">
                    Save Contact Us Page
                </button>
            @endif
        </div>
    </form>
</x-layouts.admin>

