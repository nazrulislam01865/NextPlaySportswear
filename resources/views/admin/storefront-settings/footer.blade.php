<x-layouts.admin title="Footer Settings" eyebrow="Storefront" subtitle="Control the new Vue storefront footer content without editing frontend code." :storefront-url="route('home')">
    @php
        $contact = (array) ($settings['contact'] ?? []);
        $columns = array_values(array_filter((array) old('columns', $settings['columns'] ?? []), 'is_array'));
        $club = (array) ($settings['club'] ?? []);
        $social = (array) ($settings['social'] ?? []);
        $socialLinks = array_values(array_filter((array) old('social_links', $social['links'] ?? []), 'is_array'));
        $legal = (array) ($settings['legal'] ?? []);
        $legalLinks = array_values(array_filter((array) old('legal_links', $legal['links'] ?? []), 'is_array'));
        $payments = (array) ($settings['payments'] ?? []);
    @endphp

    <div class="mx-auto max-w-6xl space-y-6" data-footer-settings>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div>
                <h2 class="text-lg font-black text-slate-900">New Vue Footer</h2>
                <p class="mt-1 text-sm text-slate-500">All footer data is stored in Laravel and returned through <code>/api/v1/storefront/bootstrap</code>. Menu and social icons are uploaded as images and managed by Laravel.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.footer-settings.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PATCH')

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-base font-black text-slate-900">Contact Information</h3>
                <div class="mt-5 grid gap-4 lg:grid-cols-3">
                    <div class="lg:col-span-3">
                        <label class="mb-2 block text-sm font-bold text-slate-700">Address</label>
                        <input name="address" value="{{ old('address', $contact['address'] ?? '') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                        @error('address')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">Email</label>
                        <input type="email" name="email" value="{{ old('email', $contact['email'] ?? '') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                        @error('email')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">Phone</label>
                        <input name="phone" value="{{ old('phone', $contact['phone'] ?? '') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-base font-black text-slate-900">Footer Menu Columns</h3>
                        <p class="mt-1 text-sm text-slate-500">Create up to six columns. Every column can contain multiple links with optional uploaded icon images.</p>
                    </div>
                    <button type="button" data-action="add-column" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-300 px-4 text-sm font-bold text-slate-700 hover:bg-slate-50">+ Add Menu Column</button>
                </div>

                <div class="space-y-5" data-column-list>
                    @forelse($columns as $columnIndex => $column)
                        @php $items = array_values(array_filter((array) ($column['items'] ?? []), 'is_array')); @endphp
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/40 p-4" data-sortable-row data-column-row data-column-key="{{ $columnIndex }}">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
                                <label class="flex items-center gap-2 pb-2 text-sm font-bold text-slate-800">
                                    <input type="hidden" name="columns[{{ $columnIndex }}][enabled]" value="0">
                                    <input type="checkbox" name="columns[{{ $columnIndex }}][enabled]" value="1" @checked((bool) ($column['enabled'] ?? true))>
                                    Show column
                                </label>
                                <div class="min-w-0 flex-1">
                                    <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Column heading</label>
                                    <input name="columns[{{ $columnIndex }}][title]" value="{{ $column['title'] ?? '' }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm" placeholder="Quick Links">
                                </div>
                                <div class="flex items-center gap-2 pb-1">
                                    <button type="button" data-action="move-up" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-50">Up</button>
                                    <button type="button" data-action="move-down" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-50">Down</button>
                                    <button type="button" data-action="remove-column" class="rounded-lg border border-red-200 bg-white px-2.5 py-1.5 text-xs font-bold text-red-600 hover:bg-red-50">Remove</button>
                                </div>
                            </div>

                            <div class="mt-4 space-y-3" data-column-items>
                                @foreach($items as $itemIndex => $item)
                                    <div class="grid gap-3 rounded-xl border border-slate-200 bg-white p-3 lg:grid-cols-[110px_210px_1fr_1.2fr_auto] lg:items-end" data-sortable-row data-link-row>
                                        <label class="flex items-center gap-2 pb-2 text-xs font-bold text-slate-700">
                                            <input type="hidden" name="columns[{{ $columnIndex }}][items][{{ $itemIndex }}][enabled]" value="0">
                                            <input type="checkbox" name="columns[{{ $columnIndex }}][items][{{ $itemIndex }}][enabled]" value="1" @checked((bool) ($item['enabled'] ?? true))>
                                            Show
                                        </label>
                                        @php
                                            $currentIcon = trim((string) ($item['icon'] ?? ''));
                                            $hasImagePreview = $currentIcon !== '' && (str_starts_with($currentIcon, '/storage/') || str_starts_with($currentIcon, 'http://') || str_starts_with($currentIcon, 'https://'));
                                        @endphp
                                        <div data-icon-field>
                                            <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Icon image</label>
                                            <input type="hidden" name="columns[{{ $columnIndex }}][items][{{ $itemIndex }}][icon]" value="{{ $currentIcon }}">
                                            <div class="flex items-center gap-2">
                                                <div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                                                    <img src="{{ $hasImagePreview ? $currentIcon : '' }}" alt="" class="h-7 w-7 object-contain {{ $hasImagePreview ? '' : 'hidden' }}" data-icon-preview>
                                                    <span class="text-[9px] font-bold text-slate-400 {{ $hasImagePreview ? 'hidden' : '' }}" data-icon-placeholder>ICON</span>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <input type="file" name="columns[{{ $columnIndex }}][items][{{ $itemIndex }}][icon_file]" accept="image/png,image/jpeg,image/webp" class="block w-full text-[11px] text-slate-600 file:mr-2 file:rounded-lg file:border-0 file:bg-slate-100 file:px-2 file:py-1.5 file:text-[11px] file:font-bold file:text-slate-700" data-icon-file>
                                                    @if($currentIcon !== '')
                                                        <label class="mt-1 flex items-center gap-1.5 text-[10px] font-bold text-slate-500"><input type="hidden" name="columns[{{ $columnIndex }}][items][{{ $itemIndex }}][remove_icon]" value="0"><input type="checkbox" name="columns[{{ $columnIndex }}][items][{{ $itemIndex }}][remove_icon]" value="1" data-remove-icon> Remove</label>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Label</label>
                                            <input name="columns[{{ $columnIndex }}][items][{{ $itemIndex }}][label]" value="{{ $item['label'] ?? '' }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="Link label">
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">URL</label>
                                            <input name="columns[{{ $columnIndex }}][items][{{ $itemIndex }}][url]" value="{{ $item['url'] ?? '' }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="/page or https://...">
                                        </div>
                                        <div class="flex gap-1 pb-1">
                                            <button type="button" data-action="move-up" class="rounded-lg border border-slate-200 px-2 py-1.5 text-xs font-bold text-slate-600">↑</button>
                                            <button type="button" data-action="move-down" class="rounded-lg border border-slate-200 px-2 py-1.5 text-xs font-bold text-slate-600">↓</button>
                                            <button type="button" data-action="remove-link" class="rounded-lg border border-red-200 px-2 py-1.5 text-xs font-bold text-red-600">×</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" data-action="add-column-link" class="mt-3 inline-flex min-h-9 items-center rounded-lg border border-slate-300 bg-white px-3 text-xs font-black text-slate-700 hover:bg-slate-50">+ Add Link</button>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 p-5 text-sm text-slate-500" data-empty-state>No footer menu columns yet.</div>
                    @endforelse
                </div>
                @error('columns')<p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                @error('columns.*')<p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div><h3 class="text-base font-black text-slate-900">Club CTA</h3><p class="mt-1 text-sm text-slate-500">Controls the signup block in the main footer.</p></div>
                    <label class="flex items-center gap-2 text-sm font-bold text-slate-800"><input type="hidden" name="club_enabled" value="0"><input type="checkbox" name="club_enabled" value="1" @checked(old('club_enabled', $club['enabled'] ?? true))> Show</label>
                </div>
                <div class="mt-5 grid gap-4 lg:grid-cols-3">
                    <div><label class="mb-2 block text-sm font-bold text-slate-700">Title</label><input name="club_title" value="{{ old('club_title', $club['title'] ?? '') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"></div>
                    <div><label class="mb-2 block text-sm font-bold text-slate-700">Button label</label><input name="club_button_label" value="{{ old('club_button_label', $club['button_label'] ?? '') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"></div>
                    <div><label class="mb-2 block text-sm font-bold text-slate-700">Button URL</label><input name="club_button_url" value="{{ old('club_button_url', $club['button_url'] ?? '') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"></div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="flex items-center gap-4">
                            <h3 class="text-base font-black text-slate-900">Social Links</h3>
                            <label class="flex items-center gap-2 text-sm font-bold text-slate-800"><input type="hidden" name="social_enabled" value="0"><input type="checkbox" name="social_enabled" value="1" @checked(old('social_enabled', $social['enabled'] ?? true))> Show band</label>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">Add social links and upload the exact PNG/JPG/WEBP icon image you want to display for each one.</p>
                    </div>
                    <button type="button" data-action="add-social" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-300 px-4 text-sm font-bold text-slate-700 hover:bg-slate-50">+ Add Social Link</button>
                </div>
                <div class="mb-4">
                    <label class="mb-2 block text-sm font-bold text-slate-700">Social band label</label>
                    <input name="social_label" value="{{ old('social_label', $social['label'] ?? '') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="Follow Us :">
                </div>
                <div class="space-y-3" data-social-list>
                    @forelse($socialLinks as $index => $item)
                        <div class="grid gap-3 rounded-xl border border-slate-200 p-3 lg:grid-cols-[110px_210px_1fr_1.3fr_auto] lg:items-end" data-sortable-row>
                            <label class="flex items-center gap-2 pb-2 text-xs font-bold text-slate-700">
                                <input type="hidden" name="social_links[{{ $index }}][enabled]" value="0">
                                <input type="checkbox" name="social_links[{{ $index }}][enabled]" value="1" @checked((bool) ($item['enabled'] ?? true))> Show
                            </label>
                            @php
                                $currentIcon = trim((string) ($item['icon'] ?? ''));
                                $hasImagePreview = $currentIcon !== '' && (str_starts_with($currentIcon, '/storage/') || str_starts_with($currentIcon, 'http://') || str_starts_with($currentIcon, 'https://'));
                            @endphp
                            <div data-icon-field>
                                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Icon image</label>
                                <input type="hidden" name="social_links[{{ $index }}][icon]" value="{{ $currentIcon }}">
                                <div class="flex items-center gap-2">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                                        <img src="{{ $hasImagePreview ? $currentIcon : '' }}" alt="" class="h-7 w-7 object-contain {{ $hasImagePreview ? '' : 'hidden' }}" data-icon-preview>
                                        <span class="text-[9px] font-bold text-slate-400 {{ $hasImagePreview ? 'hidden' : '' }}" data-icon-placeholder>ICON</span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <input type="file" name="social_links[{{ $index }}][icon_file]" accept="image/png,image/jpeg,image/webp" class="block w-full text-[11px] text-slate-600 file:mr-2 file:rounded-lg file:border-0 file:bg-slate-100 file:px-2 file:py-1.5 file:text-[11px] file:font-bold file:text-slate-700" data-icon-file>
                                        <p class="mt-1 text-[10px] text-slate-500">Transparent PNG recommended.</p>
                                        @if($currentIcon !== '')
                                            <label class="mt-1 flex items-center gap-1.5 text-[10px] font-bold text-slate-500"><input type="hidden" name="social_links[{{ $index }}][remove_icon]" value="0"><input type="checkbox" name="social_links[{{ $index }}][remove_icon]" value="1" data-remove-icon> Remove</label>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div><label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Label</label><input name="social_links[{{ $index }}][label]" value="{{ $item['label'] ?? '' }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="Instagram"></div>
                            <div><label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">URL</label><input name="social_links[{{ $index }}][url]" value="{{ $item['url'] ?? '' }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="https://..."></div>
                            <div class="flex gap-1 pb-1">
                                <button type="button" data-action="move-up" class="rounded-lg border border-slate-200 px-2 py-1.5 text-xs font-bold text-slate-600">↑</button>
                                <button type="button" data-action="move-down" class="rounded-lg border border-slate-200 px-2 py-1.5 text-xs font-bold text-slate-600">↓</button>
                                <button type="button" data-action="remove-social" class="rounded-lg border border-red-200 px-2 py-1.5 text-xs font-bold text-red-600">×</button>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 p-5 text-sm text-slate-500" data-empty-state>No social links yet.</div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-base font-black text-slate-900">Legal & Bottom Bar</h3>
                        <p class="mt-1 text-sm text-slate-500">Use <code>{year}</code> in copyright. Legal links are fully repeatable and can optionally use uploaded icon images.</p>
                    </div>
                    <button type="button" data-action="add-legal" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-300 px-4 text-sm font-bold text-slate-700 hover:bg-slate-50">+ Add Legal Link</button>
                </div>
                <div class="mb-4"><label class="mb-2 block text-sm font-bold text-slate-700">Copyright</label><input name="copyright" value="{{ old('copyright', $legal['copyright'] ?? '') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"></div>
                <div class="space-y-3" data-legal-list>
                    @forelse($legalLinks as $index => $item)
                        <div class="grid gap-3 rounded-xl border border-slate-200 p-3 lg:grid-cols-[110px_210px_1fr_1.3fr_auto] lg:items-end" data-sortable-row>
                            <label class="flex items-center gap-2 pb-2 text-xs font-bold text-slate-700">
                                <input type="hidden" name="legal_links[{{ $index }}][enabled]" value="0">
                                <input type="checkbox" name="legal_links[{{ $index }}][enabled]" value="1" @checked((bool) ($item['enabled'] ?? true))> Show
                            </label>
                            @php
                                $currentIcon = trim((string) ($item['icon'] ?? ''));
                                $hasImagePreview = $currentIcon !== '' && (str_starts_with($currentIcon, '/storage/') || str_starts_with($currentIcon, 'http://') || str_starts_with($currentIcon, 'https://'));
                            @endphp
                            <div data-icon-field>
                                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Icon image</label>
                                <input type="hidden" name="legal_links[{{ $index }}][icon]" value="{{ $currentIcon }}">
                                <div class="flex items-center gap-2">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                                        <img src="{{ $hasImagePreview ? $currentIcon : '' }}" alt="" class="h-7 w-7 object-contain {{ $hasImagePreview ? '' : 'hidden' }}" data-icon-preview>
                                        <span class="text-[9px] font-bold text-slate-400 {{ $hasImagePreview ? 'hidden' : '' }}" data-icon-placeholder>ICON</span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <input type="file" name="legal_links[{{ $index }}][icon_file]" accept="image/png,image/jpeg,image/webp" class="block w-full text-[11px] text-slate-600 file:mr-2 file:rounded-lg file:border-0 file:bg-slate-100 file:px-2 file:py-1.5 file:text-[11px] file:font-bold file:text-slate-700" data-icon-file>
                                        @if($currentIcon !== '')
                                            <label class="mt-1 flex items-center gap-1.5 text-[10px] font-bold text-slate-500"><input type="hidden" name="legal_links[{{ $index }}][remove_icon]" value="0"><input type="checkbox" name="legal_links[{{ $index }}][remove_icon]" value="1" data-remove-icon> Remove</label>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div><label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Label</label><input name="legal_links[{{ $index }}][label]" value="{{ $item['label'] ?? '' }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="Privacy Policy"></div>
                            <div><label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">URL</label><input name="legal_links[{{ $index }}][url]" value="{{ $item['url'] ?? '' }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="/privacy-policy"></div>
                            <div class="flex gap-1 pb-1">
                                <button type="button" data-action="move-up" class="rounded-lg border border-slate-200 px-2 py-1.5 text-xs font-bold text-slate-600">↑</button>
                                <button type="button" data-action="move-down" class="rounded-lg border border-slate-200 px-2 py-1.5 text-xs font-bold text-slate-600">↓</button>
                                <button type="button" data-action="remove-legal" class="rounded-lg border border-red-200 px-2 py-1.5 text-xs font-bold text-red-600">×</button>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 p-5 text-sm text-slate-500" data-empty-state>No legal links yet.</div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="grid gap-4 lg:grid-cols-[220px_1fr] lg:items-end">
                    <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-4 text-sm font-bold text-slate-800"><input type="hidden" name="payments_enabled" value="0"><input type="checkbox" name="payments_enabled" value="1" @checked(old('payments_enabled', $payments['enabled'] ?? true))> Show payment trust area</label>
                    <div><label class="mb-2 block text-sm font-bold text-slate-700">Payment trust label</label><input name="payments_label" value="{{ old('payments_label', $payments['label'] ?? '') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"></div>
                </div>
            </section>

            <div class="sticky bottom-4 flex justify-end rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-lg backdrop-blur">
                <button type="submit" class="min-h-11 rounded-xl bg-brand-dark px-6 text-sm font-black text-white hover:opacity-90">Save Footer Settings</button>
            </div>
        </form>
    </div>

    <script>
        (() => {
            const root = document.querySelector('[data-footer-settings]');
            if (!root) return;

            let sequence = Date.now();
            const key = () => `new_${sequence++}`;
            const iconUploadField = (name) => `
                <div data-icon-field>
                    <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Icon image</label>
                    <input type="hidden" name="${name}[icon]" value="">
                    <div class="flex items-center gap-2">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-slate-50"><img alt="" class="hidden h-7 w-7 object-contain" data-icon-preview><span class="text-[9px] font-bold text-slate-400" data-icon-placeholder>ICON</span></div>
                        <div class="min-w-0 flex-1"><input type="file" name="${name}[icon_file]" accept="image/png,image/jpeg,image/webp" class="block w-full text-[11px] text-slate-600 file:mr-2 file:rounded-lg file:border-0 file:bg-slate-100 file:px-2 file:py-1.5 file:text-[11px] file:font-bold file:text-slate-700" data-icon-file><p class="mt-1 text-[10px] text-slate-500">PNG, JPG or WEBP. Max 2 MB.</p></div>
                    </div>
                </div>`;

            const clearEmpty = (list) => list.querySelector('[data-empty-state]')?.remove();
            const setEmpty = (list, message) => {
                if (!list.querySelector('[data-sortable-row]')) {
                    list.insertAdjacentHTML('beforeend', `<div class="rounded-xl border border-dashed border-slate-300 p-5 text-sm text-slate-500" data-empty-state>${message}</div>`);
                }
            };

            const menuLink = (columnKey, itemKey) => `
                <div class="grid gap-3 rounded-xl border border-slate-200 bg-white p-3 lg:grid-cols-[110px_210px_1fr_1.2fr_auto] lg:items-end" data-sortable-row data-link-row>
                    <label class="flex items-center gap-2 pb-2 text-xs font-bold text-slate-700"><input type="hidden" name="columns[${columnKey}][items][${itemKey}][enabled]" value="0"><input type="checkbox" name="columns[${columnKey}][items][${itemKey}][enabled]" value="1" checked> Show</label>
                    ${iconUploadField('columns[' + columnKey + '][items][' + itemKey + ']')}
                    <div><label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Label</label><input name="columns[${columnKey}][items][${itemKey}][label]" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="Link label"></div>
                    <div><label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">URL</label><input name="columns[${columnKey}][items][${itemKey}][url]" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="/page or https://..."></div>
                    <div class="flex gap-1 pb-1"><button type="button" data-action="move-up" class="rounded-lg border border-slate-200 px-2 py-1.5 text-xs font-bold text-slate-600">↑</button><button type="button" data-action="move-down" class="rounded-lg border border-slate-200 px-2 py-1.5 text-xs font-bold text-slate-600">↓</button><button type="button" data-action="remove-link" class="rounded-lg border border-red-200 px-2 py-1.5 text-xs font-bold text-red-600">×</button></div>
                </div>`;

            const column = (columnKey) => `
                <div class="rounded-2xl border border-slate-200 bg-slate-50/40 p-4" data-sortable-row data-column-row data-column-key="${columnKey}">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
                        <label class="flex items-center gap-2 pb-2 text-sm font-bold text-slate-800"><input type="hidden" name="columns[${columnKey}][enabled]" value="0"><input type="checkbox" name="columns[${columnKey}][enabled]" value="1" checked> Show column</label>
                        <div class="min-w-0 flex-1"><label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Column heading</label><input name="columns[${columnKey}][title]" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm" placeholder="Menu heading"></div>
                        <div class="flex items-center gap-2 pb-1"><button type="button" data-action="move-up" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-600">Up</button><button type="button" data-action="move-down" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-600">Down</button><button type="button" data-action="remove-column" class="rounded-lg border border-red-200 bg-white px-2.5 py-1.5 text-xs font-bold text-red-600">Remove</button></div>
                    </div>
                    <div class="mt-4 space-y-3" data-column-items></div>
                    <button type="button" data-action="add-column-link" class="mt-3 inline-flex min-h-9 items-center rounded-lg border border-slate-300 bg-white px-3 text-xs font-black text-slate-700 hover:bg-slate-50">+ Add Link</button>
                </div>`;

            const socialRow = (itemKey) => `
                <div class="grid gap-3 rounded-xl border border-slate-200 p-3 lg:grid-cols-[110px_210px_1fr_1.3fr_auto] lg:items-end" data-sortable-row>
                    <label class="flex items-center gap-2 pb-2 text-xs font-bold text-slate-700"><input type="hidden" name="social_links[${itemKey}][enabled]" value="0"><input type="checkbox" name="social_links[${itemKey}][enabled]" value="1" checked> Show</label>
                    ${iconUploadField('social_links[' + itemKey + ']')}
                    <div><label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Label</label><input name="social_links[${itemKey}][label]" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="Instagram"></div>
                    <div><label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">URL</label><input name="social_links[${itemKey}][url]" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="https://..."></div>
                    <div class="flex gap-1 pb-1"><button type="button" data-action="move-up" class="rounded-lg border border-slate-200 px-2 py-1.5 text-xs font-bold text-slate-600">↑</button><button type="button" data-action="move-down" class="rounded-lg border border-slate-200 px-2 py-1.5 text-xs font-bold text-slate-600">↓</button><button type="button" data-action="remove-social" class="rounded-lg border border-red-200 px-2 py-1.5 text-xs font-bold text-red-600">×</button></div>
                </div>`;

            const legalRow = (itemKey) => `
                <div class="grid gap-3 rounded-xl border border-slate-200 p-3 lg:grid-cols-[110px_210px_1fr_1.3fr_auto] lg:items-end" data-sortable-row>
                    <label class="flex items-center gap-2 pb-2 text-xs font-bold text-slate-700"><input type="hidden" name="legal_links[${itemKey}][enabled]" value="0"><input type="checkbox" name="legal_links[${itemKey}][enabled]" value="1" checked> Show</label>
                    ${iconUploadField('legal_links[' + itemKey + ']')}
                    <div><label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Label</label><input name="legal_links[${itemKey}][label]" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="Privacy Policy"></div>
                    <div><label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">URL</label><input name="legal_links[${itemKey}][url]" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="/privacy-policy"></div>
                    <div class="flex gap-1 pb-1"><button type="button" data-action="move-up" class="rounded-lg border border-slate-200 px-2 py-1.5 text-xs font-bold text-slate-600">↑</button><button type="button" data-action="move-down" class="rounded-lg border border-slate-200 px-2 py-1.5 text-xs font-bold text-slate-600">↓</button><button type="button" data-action="remove-legal" class="rounded-lg border border-red-200 px-2 py-1.5 text-xs font-bold text-red-600">×</button></div>
                </div>`;

            root.addEventListener('change', (event) => {
                const input = event.target;
                if (!(input instanceof HTMLInputElement)) return;

                if (input.matches('[data-icon-file]')) {
                    const field = input.closest('[data-icon-field]');
                    const preview = field?.querySelector('[data-icon-preview]');
                    const placeholder = field?.querySelector('[data-icon-placeholder]');
                    const remove = field?.querySelector('[data-remove-icon]');
                    const file = input.files?.[0];
                    if (!file || !(preview instanceof HTMLImageElement)) return;

                    preview.src = URL.createObjectURL(file);
                    preview.classList.remove('hidden');
                    placeholder?.classList.add('hidden');
                    if (remove instanceof HTMLInputElement) remove.checked = false;
                }

                if (input.matches('[data-remove-icon]') && input.checked) {
                    const field = input.closest('[data-icon-field]');
                    const fileInput = field?.querySelector('[data-icon-file]');
                    const preview = field?.querySelector('[data-icon-preview]');
                    const placeholder = field?.querySelector('[data-icon-placeholder]');
                    if (fileInput instanceof HTMLInputElement) fileInput.value = '';
                    if (preview instanceof HTMLImageElement) {
                        preview.src = '';
                        preview.classList.add('hidden');
                    }
                    placeholder?.classList.remove('hidden');
                }
            });

            root.addEventListener('click', (event) => {
                const button = event.target.closest('[data-action]');
                if (!button) return;
                const action = button.dataset.action;

                if (action === 'add-column') {
                    const list = root.querySelector('[data-column-list]');
                    if (list.querySelectorAll(':scope > [data-column-row]').length >= 6) return;
                    clearEmpty(list);
                    const columnKey = key();
                    list.insertAdjacentHTML('beforeend', column(columnKey));
                    const created = list.lastElementChild;
                    created.querySelector('[data-column-items]').insertAdjacentHTML('beforeend', menuLink(columnKey, key()));
                    return;
                }

                if (action === 'add-column-link') {
                    const columnRow = button.closest('[data-column-row]');
                    const list = columnRow.querySelector('[data-column-items]');
                    if (list.querySelectorAll(':scope > [data-link-row]').length >= 12) return;
                    list.insertAdjacentHTML('beforeend', menuLink(columnRow.dataset.columnKey, key()));
                    return;
                }

                if (action === 'add-social') {
                    const list = root.querySelector('[data-social-list]');
                    if (list.querySelectorAll('[data-sortable-row]').length >= 12) return;
                    clearEmpty(list);
                    list.insertAdjacentHTML('beforeend', socialRow(key()));
                    return;
                }

                if (action === 'add-legal') {
                    const list = root.querySelector('[data-legal-list]');
                    if (list.querySelectorAll('[data-sortable-row]').length >= 10) return;
                    clearEmpty(list);
                    list.insertAdjacentHTML('beforeend', legalRow(key()));
                    return;
                }

                if (action === 'remove-column') {
                    const list = root.querySelector('[data-column-list]');
                    button.closest('[data-column-row]')?.remove();
                    setEmpty(list, 'No footer menu columns yet.');
                    return;
                }

                if (action === 'remove-link') {
                    button.closest('[data-link-row]')?.remove();
                    return;
                }

                if (action === 'remove-social') {
                    const list = root.querySelector('[data-social-list]');
                    button.closest('[data-sortable-row]')?.remove();
                    setEmpty(list, 'No social links yet.');
                    return;
                }

                if (action === 'remove-legal') {
                    const list = root.querySelector('[data-legal-list]');
                    button.closest('[data-sortable-row]')?.remove();
                    setEmpty(list, 'No legal links yet.');
                    return;
                }

                if (action === 'move-up' || action === 'move-down') {
                    const row = button.closest('[data-sortable-row]');
                    if (!row) return;
                    const list = row.parentElement;
                    if (action === 'move-up' && row.previousElementSibling?.matches('[data-sortable-row]')) {
                        list.insertBefore(row, row.previousElementSibling);
                    }
                    if (action === 'move-down' && row.nextElementSibling?.matches('[data-sortable-row]')) {
                        list.insertBefore(row.nextElementSibling, row);
                    }
                }
            });
        })();
    </script>
</x-layouts.admin>
