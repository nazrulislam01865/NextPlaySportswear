@push('admin-page-styles')
    <link rel="stylesheet" href="{{ asset('css/nextplay-payment-editor.css') }}?v=20261103a">
@endpush
<x-layouts.admin title="Payment Information Page" :storefront-url="route('payment-information')">
    @php
        $hero = old('hero', data_get($content, 'hero', []));
        $navigation = old('navigation', data_get($content, 'navigation', []));
        $sections = old('sections', data_get($content, 'sections', []));
        $statusTable = old('status_table', data_get($content, 'status_table', []));
        $refundLink = old('refund_link', data_get($content, 'refund_link', []));
        $support = old('support', data_get($content, 'support', []));
        $seo = old('seo', data_get($content, 'seo', []));
    @endphp

    <form method="POST" action="{{ route('admin.payment-page.update') }}" class="np-storefront-editor space-y-6" id="payment-page-editor">
        @csrf
        @method('PUT')

        <x-admin.storefront-editor-toolbar
            description="Edit all customer-facing copy on the Payment Information page. The public layout, header, footer, payment gateways and checkout flow are unchanged."
            :preview-url="route('payment-information')"
            preview-label="Preview Payment Information"
            save-label="Save Payment Information Page"
            :can-manage="$canManage"
        />

        @if(session('status'))
            <div data-admin-flash class="border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800" role="status">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                <p class="font-semibold">Please correct the following fields before saving:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <section class="border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-bold uppercase tracking-[.16em] text-brand-red">01 · Page header</p>
                <h2 class="mt-2 text-xl font-bold text-brand-ink">Heading &amp; Introduction</h2>
            </div>
            <div class="grid gap-5 p-5 sm:p-6 md:grid-cols-2">
                <div>
                    <label class="admin-label" for="payment-title">Page title</label>
                    <input class="admin-input" id="payment-title" name="hero[title]" value="{{ data_get($hero, 'title') }}" required maxlength="255" @disabled(! $canManage)>
                    @error('hero.title')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="admin-label" for="payment-breadcrumb-home">Home breadcrumb</label>
                    <input class="admin-input" id="payment-breadcrumb-home" name="navigation[home_label]" value="{{ data_get($navigation, 'home_label') }}" required maxlength="100" @disabled(! $canManage)>
                    @error('navigation.home_label')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label class="admin-label" for="payment-introduction">Subtitle</label>
                    <textarea class="admin-input" id="payment-introduction" name="hero[description]" rows="2" required maxlength="1000" @disabled(! $canManage)>{{ data_get($hero, 'description') }}</textarea>
                    @error('hero.description')<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="admin-label" for="payment-breadcrumb-current">Current-page breadcrumb (optional)</label>
                    <input class="admin-input" id="payment-breadcrumb-current" name="navigation[breadcrumb_label]" value="{{ data_get($navigation, 'breadcrumb_label') }}" maxlength="100" placeholder="Uses page title if blank" @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="payment-sidebar-heading">Contents sidebar heading</label>
                    <input class="admin-input" id="payment-sidebar-heading" name="navigation[sidebar_heading]" value="{{ data_get($navigation, 'sidebar_heading') }}" required maxlength="100" @disabled(! $canManage)>
                </div>
            </div>
        </section>

        <section class="border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-bold uppercase tracking-[.16em] text-brand-red">02 · Body content</p>
                <h2 class="mt-2 text-xl font-bold text-brand-ink">Eight Payment Information Sections</h2>
                <p class="mt-2 text-sm text-slate-500">Edit section titles, descriptions and the matching sidebar labels. Section order and numbering follow the approved prototype.</p>
            </div>
            <div class="divide-y divide-slate-200">
                @foreach($sections as $index => $section)
                    <div class="space-y-4 p-5 sm:p-6">
                        <h3 class="text-base font-bold text-brand-ink">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }} · {{ data_get($section, 'title') }}</h3>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="admin-label" for="payment-{{ $index }}-title">Section heading</label>
                                <input class="admin-input" id="payment-{{ $index }}-title" name="sections[{{ $index }}][title]" value="{{ data_get($section, 'title') }}" required maxlength="255" @disabled(! $canManage)>
                                @error("sections.{$index}.title")<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="admin-label" for="payment-{{ $index }}-nav">Sidebar navigation label</label>
                                <input class="admin-input" id="payment-{{ $index }}-nav" name="sections[{{ $index }}][nav_label]" value="{{ data_get($section, 'nav_label') }}" required maxlength="100" @disabled(! $canManage)>
                                @error("sections.{$index}.nav_label")<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div>
                            <label class="admin-label" for="payment-{{ $index }}-content">Section description @if($index === 5)<span class="font-normal text-slate-500">(optional — table content below)</span>@endif</label>
                            <textarea class="admin-input" id="payment-{{ $index }}-content" name="sections[{{ $index }}][content]" maxlength="3000" rows="3" @disabled(! $canManage)>{{ data_get($section, 'content') }}</textarea>
                            @error("sections.{$index}.content")<p class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                        </div>
                        @if($index === 5)
                            <div class="space-y-4 border border-slate-200 bg-slate-50 p-4 sm:p-5" id="payment-status-table-editor" data-editable="{{ $canManage ? '1' : '0' }}" data-max-rows="100" data-max-columns="10">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <h4 class="font-bold text-brand-ink">Payment status table</h4>
                                        <p class="mt-1 text-sm text-slate-500">Edit each row across the table. Add rows or columns without changing the storefront design.</p>
                                    </div>
                                    @if($canManage)
                                        <div class="flex flex-wrap gap-2" role="group" aria-label="Edit payment table">
                                            <button type="button" class="btn btn-white" id="payment-add-row">+ Add row</button>
                                            <button type="button" class="btn btn-white" id="payment-add-column">+ Add column</button>
                                        </div>
                                    @endif
                                </div>
                                <div class="np-payment-admin-table-scroll" role="region" aria-label="Payment status table fields" tabindex="0">
                                    <table class="np-payment-admin-table" id="payment-status-grid">
                                        <caption class="sr-only">Editable payment status information table</caption>
                                        <thead>
                                            <tr id="payment-status-headings">
                                                @foreach(data_get($statusTable, 'columns', []) as $columnIndex => $heading)
                                                    <th scope="col">
                                                        <div class="np-payment-admin-column-title">
                                                            <label for="payment-column-{{ $columnIndex }}">Column {{ $columnIndex + 1 }}</label>
                                                            @if($canManage)
                                                                <button type="button" class="np-payment-admin-remove" data-remove-column="{{ $columnIndex }}" aria-label="Remove column {{ $columnIndex + 1 }}" @disabled(count(data_get($statusTable, 'columns', [])) === 1)>Remove</button>
                                                            @endif
                                                        </div>
                                                        <input class="admin-input" id="payment-column-{{ $columnIndex }}" data-payment-column="{{ $columnIndex }}" name="status_table[columns][{{ $columnIndex }}]" value="{{ $heading }}" maxlength="100" required @disabled(! $canManage)>
                                                    </th>
                                                @endforeach
                                                @if($canManage)<th scope="col" class="np-payment-admin-actions-heading">Actions</th>@endif
                                            </tr>
                                        </thead>
                                        <tbody id="payment-status-rows">
                                            @foreach(data_get($statusTable, 'rows', []) as $rowIndex => $row)
                                                <tr>
                                                    @foreach(data_get($statusTable, 'columns', []) as $columnIndex => $heading)
                                                        <td>
                                                            <label class="sr-only" for="payment-cell-{{ $rowIndex }}-{{ $columnIndex }}">Row {{ $rowIndex + 1 }} · {{ $heading }}</label>
                                                            <input class="admin-input" id="payment-cell-{{ $rowIndex }}-{{ $columnIndex }}" data-payment-row="{{ $rowIndex }}" data-payment-cell="{{ $columnIndex }}" name="status_table[rows][{{ $rowIndex }}][cells][{{ $columnIndex }}]" value="{{ data_get($row, 'cells.'.$columnIndex, '') }}" maxlength="500" @if($columnIndex === 0) required @endif @disabled(! $canManage)>
                                                        </td>
                                                    @endforeach
                                                    @if($canManage)
                                                        <td class="np-payment-admin-row-action"><button type="button" class="np-payment-admin-remove" data-remove-row="{{ $rowIndex }}" aria-label="Remove row {{ $rowIndex + 1 }}" @disabled(count(data_get($statusTable, 'rows', [])) === 1)>Remove</button></td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <p class="text-xs text-slate-500" id="payment-table-help">The first column identifies each row. Optional cells may be left blank. Maximum 100 rows and 10 columns.</p>
                            </div>
                        @endif
                        @if($index === 6)
                            <div class="grid gap-4 border border-slate-200 bg-slate-50 p-4 md:grid-cols-2">
                                <div>
                                    <label class="admin-label" for="payment-refund-text">Returns &amp; Refunds link text</label>
                                    <input class="admin-input" id="payment-refund-text" name="refund_link[label]" value="{{ data_get($refundLink, 'label') }}" maxlength="160" required @disabled(! $canManage)>
                                </div>
                                <div>
                                    <label class="admin-label" for="payment-refund-href">Returns &amp; Refunds destination</label>
                                    <input class="admin-input" id="payment-refund-href" name="refund_link[href]" value="{{ data_get($refundLink, 'href') }}" maxlength="500" required placeholder="/returns-refunds-exchanges" @disabled(! $canManage)>
                                    <p class="mt-1 text-xs text-slate-500">Use an internal URL path starting with /.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        <section class="border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-bold uppercase tracking-[.16em] text-brand-red">03 · Support banner</p>
                <h2 class="mt-2 text-xl font-bold text-brand-ink">Payment Help Call-to-action</h2>
            </div>
            <div class="grid gap-5 p-5 sm:p-6 md:grid-cols-2">
                <div>
                    <label class="admin-label" for="payment-support-title">Banner heading</label>
                    <input class="admin-input" id="payment-support-title" name="support[title]" value="{{ data_get($support, 'title') }}" maxlength="255" required @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="payment-support-description">Banner description</label>
                    <textarea class="admin-input" id="payment-support-description" name="support[description]" rows="2" maxlength="1000" required @disabled(! $canManage)>{{ data_get($support, 'description') }}</textarea>
                </div>
                @foreach(['primary' => 'Contact support button', 'secondary' => 'Track order link'] as $type => $label)
                    <div>
                        <label class="admin-label" for="payment-support-{{ $type }}-label">{{ $label }} text</label>
                        <input class="admin-input" id="payment-support-{{ $type }}-label" name="support[{{ $type }}_label]" value="{{ data_get($support, $type.'_label') }}" required maxlength="100" @disabled(! $canManage)>
                    </div>
                    <div>
                        <label class="admin-label" for="payment-support-{{ $type }}-href">{{ $label }} destination</label>
                        <input class="admin-input" id="payment-support-{{ $type }}-href" name="support[{{ $type }}_href]" value="{{ data_get($support, $type.'_href') }}" required maxlength="500" placeholder="/contact-us" @disabled(! $canManage)>
                        <p class="mt-1 text-xs text-slate-500">Internal URL path only (starts with /).</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-bold uppercase tracking-[.16em] text-brand-red">04 · Search engines</p>
                <h2 class="mt-2 text-xl font-bold text-brand-ink">SEO Settings</h2>
            </div>
            <div class="space-y-4 p-5 sm:p-6">
                <div>
                    <label class="admin-label" for="payment-seo-title">Meta title</label>
                    <input class="admin-input" id="payment-seo-title" name="seo[meta_title]" value="{{ data_get($seo, 'meta_title') }}" maxlength="255" @disabled(! $canManage)>
                </div>
                <div>
                    <label class="admin-label" for="payment-seo-description">Meta description</label>
                    <textarea class="admin-input" id="payment-seo-description" name="seo[meta_description]" maxlength="500" rows="2" @disabled(! $canManage)>{{ data_get($seo, 'meta_description') }}</textarea>
                </div>
            </div>
        </section>

        <div class="flex flex-wrap justify-end gap-3 pb-8">
            <a href="{{ route('payment-information') }}" target="_blank" rel="noopener noreferrer" class="btn btn-white">Preview</a>
            @if($canManage)
                <button type="submit" class="btn btn-red">Save Payment Information Page</button>
            @endif
        </div>
    </form>
    @if($canManage)
        <script defer src="{{ asset('js/nextplay-payment-table-editor.js') }}?v=20261103a"></script>
    @endif
</x-layouts.admin>
