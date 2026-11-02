<x-layouts.admin
    title="Shipping & Delivery Page"
    :storefront-url="route('shipping')"
>
    @php
        $fieldClass = 'admin-input';
        $textareaClass = 'admin-input np-content-textarea';
    @endphp

    <form method="POST" action="{{ route('admin.shipping-delivery-page.update') }}" class="np-storefront-editor space-y-6">
        @csrf
        @method('PUT')

        <x-admin.storefront-editor-toolbar
            description="Manage every text, delivery option, calculation formula, and guideline on the Shipping & Delivery page. The approved 8-section layout remains fixed."
            :preview-url="route('shipping')"
            preview-label="Preview Shipping & Delivery"
            save-label="Save Shipping & Delivery Page"
            :can-manage="$canManageShippingDeliveryPage"
        />

        @if(session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
        @endif

        @if($errors->has('shipping_delivery_page'))
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{{ $errors->first('shipping_delivery_page') }}</div>
        @elseif($errors->any())
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-900">Please correct the highlighted fields and save again.</div>
        @endif

        {{-- Page Header --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Header</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Page Header</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">The main title and subtitle displayed at the top of the Shipping & Delivery page.</p>
            </div>
            <div class="grid gap-5 p-5 sm:p-6 lg:grid-cols-2">
                <div>
                    <label class="admin-label" for="header-title">Page Title</label>
                    <input id="header-title" class="{{ $fieldClass }}" name="display_header[title]" value="{{ old('display_header.title', data_get($shippingDelivery, 'display_header.title', 'Shipping & Delivery')) }}" maxlength="255" required @disabled(! $canManageShippingDeliveryPage)>
                </div>
                <div>
                    <label class="admin-label" for="header-subtitle">Subtitle</label>
                    <input id="header-subtitle" class="{{ $fieldClass }}" name="display_header[subtitle]" value="{{ old('display_header.subtitle', data_get($shippingDelivery, 'display_header.subtitle', 'Understand production times, delivery options and tracking before you order.')) }}" maxlength="1000" required @disabled(! $canManageShippingDeliveryPage)>
                </div>
            </div>
        </section>

        {{-- Section 01: Before you order --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 01</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Before You Order</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">Initial checklist guidance and event deadline warnings.</p>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="sec-01-title">Section Title</label>
                    <input id="sec-01-title" class="{{ $fieldClass }}" name="sections[01][title]" value="{{ old('sections.01.title', data_get($shippingDelivery, 'sections.01.title', 'Before you order')) }}" maxlength="255" required @disabled(! $canManageShippingDeliveryPage)>
                </div>
                <div>
                    <label class="admin-label" for="sec-01-p1">Paragraph 1 (Estimates & Options)</label>
                    <textarea id="sec-01-p1" class="{{ $textareaClass }} h-20" name="sections[01][p1]" maxlength="1000" required @disabled(! $canManageShippingDeliveryPage)>{{ old('sections.01.p1', data_get($shippingDelivery, 'sections.01.p1')) }}</textarea>
                </div>
                <div>
                    <label class="admin-label" for="sec-01-p2">Paragraph 2 (Deadline Notice)</label>
                    <textarea id="sec-01-p2" class="{{ $textareaClass }} h-20" name="sections[01][p2]" maxlength="1000" @disabled(! $canManageShippingDeliveryPage)>{{ old('sections.01.p2', data_get($shippingDelivery, 'sections.01.p2')) }}</textarea>
                </div>
            </div>
        </section>

        {{-- Section 02: Production and shipping are separate --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 02</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Production and Shipping are Separate</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">Explain the distinction between manufacture and courier delivery times.</p>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="sec-02-title">Section Title</label>
                    <input id="sec-02-title" class="{{ $fieldClass }}" name="sections[02][title]" value="{{ old('sections.02.title', data_get($shippingDelivery, 'sections.02.title', 'Production and shipping are separate')) }}" maxlength="255" required @disabled(! $canManageShippingDeliveryPage)>
                </div>
                <div>
                    <label class="admin-label" for="sec-02-p1">Lead Paragraph</label>
                    <textarea id="sec-02-p1" class="{{ $textareaClass }} h-20" name="sections[02][p1]" maxlength="1000" required @disabled(! $canManageShippingDeliveryPage)>{{ old('sections.02.p1', data_get($shippingDelivery, 'sections.02.p1')) }}</textarea>
                </div>
                <div>
                    <label class="admin-label" for="sec-02-formula">Calculation Formula Callout</label>
                    <input id="sec-02-formula" class="{{ $fieldClass }} font-bold text-brand-ink" name="sections[02][formula]" value="{{ old('sections.02.formula', data_get($shippingDelivery, 'sections.02.formula')) }}" maxlength="1000" required @disabled(! $canManageShippingDeliveryPage)>
                </div>
                <div>
                    <label class="admin-label" for="sec-02-p2">Footnote Paragraph</label>
                    <input id="sec-02-p2" class="{{ $fieldClass }}" name="sections[02][p2]" value="{{ old('sections.02.p2', data_get($shippingDelivery, 'sections.02.p2')) }}" maxlength="1000" @disabled(! $canManageShippingDeliveryPage)>
                </div>
            </div>
        </section>

        {{-- Section 03: Choose your shipping option --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 03</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Choose Your Shipping Option</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">The options table comparing Regular and Express delivery services.</p>
            </div>
            <div class="space-y-6 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="sec-03-title">Section Title</label>
                    <input id="sec-03-title" class="{{ $fieldClass }}" name="sections[03][title]" value="{{ old('sections.03.title', data_get($shippingDelivery, 'sections.03.title', 'Choose your shipping option')) }}" maxlength="255" required @disabled(! $canManageShippingDeliveryPage)>
                </div>

                <div class="space-y-4">
                    <p class="text-xs font-black uppercase tracking-[.2em] text-slate-400">Shipping Options Table</p>
                    @foreach(data_get($shippingDelivery, 'sections.03.options', []) as $index => $opt)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="grid gap-4 sm:grid-cols-3">
                                <div>
                                    <label class="admin-label text-xs">Option Name</label>
                                    <input class="{{ $fieldClass }} text-sm font-bold" name="sections[03][options][{{ $index }}][name]" value="{{ old("sections.03.options.{$index}.name", $opt['name'] ?? '') }}" required @disabled(! $canManageShippingDeliveryPage)>
                                </div>
                                <div>
                                    <label class="admin-label text-xs">What to expect</label>
                                    <input class="{{ $fieldClass }} text-sm" name="sections[03][options][{{ $index }}][what_to_expect]" value="{{ old("sections.03.options.{$index}.what_to_expect", $opt['what_to_expect'] ?? '') }}" required @disabled(! $canManageShippingDeliveryPage)>
                                </div>
                                <div>
                                    <label class="admin-label text-xs">Timing Guidance</label>
                                    <input class="{{ $fieldClass }} text-sm" name="sections[03][options][{{ $index }}][timing]" value="{{ old("sections.03.options.{$index}.timing", $opt['timing'] ?? '') }}" required @disabled(! $canManageShippingDeliveryPage)>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="admin-label" for="sec-03-note1">Table Note 1</label>
                        <textarea id="sec-03-note1" class="{{ $textareaClass }} h-20" name="sections[03][note_1]" maxlength="1000" required @disabled(! $canManageShippingDeliveryPage)>{{ old('sections.03.note_1', data_get($shippingDelivery, 'sections.03.note_1')) }}</textarea>
                    </div>
                    <div>
                        <label class="admin-label" for="sec-03-note2">Table Note 2</label>
                        <textarea id="sec-03-note2" class="{{ $textareaClass }} h-20" name="sections[03][note_2]" maxlength="1000" required @disabled(! $canManageShippingDeliveryPage)>{{ old('sections.03.note_2', data_get($shippingDelivery, 'sections.03.note_2')) }}</textarea>
                    </div>
                </div>
            </div>
        </section>

        {{-- Section 04: How working days are counted --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 04</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">How Working Days are Counted</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">Explanation of working days, weekends, and regional holiday calendars.</p>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="sec-04-title">Section Title</label>
                    <input id="sec-04-title" class="{{ $fieldClass }}" name="sections[04][title]" value="{{ old('sections.04.title', data_get($shippingDelivery, 'sections.04.title', 'How working days are counted')) }}" maxlength="255" required @disabled(! $canManageShippingDeliveryPage)>
                </div>
                <div>
                    <label class="admin-label" for="sec-04-p1">Guidance Paragraph</label>
                    <textarea id="sec-04-p1" class="{{ $textareaClass }} h-24" name="sections[04][p1]" maxlength="1000" required @disabled(! $canManageShippingDeliveryPage)>{{ old('sections.04.p1', data_get($shippingDelivery, 'sections.04.p1')) }}</textarea>
                </div>
            </div>
        </section>

        {{-- Section 05: Why we show a delivery range --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 05</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Why We Show a Delivery Range</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">Delays explanation and estimated date callout.</p>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="sec-05-title">Section Title</label>
                    <input id="sec-05-title" class="{{ $fieldClass }}" name="sections[05][title]" value="{{ old('sections.05.title', data_get($shippingDelivery, 'sections.05.title', 'Why we show a delivery range')) }}" maxlength="255" required @disabled(! $canManageShippingDeliveryPage)>
                </div>
                <div>
                    <label class="admin-label" for="sec-05-p1">Paragraph 1 (Transit variations & customs)</label>
                    <textarea id="sec-05-p1" class="{{ $textareaClass }} h-20" name="sections[05][p1]" maxlength="1000" required @disabled(! $canManageShippingDeliveryPage)>{{ old('sections.05.p1', data_get($shippingDelivery, 'sections.05.p1')) }}</textarea>
                </div>
                <div>
                    <label class="admin-label" for="sec-05-p2">Paragraph 2 (Late shipment procedure)</label>
                    <textarea id="sec-05-p2" class="{{ $textareaClass }} h-20" name="sections[05][p2]" maxlength="1000" @disabled(! $canManageShippingDeliveryPage)>{{ old('sections.05.p2', data_get($shippingDelivery, 'sections.05.p2')) }}</textarea>
                </div>
                <div>
                    <label class="admin-label" for="sec-05-callout">Information Callout</label>
                    <input id="sec-05-callout" class="{{ $fieldClass }}" name="sections[05][callout]" value="{{ old('sections.05.callout', data_get($shippingDelivery, 'sections.05.callout')) }}" maxlength="1000" required @disabled(! $canManageShippingDeliveryPage)>
                </div>
            </div>
        </section>

        {{-- Section 06: Track your order --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 06</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Track Your Order</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">Tracking instructions, 3-step numbered guide, and tracking button link.</p>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="sec-06-title">Section Title</label>
                    <input id="sec-06-title" class="{{ $fieldClass }}" name="sections[06][title]" value="{{ old('sections.06.title', data_get($shippingDelivery, 'sections.06.title', 'Track your order')) }}" maxlength="255" required @disabled(! $canManageShippingDeliveryPage)>
                </div>
                <div>
                    <label class="admin-label" for="sec-06-p1">Introductory Paragraph</label>
                    <textarea id="sec-06-p1" class="{{ $textareaClass }} h-20" name="sections[06][p1]" maxlength="1000" required @disabled(! $canManageShippingDeliveryPage)>{{ old('sections.06.p1', data_get($shippingDelivery, 'sections.06.p1')) }}</textarea>
                </div>

                <div class="space-y-3">
                    <p class="text-xs font-black uppercase tracking-[.2em] text-slate-400">Step Instructions</p>
                    @foreach(data_get($shippingDelivery, 'sections.06.steps', []) as $index => $step)
                        <div class="flex items-center gap-3">
                            <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-brand-red text-xs font-black text-white">{{ $index + 1 }}</span>
                            <input class="{{ $fieldClass }}" name="sections[06][steps][{{ $index }}]" value="{{ old("sections.06.steps.{$index}", $step) }}" maxlength="500" required @disabled(! $canManageShippingDeliveryPage)>
                        </div>
                    @endforeach
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="admin-label" for="sec-06-btn-text">Button Label</label>
                        <input id="sec-06-btn-text" class="{{ $fieldClass }}" name="sections[06][btn_text]" value="{{ old('sections.06.btn_text', data_get($shippingDelivery, 'sections.06.btn_text', 'Track Your Order')) }}" maxlength="120" required @disabled(! $canManageShippingDeliveryPage)>
                    </div>
                    <div>
                        <label class="admin-label" for="sec-06-btn-url">Button URL</label>
                        <input id="sec-06-btn-url" class="{{ $fieldClass }}" name="sections[06][btn_url]" value="{{ old('sections.06.btn_url', data_get($shippingDelivery, 'sections.06.btn_url', '/track-order')) }}" maxlength="255" required @disabled(! $canManageShippingDeliveryPage)>
                    </div>
                </div>
            </div>
        </section>

        {{-- Section 07: Orders with multiple products --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 07</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Orders with Multiple Products</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">Rules for split shipments, combined dispatches, and multiple recipient addresses.</p>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="sec-07-title">Section Title</label>
                    <input id="sec-07-title" class="{{ $fieldClass }}" name="sections[07][title]" value="{{ old('sections.07.title', data_get($shippingDelivery, 'sections.07.title', 'Orders with multiple products')) }}" maxlength="255" required @disabled(! $canManageShippingDeliveryPage)>
                </div>
                <div>
                    <label class="admin-label" for="sec-07-p1">Guidance Paragraph</label>
                    <textarea id="sec-07-p1" class="{{ $textareaClass }} h-24" name="sections[07][p1]" maxlength="1000" required @disabled(! $canManageShippingDeliveryPage)>{{ old('sections.07.p1', data_get($shippingDelivery, 'sections.07.p1')) }}</textarea>
                </div>
            </div>
        </section>

        {{-- Section 08: Check your delivery details --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 08</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Check Your Delivery Details</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">Address accuracy requirements and customer support contact link.</p>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="sec-08-title">Section Title</label>
                    <input id="sec-08-title" class="{{ $fieldClass }}" name="sections[08][title]" value="{{ old('sections.08.title', data_get($shippingDelivery, 'sections.08.title', 'Check your delivery details')) }}" maxlength="255" required @disabled(! $canManageShippingDeliveryPage)>
                </div>
                <div>
                    <label class="admin-label" for="sec-08-p1">Guidance Paragraph</label>
                    <textarea id="sec-08-p1" class="{{ $textareaClass }} h-20" name="sections[08][p1]" maxlength="1000" required @disabled(! $canManageShippingDeliveryPage)>{{ old('sections.08.p1', data_get($shippingDelivery, 'sections.08.p1')) }}</textarea>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="admin-label" for="sec-08-help">Help Prompt</label>
                        <input id="sec-08-help" class="{{ $fieldClass }}" name="sections[08][help_label]" value="{{ old('sections.08.help_label', data_get($shippingDelivery, 'sections.08.help_label', 'Need help with a delivery?')) }}" maxlength="255" required @disabled(! $canManageShippingDeliveryPage)>
                    </div>
                    <div>
                        <label class="admin-label" for="sec-08-contact-text">Contact Link Text</label>
                        <input id="sec-08-contact-text" class="{{ $fieldClass }}" name="sections[08][contact_text]" value="{{ old('sections.08.contact_text', data_get($shippingDelivery, 'sections.08.contact_text', 'Contact us')) }}" maxlength="120" required @disabled(! $canManageShippingDeliveryPage)>
                    </div>
                    <div>
                        <label class="admin-label" for="sec-08-contact-url">Contact Link URL</label>
                        <input id="sec-08-contact-url" class="{{ $fieldClass }}" name="sections[08][contact_url]" value="{{ old('sections.08.contact_url', data_get($shippingDelivery, 'sections.08.contact_url', '/contact-us')) }}" maxlength="255" required @disabled(! $canManageShippingDeliveryPage)>
                    </div>
                </div>
            </div>
        </section>

        {{-- SEO Meta Data --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Search Engine Optimization</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">SEO Meta Data</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">Configure page title and description for search engines.</p>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="seo-title">Meta Title</label>
                    <input id="seo-title" class="{{ $fieldClass }}" name="seo[title]" value="{{ old('seo.title', data_get($shippingDelivery, 'seo.title')) }}" maxlength="255" @disabled(! $canManageShippingDeliveryPage)>
                </div>
                <div>
                    <label class="admin-label" for="seo-description">Meta Description</label>
                    <textarea id="seo-description" class="{{ $textareaClass }} h-20" name="seo[description]" maxlength="500" @disabled(! $canManageShippingDeliveryPage)>{{ old('seo.description', data_get($shippingDelivery, 'seo.description')) }}</textarea>
                </div>
            </div>
        </section>
    </form>
</x-layouts.admin>
