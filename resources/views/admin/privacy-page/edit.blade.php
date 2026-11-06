<x-layouts.admin
    title="Privacy Policy Page"
    :storefront-url="route('privacy')"
>
    @php
        $fieldClass = 'admin-input';
        $textareaClass = 'admin-input np-content-textarea';
        $sections = data_get($content, 'sections', []);
    @endphp

    <form method="POST" action="{{ route('admin.privacy-page.update') }}" class="np-storefront-editor space-y-6">
        @csrf
        @method('PUT')

        <x-admin.storefront-editor-toolbar
            description="Manage data collection policies, usage clauses, sharing practices, user rights, cookies information, and SEO on the Privacy Policy page."
            :preview-url="route('privacy')"
            preview-label="Preview Privacy Policy"
            save-label="Save Privacy Policy Page"
            :can-manage="$canManage"
        />

        @if(session('status'))
            <div data-admin-flash class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
        @endif

        @if($errors->has('privacy_page'))
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{{ $errors->first('privacy_page') }}</div>
        @endif

        @if($errors->any() && ! $errors->has('privacy_page'))
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-900">Please correct the highlighted fields and save again.</div>
        @endif

        {{-- Page Header --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Header</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Page Header</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">The main title, revision date / version, and introductory summary.</p>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="admin-label" for="header-title">Page Title</label>
                        <input id="header-title" class="{{ $fieldClass }}" name="hero[title]" value="{{ old('hero.title', data_get($content, 'hero.title', 'Privacy Policy')) }}" maxlength="255" required @disabled(! $canManage)>
                    </div>
                    <div>
                        <label class="admin-label" for="header-updated-label">Last Updated Label</label>
                        <input id="header-updated-label" class="{{ $fieldClass }}" name="hero[updated_label]" value="{{ old('hero.updated_label', data_get($content, 'hero.updated_label', 'Last updated:')) }}" maxlength="100" @disabled(! $canManage)>
                    </div>
                    <div>
                        <label class="admin-label" for="header-sub">Last Updated Date / Version</label>
                        <input id="header-sub" class="{{ $fieldClass }}" name="hero[subtitle]" value="{{ old('hero.subtitle', data_get($content, 'hero.subtitle', 'Last updated: 22 June 2026')) }}" maxlength="255" required @disabled(! $canManage)>
                    </div>
                </div>
                <div>
                    <label class="admin-label" for="header-desc">Introductory Paragraph</label>
                    <textarea id="header-desc" class="{{ $textareaClass }} h-20" name="hero[description]" maxlength="1000" required @disabled(! $canManage)>{{ old('hero.description', data_get($content, 'hero.description', 'This policy explains how NextPlay Sportswear handles your personal information when you use our website and services.')) }}</textarea>
                </div>
            </div>
        </section>

        {{-- Document navigation: all visible privacy-page labels are managed here. --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Navigation</p>
                <h2 class="mt-2 text-xl font-black text-brand-ink">Breadcrumb &amp; Return Link</h2>
            </div>
            <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                <div>
                    <label class="admin-label" for="privacy-home-label">Home Breadcrumb Text</label>
                    <input id="privacy-home-label" class="{{ $fieldClass }}" name="navigation[home_label]" value="{{ old('navigation.home_label', data_get($content, 'navigation.home_label', 'Home')) }}" maxlength="100" required @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="privacy-current-label">Current Page Breadcrumb Text (optional)</label>
                    <input id="privacy-current-label" class="{{ $fieldClass }}" name="navigation[breadcrumb_label]" value="{{ old('navigation.breadcrumb_label', data_get($content, 'navigation.breadcrumb_label', '')) }}" placeholder="Uses Page Title when blank" maxlength="100" @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="privacy-back-label">Back to Top Link Text</label>
                    <input id="privacy-back-label" class="{{ $fieldClass }}" name="navigation[back_to_top_label]" value="{{ old('navigation.back_to_top_label', data_get($content, 'navigation.back_to_top_label', '↑ Back to top')) }}" maxlength="100" required @disabled(! $canManage)>
                </div>
            </div>
        </section>

        {{-- Privacy Sections --}}
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
                    <input id="seo-title" class="{{ $fieldClass }}" name="seo[meta_title]" value="{{ old('seo.meta_title', data_get($content, 'seo.meta_title', 'Privacy Policy | NextPlay Sportswear')) }}" maxlength="255" @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="seo-desc">Meta Description</label>
                    <textarea id="seo-desc" class="{{ $textareaClass }} h-20" name="seo[meta_description]" maxlength="500" @disabled(! $canManage)>{{ old('seo.meta_description', data_get($content, 'seo.meta_description', 'Learn how NextPlay Sportswear collects, uses, and protects your personal information and data.')) }}</textarea>
                </div>
            </div>
        </section>

        <div class="flex items-center justify-end gap-3 pb-8">
            <a href="{{ route('privacy') }}" target="_blank" rel="noopener noreferrer" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">
                Preview
            </a>
            @if($canManage)
                <button type="submit" class="rounded-xl bg-brand-red px-6 py-2.5 text-sm font-bold text-white hover:bg-brand-red-dark">
                    Save Privacy Policy Page
                </button>
            @endif
        </div>
    </form>
</x-layouts.admin>

