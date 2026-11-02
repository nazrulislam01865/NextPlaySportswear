<x-layouts.admin
    title="Terms and Conditions Page"
    :storefront-url="route('terms')"
>
    @php
        $fieldClass = 'admin-input';
        $textareaClass = 'admin-input np-content-textarea';
        $sections = data_get($content, 'sections', []);
    @endphp

    <form method="POST" action="{{ route('admin.terms-page.update') }}" class="np-storefront-editor space-y-6">
        @csrf
        @method('PUT')

        <x-admin.storefront-editor-toolbar
            description="Manage legal clauses, ordering terms, pricing rules, customisation responsibilities, and cancellation policies on the Terms and Conditions page."
            :preview-url="route('terms')"
            preview-label="Preview Terms & Conditions"
            save-label="Save Terms & Conditions Page"
            :can-manage="$canManage"
        />

        @if(session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
        @endif

        @if($errors->has('terms_page'))
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{{ $errors->first('terms_page') }}</div>
        @endif

        @if($errors->any() && ! $errors->has('terms_page'))
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-900">Please correct the highlighted fields and save again.</div>
        @endif

        {{-- Page Header --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Header</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Page Header</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">The main title, subtitle / draft notice, and introductory summary.</p>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="admin-label" for="header-title">Page Title</label>
                        <input id="header-title" class="{{ $fieldClass }}" name="hero[title]" value="{{ old('hero.title', data_get($content, 'hero.title', 'Terms and Conditions')) }}" maxlength="255" required @disabled(! $canManage)>
                    </div>
                    <div>
                        <label class="admin-label" for="header-sub">Document Subtitle / Version</label>
                        <input id="header-sub" class="{{ $fieldClass }}" name="hero[subtitle]" value="{{ old('hero.subtitle', data_get($content, 'hero.subtitle', '')) }}" maxlength="255" @disabled(! $canManage)>
                    </div>
                </div>
                <div>
                    <label class="admin-label" for="header-desc">Introductory Paragraph</label>
                    <textarea id="header-desc" class="{{ $textareaClass }} h-20" name="hero[description]" maxlength="1000" required @disabled(! $canManage)>{{ old('hero.description', data_get($content, 'hero.description', 'These terms explain the ordering process and your responsibilities when shopping with NextPlay Sportswear.')) }}</textarea>
                </div>
            </div>
        </section>

        {{-- Terms Sections --}}
        @foreach($sections as $secKey => $secData)
            <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
                <div class="border-b border-slate-200 p-5 sm:p-6">
                    <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section {{ $secKey }}</p>
                    <h2 class="mt-2 text-xl font-black text-brand-ink">{{ data_get($secData, 'title', 'Section ' . $secKey) }}</h2>
                </div>
                <div class="space-y-4 p-5 sm:p-6">
                    <div>
                        <label class="admin-label" for="sec-{{ $secKey }}-title">Section Title</label>
                        <input id="sec-{{ $secKey }}-title" class="{{ $fieldClass }}" name="sections[{{ $secKey }}][title]" value="{{ old("sections.{$secKey}.title", data_get($secData, 'title')) }}" maxlength="255" required @disabled(! $canManage)>
                    </div>
                    <div>
                        <label class="admin-label" for="sec-{{ $secKey }}-content">Section Content</label>
                        <textarea id="sec-{{ $secKey }}-content" class="{{ $textareaClass }} h-28" name="sections[{{ $secKey }}][content]" maxlength="3000" required @disabled(! $canManage)>{{ old("sections.{$secKey}.content", data_get($secData, 'content')) }}</textarea>
                    </div>
                </div>
            </section>
        @endforeach

        {{-- SEO Settings --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Search Engines</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">SEO Settings</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">Meta tags for Google and search crawlers.</p>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="seo-title">Meta Title</label>
                    <input id="seo-title" class="{{ $fieldClass }}" name="seo[meta_title]" value="{{ old('seo.meta_title', data_get($content, 'seo.meta_title', 'Terms and Conditions | NextPlay Sportswear')) }}" maxlength="255" @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="seo-desc">Meta Description</label>
                    <textarea id="seo-desc" class="{{ $textareaClass }} h-20" name="seo[meta_description]" maxlength="500" @disabled(! $canManage)>{{ old('seo.meta_description', data_get($content, 'seo.meta_description', 'Terms and conditions governing the purchase of standard and custom sportswear, orders, production and returns.')) }}</textarea>
                </div>
            </div>
        </section>

        <div class="flex items-center justify-end gap-3 pb-8">
            <a href="{{ route('terms') }}" target="_blank" rel="noopener noreferrer" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">
                Preview
            </a>
            @if($canManage)
                <button type="submit" class="rounded-xl bg-brand-red px-6 py-2.5 text-sm font-bold text-white hover:bg-brand-red-dark">
                    Save Terms & Conditions Page
                </button>
            @endif
        </div>
    </form>
</x-layouts.admin>

