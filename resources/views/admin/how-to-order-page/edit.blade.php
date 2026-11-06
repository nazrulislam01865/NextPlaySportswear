<x-layouts.admin
    title="How to Order Page"
    :storefront-url="route('how-to-order')"
>
    @php
        $fieldClass = 'admin-input';
        $textareaClass = 'admin-input np-content-textarea';
    @endphp

    <form method="POST" action="{{ route('admin.how-to-order-page.update') }}" class="np-storefront-editor space-y-6">
        @csrf
        @method('PUT')

        <x-admin.storefront-editor-toolbar
            description="Manage every text, search mockup, add-to-cart prompt, split delivery comparison, formula, and action links on the How to Order page. The 8-step flow remains fixed."
            :preview-url="route('how-to-order')"
            preview-label="Preview How to Order"
            save-label="Save How to Order Page"
            :can-manage="$canManage"
        />

        @if(session('status'))
            <div data-admin-flash class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
        @endif

        @if($errors->has('how_to_order'))
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{{ $errors->first('how_to_order') }}</div>
        @endif

        @if($errors->any() && ! $errors->has('how_to_order'))
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-900">Please correct the highlighted fields and save again.</div>
        @endif

        {{-- Page Header --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Header</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Page Header</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">The main title and subtitle displayed at the top of the How to Order page.</p>
            </div>
            <div class="grid gap-5 p-5 sm:p-6 lg:grid-cols-2">
                <div>
                    <label class="admin-label" for="header-title">Page Title</label>
                    <input id="header-title" class="{{ $fieldClass }}" name="hero[title]" value="{{ old('hero.title', data_get($content, 'hero.title', 'How to Order')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="header-desc">Subtitle</label>
                    <input id="header-desc" class="{{ $fieldClass }}" name="hero[description]" value="{{ old('hero.description', data_get($content, 'hero.description', 'From finding your product to receiving your custom order.')) }}" maxlength="1000" required @disabled(! $canManage)>
                </div>
            </div>
        </section>

        {{-- Section 01: Find your product --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 01</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Find Your Product</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">Instructions for site navigation, catalog search preview, and browse link.</p>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="sec-01-title">Section Title</label>
                    <input id="sec-01-title" class="{{ $fieldClass }}" name="sections[01][title]" value="{{ old('sections.01.title', data_get($content, 'sections.01.title', 'Find your product')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="sec-01-desc">Description</label>
                    <textarea id="sec-01-desc" class="{{ $textareaClass }} h-20" name="sections[01][description]" maxlength="1000" required @disabled(! $canManage)>{{ old('sections.01.description', data_get($content, 'sections.01.description')) }}</textarea>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="admin-label" for="sec-01-search">Search Mockup Placeholder</label>
                        <input id="sec-01-search" class="{{ $fieldClass }}" name="sections[01][search_placeholder]" value="{{ old('sections.01.search_placeholder', data_get($content, 'sections.01.search_placeholder', 'Search jerseys, caps, bags...')) }}" maxlength="255" required @disabled(! $canManage)>
                    </div>
                    <div>
                        <label class="admin-label" for="sec-01-browse-text">Browse Link Text</label>
                        <input id="sec-01-browse-text" class="{{ $fieldClass }}" name="sections[01][browse_text]" value="{{ old('sections.01.browse_text', data_get($content, 'sections.01.browse_text', 'Browse Products')) }}" maxlength="120" required @disabled(! $canManage)>
                    </div>
                    <div>
                        <label class="admin-label" for="sec-01-browse-url">Browse Link URL</label>
                        <input id="sec-01-browse-url" class="{{ $fieldClass }}" name="sections[01][browse_url]" value="{{ old('sections.01.browse_url', data_get($content, 'sections.01.browse_url', '/products')) }}" maxlength="255" required @disabled(! $canManage)>
                    </div>
                </div>
            </div>
        </section>

        {{-- Section 02: Check the product details --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 02</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Check the Product Details</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">Guidance on checking specifications, materials, and size charts before ordering.</p>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="sec-02-title">Section Title</label>
                    <input id="sec-02-title" class="{{ $fieldClass }}" name="sections[02][title]" value="{{ old('sections.02.title', data_get($content, 'sections.02.title', 'Check the product details')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="sec-02-desc">Description</label>
                    <textarea id="sec-02-desc" class="{{ $textareaClass }} h-20" name="sections[02][description]" maxlength="1000" required @disabled(! $canManage)>{{ old('sections.02.description', data_get($content, 'sections.02.description')) }}</textarea>
                </div>
            </div>
        </section>

        {{-- Section 03: Customise your item step by step --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 03</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Customise Your Item Step by Step</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">Personalization selections, uploading artwork, and the Add to Cart action.</p>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="sec-03-title">Section Title</label>
                    <input id="sec-03-title" class="{{ $fieldClass }}" name="sections[03][title]" value="{{ old('sections.03.title', data_get($content, 'sections.03.title', 'Customise your item step by step')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="sec-03-p1">Paragraph 1 (Options, sizes, artwork upload)</label>
                    <textarea id="sec-03-p1" class="{{ $textareaClass }} h-20" name="sections[03][p1]" maxlength="1000" required @disabled(! $canManage)>{{ old('sections.03.p1', data_get($content, 'sections.03.p1')) }}</textarea>
                </div>
                <div>
                    <label class="admin-label" for="sec-03-p2">Paragraph 2 (Add to Cart instructions)</label>
                    <input id="sec-03-p2" class="{{ $fieldClass }}" name="sections[03][p2]" value="{{ old('sections.03.p2', data_get($content, 'sections.03.p2')) }}" maxlength="1000" required @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="sec-03-btn">Mock Button Label</label>
                    <input id="sec-03-btn" class="{{ $fieldClass }}" name="sections[03][btn_text]" value="{{ old('sections.03.btn_text', data_get($content, 'sections.03.btn_text', 'Add to Cart')) }}" maxlength="120" required @disabled(! $canManage)>
                </div>
            </div>
        </section>

        {{-- Section 04: Review your cart --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 04</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Review Your Cart</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">Cart verification before proceeding to checkout.</p>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="sec-04-title">Section Title</label>
                    <input id="sec-04-title" class="{{ $fieldClass }}" name="sections[04][title]" value="{{ old('sections.04.title', data_get($content, 'sections.04.title', 'Review your cart')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="sec-04-desc">Description</label>
                    <textarea id="sec-04-desc" class="{{ $textareaClass }} h-20" name="sections[04][description]" maxlength="1000" required @disabled(! $canManage)>{{ old('sections.04.description', data_get($content, 'sections.04.description')) }}</textarea>
                </div>
            </div>
        </section>

        {{-- Section 05: Choose contacts and delivery --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 05</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Choose Contacts and Delivery</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">Delivery preference comparison table (Ship Together vs. Ship Separately) and policies.</p>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="sec-05-title">Section Title</label>
                    <input id="sec-05-title" class="{{ $fieldClass }}" name="sections[05][title]" value="{{ old('sections.05.title', data_get($content, 'sections.05.title', 'Choose contacts and delivery')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="sec-05-intro">Lead Paragraph</label>
                    <input id="sec-05-intro" class="{{ $fieldClass }}" name="sections[05][intro]" value="{{ old('sections.05.intro', data_get($content, 'sections.05.intro')) }}" maxlength="1000" required @disabled(! $canManage)>
                </div>

                {{-- Table Fields --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <label class="admin-label text-xs">Option 1 Label</label>
                        <input class="{{ $fieldClass }} mb-3" name="sections[05][ship_together_label]" value="{{ old('sections.05.ship_together_label', data_get($content, 'sections.05.ship_together_label', 'Ship together')) }}" required @disabled(! $canManage)>
                        <label class="admin-label text-xs">Option 1 Description</label>
                        <textarea class="{{ $textareaClass }} h-16 text-sm" name="sections[05][ship_together_desc]" required @disabled(! $canManage)>{{ old('sections.05.ship_together_desc', data_get($content, 'sections.05.ship_together_desc')) }}</textarea>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <label class="admin-label text-xs">Option 2 Label</label>
                        <input class="{{ $fieldClass }} mb-3" name="sections[05][ship_separately_label]" value="{{ old('sections.05.ship_separately_label', data_get($content, 'sections.05.ship_separately_label', 'Ship separately')) }}" required @disabled(! $canManage)>
                        <label class="admin-label text-xs">Option 2 Description</label>
                        <textarea class="{{ $textareaClass }} h-16 text-sm" name="sections[05][ship_separately_desc]" required @disabled(! $canManage)>{{ old('sections.05.ship_separately_desc', data_get($content, 'sections.05.ship_separately_desc')) }}</textarea>
                    </div>
                </div>

                <div>
                    <label class="admin-label" for="sec-05-p1">Paragraph 1 (One order and payment)</label>
                    <input id="sec-05-p1" class="{{ $fieldClass }}" name="sections[05][p1]" value="{{ old('sections.05.p1', data_get($content, 'sections.05.p1')) }}" maxlength="1000" required @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="sec-05-p2">Paragraph 2 (Shipping methods & surcharges)</label>
                    <textarea id="sec-05-p2" class="{{ $textareaClass }} h-20" name="sections[05][p2]" maxlength="1000" required @disabled(! $canManage)>{{ old('sections.05.p2', data_get($content, 'sections.05.p2')) }}</textarea>
                </div>
            </div>
        </section>

        {{-- Section 06: Review and place your order --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 06</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Review and Place Your Order</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">Final checkout overview, payment, and order confirmation.</p>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="sec-06-title">Section Title</label>
                    <input id="sec-06-title" class="{{ $fieldClass }}" name="sections[06][title]" value="{{ old('sections.06.title', data_get($content, 'sections.06.title', 'Review and place your order')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="sec-06-desc">Description</label>
                    <textarea id="sec-06-desc" class="{{ $textareaClass }} h-20" name="sections[06][description]" maxlength="1000" required @disabled(! $canManage)>{{ old('sections.06.description', data_get($content, 'sections.06.description')) }}</textarea>
                </div>
            </div>
        </section>

        {{-- Section 07: Review and approve your artwork --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 07</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Review and Approve Your Artwork</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">Digital proofing procedure and artwork approval requirement.</p>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="sec-07-title">Section Title</label>
                    <input id="sec-07-title" class="{{ $fieldClass }}" name="sections[07][title]" value="{{ old('sections.07.title', data_get($content, 'sections.07.title', 'Review and approve your artwork')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="sec-07-desc">Description</label>
                    <textarea id="sec-07-desc" class="{{ $textareaClass }} h-20" name="sections[07][description]" maxlength="1000" required @disabled(! $canManage)>{{ old('sections.07.description', data_get($content, 'sections.07.description')) }}</textarea>
                </div>
                <div>
                    <label class="admin-label" for="sec-07-notice">Approval Notice Callout</label>
                    <input id="sec-07-notice" class="{{ $fieldClass }}" name="sections[07][notice]" value="{{ old('sections.07.notice', data_get($content, 'sections.07.notice', 'Production begins only after final artwork approval.')) }}" maxlength="1000" required @disabled(! $canManage)>
                </div>
            </div>
        </section>

        {{-- Section 08: Production, dispatch and tracking --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 08</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Production, Dispatch and Tracking</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">Lead time explanation, arrival formula, tracking notice, and bottom navigation links.</p>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="sec-08-title">Section Title</label>
                    <input id="sec-08-title" class="{{ $fieldClass }}" name="sections[08][title]" value="{{ old('sections.08.title', data_get($content, 'sections.08.title', 'Production, dispatch and tracking')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="sec-08-p1">Paragraph 1 (Production clock & shipping timeline)</label>
                    <textarea id="sec-08-p1" class="{{ $textareaClass }} h-20" name="sections[08][p1]" maxlength="1000" required @disabled(! $canManage)>{{ old('sections.08.p1', data_get($content, 'sections.08.p1')) }}</textarea>
                </div>
                <div>
                    <label class="admin-label" for="sec-08-formula">Estimated Arrival Formula</label>
                    <input id="sec-08-formula" class="{{ $fieldClass }} font-bold text-brand-ink" name="sections[08][formula]" value="{{ old('sections.08.formula', data_get($content, 'sections.08.formula')) }}" maxlength="1000" required @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="sec-08-p2">Paragraph 2 (Working days explanation)</label>
                    <textarea id="sec-08-p2" class="{{ $textareaClass }} h-20" name="sections[08][p2]" maxlength="1000" required @disabled(! $canManage)>{{ old('sections.08.p2', data_get($content, 'sections.08.p2')) }}</textarea>
                </div>
                <div>
                    <label class="admin-label" for="sec-08-p3">Paragraph 3 (Tracking number guidance)</label>
                    <textarea id="sec-08-p3" class="{{ $textareaClass }} h-20" name="sections[08][p3]" maxlength="1000" required @disabled(! $canManage)>{{ old('sections.08.p3', data_get($content, 'sections.08.p3')) }}</textarea>
                </div>

                <p class="text-xs font-black uppercase tracking-[.2em] text-slate-400">Bottom Action Bar Links</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="admin-label text-xs">Link 1 Label</label>
                        <input class="{{ $fieldClass }} mb-2" name="sections[08][link_1_text]" value="{{ old('sections.08.link_1_text', data_get($content, 'sections.08.link_1_text', 'Artwork Guidelines')) }}" required @disabled(! $canManage)>
                        <label class="admin-label text-xs">Link 1 URL</label>
                        <input class="{{ $fieldClass }}" name="sections[08][link_1_url]" value="{{ old('sections.08.link_1_url', data_get($content, 'sections.08.link_1_url', '/artwork-guidelines')) }}" required @disabled(! $canManage)>
                    </div>
                    <div>
                        <label class="admin-label text-xs">Link 2 Label</label>
                        <input class="{{ $fieldClass }} mb-2" name="sections[08][link_2_text]" value="{{ old('sections.08.link_2_text', data_get($content, 'sections.08.link_2_text', 'Shipping & Delivery')) }}" required @disabled(! $canManage)>
                        <label class="admin-label text-xs">Link 2 URL</label>
                        <input class="{{ $fieldClass }}" name="sections[08][link_2_url]" value="{{ old('sections.08.link_2_url', data_get($content, 'sections.08.link_2_url', '/shipping-delivery')) }}" required @disabled(! $canManage)>
                    </div>
                    <div>
                        <label class="admin-label text-xs">Track Button Label</label>
                        <input class="{{ $fieldClass }} mb-2" name="sections[08][btn_track_text]" value="{{ old('sections.08.btn_track_text', data_get($content, 'sections.08.btn_track_text', 'Track Your Order')) }}" required @disabled(! $canManage)>
                        <label class="admin-label text-xs">Track Button URL</label>
                        <input class="{{ $fieldClass }}" name="sections[08][btn_track_url]" value="{{ old('sections.08.btn_track_url', data_get($content, 'sections.08.btn_track_url', '/track-order')) }}" required @disabled(! $canManage)>
                    </div>
                    <div>
                        <label class="admin-label text-xs">Link 3 Label</label>
                        <input class="{{ $fieldClass }} mb-2" name="sections[08][link_3_text]" value="{{ old('sections.08.link_3_text', data_get($content, 'sections.08.link_3_text', 'Contact Us')) }}" required @disabled(! $canManage)>
                        <label class="admin-label text-xs">Link 3 URL</label>
                        <input class="{{ $fieldClass }}" name="sections[08][link_3_url]" value="{{ old('sections.08.link_3_url', data_get($content, 'sections.08.link_3_url', '/contact-us')) }}" required @disabled(! $canManage)>
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
                    <label class="admin-label" for="seo-meta-title">Meta Title</label>
                    <input id="seo-meta-title" class="{{ $fieldClass }}" name="seo[meta_title]" value="{{ old('seo.meta_title', data_get($content, 'seo.meta_title')) }}" maxlength="255" @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="seo-meta-description">Meta Description</label>
                    <textarea id="seo-meta-description" class="{{ $textareaClass }} h-20" name="seo[meta_description]" maxlength="500" @disabled(! $canManage)>{{ old('seo.meta_description', data_get($content, 'seo.meta_description')) }}</textarea>
                </div>
            </div>
        </section>
    </form>
</x-layouts.admin>

