<x-layouts.admin
    title="Artwork Guidelines Page"
    :storefront-url="route('artwork-guidelines')"
>
    @php
        $fieldClass = 'admin-input';
        $textareaClass = 'admin-input np-content-textarea';
    @endphp

    <form method="POST" action="{{ route('admin.artwork-guidelines-page.update') }}" class="np-storefront-editor space-y-6">
        @csrf
        @method('PUT')

        <x-admin.storefront-editor-toolbar
            description="Manage every text, table, specification, and SEO tag on the Artwork Guidelines page. The 6-section layout and coded jersey vector mockups remain preserved."
            :preview-url="route('artwork-guidelines')"
            preview-label="Preview Artwork Guidelines"
            save-label="Save Artwork Guidelines"
            :can-manage="$canManage"
        />

        @if(session('status'))
            <div data-admin-flash class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
        @endif

        @if($errors->has('artwork_guidelines'))
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{{ $errors->first('artwork_guidelines') }}</div>
        @endif

        @if($errors->any() && ! $errors->has('artwork_guidelines'))
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-900">Please correct the highlighted fields and save again.</div>
        @endif

        {{-- Hero Header --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Header</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Page Hero</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">The title and description banner at the top of the page.</p>
            </div>
            <div class="grid gap-5 p-5 sm:p-6 lg:grid-cols-2">
                <div>
                    <label class="admin-label" for="hero-eyebrow">Eyebrow</label>
                    <input id="hero-eyebrow" class="{{ $fieldClass }}" name="hero[eyebrow]" value="{{ old('hero.eyebrow', data_get($content, 'hero.eyebrow')) }}" maxlength="120" @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="hero-title">Page Title</label>
                    <input id="hero-title" class="{{ $fieldClass }}" name="hero[title]" value="{{ old('hero.title', data_get($content, 'hero.title')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>
                <div class="lg:col-span-2">
                    <label class="admin-label" for="hero-description">Description</label>
                    <textarea id="hero-description" class="{{ $textareaClass }} h-20" name="hero[description]" maxlength="1000" required @disabled(! $canManage)>{{ old('hero.description', data_get($content, 'hero.description')) }}</textarea>
                </div>
            </div>
        </section>

        {{-- Section 01: File Formats --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 01</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">File Formats</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">Configure the accepted vector and raster file types table and callout notice.</p>
            </div>
            <div class="space-y-6 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="section-01-title">Section Title</label>
                    <input id="section-01-title" class="{{ $fieldClass }}" name="section_01[title]" value="{{ old('section_01.title', data_get($content, 'section_01.title')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>

                <div class="space-y-4">
                    <p class="text-xs font-black uppercase tracking-[.2em] text-slate-400">Accepted Format Rows</p>
                    @foreach(data_get($content, 'section_01.formats', []) as $index => $format)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="grid gap-4 sm:grid-cols-3">
                                <div>
                                    <label class="admin-label text-xs">Format Name</label>
                                    <input class="{{ $fieldClass }} text-sm font-bold" name="section_01[formats][{{ $index }}][name]" value="{{ old("section_01.formats.{$index}.name", $format['name'] ?? '') }}" required @disabled(! $canManage)>
                                </div>
                                <div>
                                    <label class="admin-label text-xs">Badge Label</label>
                                    <input class="{{ $fieldClass }} text-sm" name="section_01[formats][{{ $index }}][badge]" value="{{ old("section_01.formats.{$index}.badge", $format['badge'] ?? '') }}" required @disabled(! $canManage)>
                                </div>
                                <div>
                                    <label class="admin-label text-xs">Badge Color (emerald, slate, amber)</label>
                                    <input class="{{ $fieldClass }} text-sm" name="section_01[formats][{{ $index }}][badge_color]" value="{{ old("section_01.formats.{$index}.badge_color", $format['badge_color'] ?? 'slate') }}" required @disabled(! $canManage)>
                                </div>
                                <div class="sm:col-span-3">
                                    <label class="admin-label text-xs">Description</label>
                                    <textarea class="{{ $textareaClass }} h-16 text-sm" name="section_01[formats][{{ $index }}][description]" required @disabled(! $canManage)>{{ old("section_01.formats.{$index}.description", $format['description'] ?? '') }}</textarea>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div>
                    <label class="admin-label" for="section-01-notice">Notice Callout (Warning about low-resolution raster formats)</label>
                    <textarea id="section-01-notice" class="{{ $textareaClass }} h-20" name="section_01[notice]" maxlength="1000" required @disabled(! $canManage)>{{ old('section_01.notice', data_get($content, 'section_01.notice')) }}</textarea>
                </div>
            </div>
        </section>

        {{-- Section 02: Artwork Preparation Checklist --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 02</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Artwork Preparation Checklist</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">Checklist items for preparing production-ready artwork files.</p>
            </div>
            <div class="space-y-6 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="section-02-title">Section Title</label>
                    <input id="section-02-title" class="{{ $fieldClass }}" name="section_02[title]" value="{{ old('section_02.title', data_get($content, 'section_02.title')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>

                <div class="space-y-3">
                    <p class="text-xs font-black uppercase tracking-[.2em] text-slate-400">Checklist Items</p>
                    @foreach(data_get($content, 'section_02.checklist', []) as $index => $item)
                        <div>
                            <div class="flex items-center gap-3">
                                <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-black text-emerald-700">✓</span>
                                <input class="{{ $fieldClass }}" name="section_02[checklist][{{ $index }}]" value="{{ old("section_02.checklist.{$index}", $item) }}" maxlength="500" required @disabled(! $canManage)>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div>
                    <label class="admin-label" for="section-02-callout">Bottom Callout (PPI vs DPI Note)</label>
                    <textarea id="section-02-callout" class="{{ $textareaClass }} h-20" name="section_02[callout]" maxlength="1000" required @disabled(! $canManage)>{{ old('section_02.callout', data_get($content, 'section_02.callout')) }}</textarea>
                </div>
            </div>
        </section>

        {{-- Section 03: Confirm Size & Placement --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 03</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Confirm Size & Placement</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">Placement dimensions and guidance text. The coded jersey vector mockups render below this content.</p>
            </div>
            <div class="grid gap-5 p-5 sm:p-6 lg:grid-cols-2">
                <div class="lg:col-span-2">
                    <label class="admin-label" for="section-03-title">Section Title</label>
                    <input id="section-03-title" class="{{ $fieldClass }}" name="section_03[title]" value="{{ old('section_03.title', data_get($content, 'section_03.title')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>
                <div class="lg:col-span-2">
                    <label class="admin-label" for="section-03-subtitle">Guidance Subtitle</label>
                    <input id="section-03-subtitle" class="{{ $fieldClass }}" name="section_03[subtitle]" value="{{ old('section_03.subtitle', data_get($content, 'section_03.subtitle')) }}" maxlength="500" required @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="section-03-front-spec">Front Jersey Specification</label>
                    <input id="section-03-front-spec" class="{{ $fieldClass }}" name="section_03[front_spec]" value="{{ old('section_03.front_spec', data_get($content, 'section_03.front_spec')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="section-03-back-spec">Back Jersey Specification</label>
                    <input id="section-03-back-spec" class="{{ $fieldClass }}" name="section_03[back_spec]" value="{{ old('section_03.back_spec', data_get($content, 'section_03.back_spec')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>
                <div class="lg:col-span-2">
                    <label class="admin-label" for="section-03-footnote">Footnote Note</label>
                    <input id="section-03-footnote" class="{{ $fieldClass }}" name="section_03[footnote]" value="{{ old('section_03.footnote', data_get($content, 'section_03.footnote')) }}" maxlength="500" required @disabled(! $canManage)>
                </div>
            </div>
        </section>

        {{-- Section 04: Decoration Methods & File Requirements --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 04</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Decoration Methods & File Requirements</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">Guidance for each decoration method: Sublimation, Embroidery, Screen Printing, and Heat Transfer.</p>
            </div>
            <div class="space-y-6 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="section-04-title">Section Title</label>
                    <input id="section-04-title" class="{{ $fieldClass }}" name="section_04[title]" value="{{ old('section_04.title', data_get($content, 'section_04.title')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>

                <div class="space-y-5">
                    @foreach(data_get($content, 'section_04.methods', []) as $index => $method)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <div class="mb-3 flex items-center justify-between">
                                <span class="text-xs font-black uppercase tracking-wider text-brand-navy">Method #{{ $index + 1 }}</span>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="admin-label text-xs">Method Name</label>
                                    <input class="{{ $fieldClass }} text-sm font-bold" name="section_04[methods][{{ $index }}][name]" value="{{ old("section_04.methods.{$index}.name", $method['name'] ?? '') }}" required @disabled(! $canManage)>
                                </div>
                                <div>
                                    <label class="admin-label text-xs">Colours</label>
                                    <input class="{{ $fieldClass }} text-sm" name="section_04[methods][{{ $index }}][colours]" value="{{ old("section_04.methods.{$index}.colours", $method['colours'] ?? '') }}" required @disabled(! $canManage)>
                                </div>
                                <div>
                                    <label class="admin-label text-xs">Ideal For</label>
                                    <textarea class="{{ $textareaClass }} h-16 text-sm" name="section_04[methods][{{ $index }}][ideal_for]" required @disabled(! $canManage)>{{ old("section_04.methods.{$index}.ideal_for", $method['ideal_for'] ?? '') }}</textarea>
                                </div>
                                <div>
                                    <label class="admin-label text-xs">File Types</label>
                                    <textarea class="{{ $textareaClass }} h-16 text-sm" name="section_04[methods][{{ $index }}][file_types]" required @disabled(! $canManage)>{{ old("section_04.methods.{$index}.file_types", $method['file_types'] ?? '') }}</textarea>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Section 05: Proof Review & Approval --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 05</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Proof Review & Approval</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">What is verified during digital proofing and the written approval requirement.</p>
            </div>
            <div class="space-y-6 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="section-05-title">Section Title</label>
                    <input id="section-05-title" class="{{ $fieldClass }}" name="section_05[title]" value="{{ old('section_05.title', data_get($content, 'section_05.title')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="section-05-intro">Introductory Paragraph</label>
                    <input id="section-05-intro" class="{{ $fieldClass }}" name="section_05[intro]" value="{{ old('section_05.intro', data_get($content, 'section_05.intro')) }}" maxlength="500" required @disabled(! $canManage)>
                </div>

                <div class="space-y-3">
                    <p class="text-xs font-black uppercase tracking-[.2em] text-slate-400">Proof Elements Checklist</p>
                    @foreach(data_get($content, 'section_05.items', []) as $index => $item)
                        <div class="flex items-center gap-3">
                            <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-black text-blue-700">{{ $index + 1 }}</span>
                            <input class="{{ $fieldClass }}" name="section_05[items][{{ $index }}]" value="{{ old("section_05.items.{$index}", $item) }}" maxlength="500" required @disabled(! $canManage)>
                        </div>
                    @endforeach
                </div>

                <div>
                    <label class="admin-label" for="section-05-notice">Notice Callout (Written Approval Requirement)</label>
                    <textarea id="section-05-notice" class="{{ $textareaClass }} h-20" name="section_05[notice]" maxlength="1000" required @disabled(! $canManage)>{{ old('section_05.notice', data_get($content, 'section_05.notice')) }}</textarea>
                </div>
            </div>
        </section>

        {{-- Section 06: Sending Your Files & Contact --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Section 06</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Sending Your Files & Contact</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">File submission instructions, filename convention, and design team link.</p>
            </div>
            <div class="grid gap-5 p-5 sm:p-6 lg:grid-cols-2">
                <div class="lg:col-span-2">
                    <label class="admin-label" for="section-06-title">Section Title</label>
                    <input id="section-06-title" class="{{ $fieldClass }}" name="section_06[title]" value="{{ old('section_06.title', data_get($content, 'section_06.title')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>
                <div class="lg:col-span-2">
                    <label class="admin-label" for="section-06-intro">Introductory Paragraph</label>
                    <input id="section-06-intro" class="{{ $fieldClass }}" name="section_06[intro]" value="{{ old('section_06.intro', data_get($content, 'section_06.intro')) }}" maxlength="500" required @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="section-06-filename-label">Filename Convention Label</label>
                    <input id="section-06-filename-label" class="{{ $fieldClass }}" name="section_06[filename_label]" value="{{ old('section_06.filename_label', data_get($content, 'section_06.filename_label')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="section-06-filename-example">Filename Example Code</label>
                    <input id="section-06-filename-example" class="{{ $fieldClass }}" name="section_06[filename_example]" value="{{ old('section_06.filename_example', data_get($content, 'section_06.filename_example')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>
                <div class="lg:col-span-2">
                    <label class="admin-label" for="section-06-copyright-notice">Copyright & Permission Notice</label>
                    <textarea id="section-06-copyright-notice" class="{{ $textareaClass }} h-20" name="section_06[copyright_notice]" maxlength="1000" required @disabled(! $canManage)>{{ old('section_06.copyright_notice', data_get($content, 'section_06.copyright_notice')) }}</textarea>
                </div>
                <div>
                    <label class="admin-label" for="section-06-cta-text">CTA Link Text</label>
                    <input id="section-06-cta-text" class="{{ $fieldClass }}" name="section_06[cta_text]" value="{{ old('section_06.cta_text', data_get($content, 'section_06.cta_text')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="section-06-cta-url">CTA Link URL</label>
                    <input id="section-06-cta-url" class="{{ $fieldClass }}" name="section_06[cta_url]" value="{{ old('section_06.cta_url', data_get($content, 'section_06.cta_url')) }}" maxlength="255" required @disabled(! $canManage)>
                </div>
            </div>
        </section>

        {{-- SEO Section --}}
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Search Engine Optimization</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">SEO Meta Data</h2>
                <p class="mt-2 text-sm font-semibold text-slate-500">Configure page title and meta description for search engines.</p>
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

