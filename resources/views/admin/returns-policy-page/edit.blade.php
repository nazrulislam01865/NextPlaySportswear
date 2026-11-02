<x-layouts.admin
    title="Returns & Refunds Page"
    :storefront-url="route('returns')"
>
    @php
        $fieldClass = 'admin-input';
        $textareaClass = 'admin-input np-content-textarea';
    @endphp

    <form method="POST" action="{{ route('admin.returns-policy-page.update') }}" class="np-storefront-editor space-y-6">
        @csrf
        @method('PUT')

        <x-admin.storefront-editor-toolbar
            description="Manage every section, policy notice, return checklist, cancellation guidance, and action links on the Returns & Refunds page."
            :preview-url="route('returns')"
            preview-label="Preview Returns & Refunds"
            save-label="Save Returns & Refunds Page"
            :can-manage="$canManage"
        />

        @if(session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
        @endif

        @if($errors->has('returns_policy'))
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{{ $errors->first('returns_policy') }}</div>
        @endif

        @if($errors->any() && ! $errors->has('returns_policy'))
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-900">Please correct the highlighted fields and save again.</div>
        @endif

        {{-- Page Header --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Header</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Page Header</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">The main title and subtitle displayed at the top of the Returns & Refunds page.</p>
            </div>
            <div class="grid gap-5 p-5 sm:p-6 lg:grid-cols-2">
                <div>
                    <label class="admin-label" for="header-title">Page Title</label>
                    <input id="header-title" class="{{ $fieldClass }}" name="hero[title]" value="{{ old('hero.title', data_get($content, 'hero.title', 'Returns & Refunds')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="header-desc">Subtitle</label>
                    <input id="header-desc" class="{{ $fieldClass }}" name="hero[description]" value="{{ old('hero.description', data_get($content, 'hero.description', 'Information about returns, refunds and cancellations.')) }}" maxlength="1000" required @disabled(! $canManage)>
                </div>
            </div>
        </section>

        {{-- Section 01: Overview --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 01</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Overview</h2>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="sec-01-title">Title</label>
                    <input id="sec-01-title" class="{{ $fieldClass }}" name="sections[01][title]" value="{{ old('sections.01.title', data_get($content, 'sections.01.title', 'Overview')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="sec-01-content">Content</label>
                    <textarea id="sec-01-content" class="{{ $textareaClass }} h-24" name="sections[01][content]" maxlength="1000" required @disabled(! $canManage)>{{ old('sections.01.content', data_get($content, 'sections.01.content')) }}</textarea>
                </div>
            </div>
        </section>

        {{-- Section 02: Standard products --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 02</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Standard Products</h2>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="sec-02-title">Title</label>
                    <input id="sec-02-title" class="{{ $fieldClass }}" name="sections[02][title]" value="{{ old('sections.02.title', data_get($content, 'sections.02.title', 'Standard products')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="sec-02-content">Content</label>
                    <textarea id="sec-02-content" class="{{ $textareaClass }} h-24" name="sections[02][content]" maxlength="1000" required @disabled(! $canManage)>{{ old('sections.02.content', data_get($content, 'sections.02.content')) }}</textarea>
                </div>
            </div>
        </section>

        {{-- Section 03: Custom products --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 03</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Custom Products</h2>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="sec-03-title">Title</label>
                    <input id="sec-03-title" class="{{ $fieldClass }}" name="sections[03][title]" value="{{ old('sections.03.title', data_get($content, 'sections.03.title', 'Custom products')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="sec-03-content">Content</label>
                    <textarea id="sec-03-content" class="{{ $textareaClass }} h-24" name="sections[03][content]" maxlength="1000" required @disabled(! $canManage)>{{ old('sections.03.content', data_get($content, 'sections.03.content')) }}</textarea>
                </div>
            </div>
        </section>

        {{-- Section 04: Damaged or incorrect items --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 04</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Damaged or Incorrect Items</h2>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="sec-04-title">Title</label>
                    <input id="sec-04-title" class="{{ $fieldClass }}" name="sections[04][title]" value="{{ old('sections.04.title', data_get($content, 'sections.04.title', 'Damaged or incorrect items')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="sec-04-content">Content</label>
                    <textarea id="sec-04-content" class="{{ $textareaClass }} h-24" name="sections[04][content]" maxlength="1000" required @disabled(! $canManage)>{{ old('sections.04.content', data_get($content, 'sections.04.content')) }}</textarea>
                </div>
            </div>
        </section>

        {{-- Section 05: Requesting a return --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 05</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Requesting a Return (Steps)</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">The 3 bullet steps shown under section 05.</p>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="sec-05-title">Title</label>
                    <input id="sec-05-title" class="{{ $fieldClass }}" name="sections[05][title]" value="{{ old('sections.05.title', data_get($content, 'sections.05.title', 'Requesting a return')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>
                <div class="space-y-3">
                    <label class="admin-label">Return Step Bullets</label>
                    @php
                        $items = old('sections.05.items', data_get($content, 'sections.05.items', [
                            'Contact support with your order details.',
                            'Wait for return instructions before shipping.',
                            'Package approved returns securely and retain tracking details.',
                        ]));
                    @endphp
                    @for($i = 0; $i < 3; $i++)
                        <div>
                            <span class="text-xs font-bold text-slate-500">Step {{ $i + 1 }}</span>
                            <input class="{{ $fieldClass }}" name="sections[05][items][{{ $i }}]" value="{{ $items[$i] ?? '' }}" maxlength="500" required @disabled(! $canManage)>
                        </div>
                    @endfor
                </div>
            </div>
        </section>

        {{-- Section 06: Refunds & exchanges --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 06</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Refunds & Exchanges</h2>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="sec-06-title">Title</label>
                    <input id="sec-06-title" class="{{ $fieldClass }}" name="sections[06][title]" value="{{ old('sections.06.title', data_get($content, 'sections.06.title', 'Refunds & exchanges')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="sec-06-content">Content</label>
                    <textarea id="sec-06-content" class="{{ $textareaClass }} h-24" name="sections[06][content]" maxlength="1000" required @disabled(! $canManage)>{{ old('sections.06.content', data_get($content, 'sections.06.content')) }}</textarea>
                </div>
            </div>
        </section>

        {{-- Section 07: Cancellations --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 07</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Cancellations</h2>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="sec-07-title">Title</label>
                    <input id="sec-07-title" class="{{ $fieldClass }}" name="sections[07][title]" value="{{ old('sections.07.title', data_get($content, 'sections.07.title', 'Cancellations')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="sec-07-content">Content</label>
                    <textarea id="sec-07-content" class="{{ $textareaClass }} h-24" name="sections[07][content]" maxlength="1000" required @disabled(! $canManage)>{{ old('sections.07.content', data_get($content, 'sections.07.content')) }}</textarea>
                </div>
            </div>
        </section>

        {{-- Section 08: Contact us --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 08</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Contact Us</h2>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="sec-08-title">Title</label>
                    <input id="sec-08-title" class="{{ $fieldClass }}" name="sections[08][title]" value="{{ old('sections.08.title', data_get($content, 'sections.08.title', 'Contact us')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="sec-08-content">Content</label>
                    <textarea id="sec-08-content" class="{{ $textareaClass }} h-20" name="sections[08][content]" maxlength="1000" required @disabled(! $canManage)>{{ old('sections.08.content', data_get($content, 'sections.08.content')) }}</textarea>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="admin-label" for="sec-08-btn-text">Button Text</label>
                        <input id="sec-08-btn-text" class="{{ $fieldClass }}" name="sections[08][btn_text]" value="{{ old('sections.08.btn_text', data_get($content, 'sections.08.btn_text', 'Contact Support')) }}" maxlength="120" required @disabled(! $canManage)>
                    </div>
                    <div>
                        <label class="admin-label" for="sec-08-btn-url">Button URL</label>
                        <input id="sec-08-btn-url" class="{{ $fieldClass }}" name="sections[08][btn_url]" value="{{ old('sections.08.btn_url', data_get($content, 'sections.08.btn_url', '/contact-us')) }}" maxlength="255" required @disabled(! $canManage)>
                    </div>
                </div>
            </div>
        </section>

        {{-- Statutory Disclaimer --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Legal Notice</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Statutory Disclaimer</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">Legal disclaimer shown at the bottom of the returns policy page.</p>
            </div>
            <div class="p-5 sm:p-6">
                <label class="admin-label" for="disclaimer">Disclaimer Text</label>
                <input id="disclaimer" class="{{ $fieldClass }}" name="disclaimer" value="{{ old('disclaimer', data_get($content, 'disclaimer', 'This policy does not affect your statutory rights.')) }}" maxlength="1000" required @disabled(! $canManage)>
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
                    <input id="seo-title" class="{{ $fieldClass }}" name="seo[meta_title]" value="{{ old('seo.meta_title', data_get($content, 'seo.meta_title', 'Returns & Refunds | NextPlay Sportswear')) }}" maxlength="255" @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="seo-desc">Meta Description</label>
                    <textarea id="seo-desc" class="{{ $textareaClass }} h-20" name="seo[meta_description]" maxlength="500" @disabled(! $canManage)>{{ old('seo.meta_description', data_get($content, 'seo.meta_description', 'Information about returns, refunds and cancellations.')) }}</textarea>
                </div>
            </div>
        </section>

        <div class="flex items-center justify-end gap-3 pb-8">
            <a href="{{ route('returns') }}" target="_blank" rel="noopener noreferrer" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">
                Preview
            </a>
            @if($canManage)
                <button type="submit" class="rounded-xl bg-brand-red px-6 py-2.5 text-sm font-bold text-white hover:bg-brand-red-dark">
                    Save Returns & Refunds Page
                </button>
            @endif
        </div>
    </form>
</x-layouts.admin>

