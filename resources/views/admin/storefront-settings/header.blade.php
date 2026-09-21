<x-layouts.admin title="Header Settings" eyebrow="Storefront" subtitle="Control the new Vue storefront utility bar and header actions." :storefront-url="route('home')">
    @php
        $branding = (array) old('branding', $settings['branding'] ?? []);
        $brandLogo = trim((string) ($branding['logo'] ?? ''));
        $brandLogoAlt = trim((string) ($branding['logo_alt'] ?? config('storefront.name', 'NextPlay Sportswear')));
        $hasManagedBrandLogo = str_starts_with($brandLogo, '/storage/storefront/settings/branding/');
        $announcements = array_values(array_filter((array) old('announcements', $settings['announcements'] ?? []), 'is_array'));
        $utilityLinks = array_values(array_filter((array) old('utility_links', $settings['utility_links'] ?? []), 'is_array'));
        $actions = (array) ($settings['actions'] ?? []);
        $search = (array) ($actions['search'] ?? []);
        $quote = (array) ($actions['quote'] ?? []);
    @endphp

    <div class="mx-auto max-w-6xl space-y-6" data-header-settings>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-black text-slate-900">New Vue Header</h2>
                    <p class="mt-1 text-sm text-slate-500">These settings are returned by <code>/api/v1/storefront/bootstrap</code>. The brand logo is shared across the entire new Vue storefront; the remaining controls belong to the Vue header.</p>
                </div>
                <span class="inline-flex min-h-10 items-center rounded-xl bg-slate-100 px-4 text-xs font-black uppercase tracking-wide text-slate-600">Vue storefront source</span>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.header-settings.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PATCH')

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" data-branding-section>
                <div class="mb-5">
                    <h3 class="text-base font-black text-slate-900">Global Storefront Logo</h3>
                    <p class="mt-1 text-sm text-slate-500">Upload the logo once here. The reusable Vue <code>BrandLogo</code> component uses it in the header, footer and every new Vue storefront page that displays the brand logo.</p>
                </div>

                <div class="grid gap-5 lg:grid-cols-[320px_1fr] lg:items-start">
                    <div>
                        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Current logo</label>
                        <div class="flex min-h-28 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <img src="{{ $brandLogo }}" alt="{{ $brandLogoAlt }}" class="max-h-16 max-w-full object-contain {{ $brandLogo !== '' ? '' : 'hidden' }}" data-logo-preview>
                            <span class="text-sm font-bold text-slate-400 {{ $brandLogo !== '' ? 'hidden' : '' }}" data-logo-placeholder>No logo</span>
                        </div>
                    </div>

                    <div class="grid gap-4">
                        <input type="hidden" name="branding[logo]" value="{{ $brandLogo }}">
                        <div>
                            <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Upload / replace logo</label>
                            <input type="file" name="branding[logo_file]" accept="image/png,image/jpeg,image/webp" class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-bold file:text-slate-700 hover:file:bg-slate-200" data-logo-file>
                            <p class="mt-1.5 text-xs text-slate-500">PNG, JPG or WEBP. Max 4 MB. A transparent PNG or WEBP wordmark is recommended.</p>
                            @error('branding.logo_file')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Logo alt text</label>
                            <input name="branding[logo_alt]" value="{{ old('branding.logo_alt', $brandLogoAlt) }}" maxlength="120" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="NextPlay Sportswear">
                            <p class="mt-1.5 text-xs text-slate-500">Used for accessibility wherever the shared storefront logo is rendered.</p>
                        </div>

                        @if($hasManagedBrandLogo)
                            <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-4 text-sm font-bold text-slate-800">
                                <input type="hidden" name="branding[remove_logo]" value="0">
                                <input type="checkbox" name="branding[remove_logo]" value="1" data-remove-logo>
                                Remove uploaded logo and use the project fallback logo
                            </label>
                        @else
                            <input type="hidden" name="branding[remove_logo]" value="0">
                        @endif
                    </div>
                </div>
            </section>


            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-base font-black text-slate-900">Announcements</h3>
                        <p class="mt-1 text-sm text-slate-500">Add multiple promotional messages. Enabled announcements rotate automatically in the Vue utility bar.</p>
                    </div>
                    <button type="button" data-action="add-announcement" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-300 px-4 text-sm font-bold text-slate-700 hover:bg-slate-50">+ Add Announcement</button>
                </div>

                <div class="space-y-4" data-announcement-list>
                    @forelse($announcements as $index => $announcement)
                        <div class="rounded-xl border border-slate-200 p-4" data-sortable-row>
                            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                                <div class="flex flex-wrap items-center gap-4">
                                    <label class="flex items-center gap-2 text-sm font-bold text-slate-800">
                                        <input type="hidden" name="announcements[{{ $index }}][enabled]" value="0">
                                        <input type="checkbox" name="announcements[{{ $index }}][enabled]" value="1" @checked((bool) ($announcement['enabled'] ?? true))>
                                        Show
                                    </label>
                                    <label class="flex items-center gap-2 text-sm font-bold text-slate-800">
                                        <input type="hidden" name="announcements[{{ $index }}][dismissible]" value="0">
                                        <input type="checkbox" name="announcements[{{ $index }}][dismissible]" value="1" @checked((bool) ($announcement['dismissible'] ?? true))>
                                        Allow close
                                    </label>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" data-action="move-up" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-50">Up</button>
                                    <button type="button" data-action="move-down" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-50">Down</button>
                                    <button type="button" data-action="remove-row" class="rounded-lg border border-red-200 px-2.5 py-1.5 text-xs font-bold text-red-600 hover:bg-red-50">Remove</button>
                                </div>
                            </div>
                            <div class="grid gap-4 lg:grid-cols-[1.3fr_1fr]">
                                <div>
                                    <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Announcement text</label>
                                    <input name="announcements[{{ $index }}][text]" value="{{ $announcement['text'] ?? '' }}" maxlength="255" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="Shop $200 and get free delivery">
                                </div>
                                <div>
                                    <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Optional destination</label>
                                    <input name="announcements[{{ $index }}][url]" value="{{ $announcement['url'] ?? '' }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="/offers or https://...">
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 p-5 text-sm text-slate-500" data-empty-state>No announcements yet. Add one to show a promotional message.</div>
                    @endforelse
                </div>
                @error('announcements')<p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                @error('announcements.*')<p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-base font-black text-slate-900">Utility Links</h3>
                        <p class="mt-1 text-sm text-slate-500">Add, remove and order utility links. Upload a PNG/JPG/WEBP icon for each link instead of selecting a preset icon.</p>
                    </div>
                    <button type="button" data-action="add-utility" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-300 px-4 text-sm font-bold text-slate-700 hover:bg-slate-50">+ Add Utility Link</button>
                </div>

                <div class="space-y-4" data-utility-list>
                    @forelse($utilityLinks as $index => $item)
                        <div class="rounded-xl border border-slate-200 p-4" data-sortable-row>
                            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                                <label class="flex items-center gap-2 text-sm font-bold text-slate-800">
                                    <input type="hidden" name="utility_links[{{ $index }}][enabled]" value="0">
                                    <input type="checkbox" name="utility_links[{{ $index }}][enabled]" value="1" @checked((bool) ($item['enabled'] ?? true))>
                                    Show link
                                </label>
                                <div class="flex items-center gap-2">
                                    <button type="button" data-action="move-up" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-50">Up</button>
                                    <button type="button" data-action="move-down" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-50">Down</button>
                                    <button type="button" data-action="remove-row" class="rounded-lg border border-red-200 px-2.5 py-1.5 text-xs font-bold text-red-600 hover:bg-red-50">Remove</button>
                                </div>
                            </div>
                            <div class="grid gap-4 lg:grid-cols-[300px_1fr_1.35fr]">
                                @php
                                    $currentIcon = trim((string) ($item['icon'] ?? ''));
                                    $hasImagePreview = $currentIcon !== '' && (str_starts_with($currentIcon, '/storage/') || str_starts_with($currentIcon, 'http://') || str_starts_with($currentIcon, 'https://'));
                                @endphp
                                <div data-icon-field>
                                    <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Icon image</label>
                                    <input type="hidden" name="utility_links[{{ $index }}][icon]" value="{{ $currentIcon }}">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                            <img src="{{ $hasImagePreview ? $currentIcon : '' }}" alt="" class="h-8 w-8 object-contain {{ $hasImagePreview ? '' : 'hidden' }}" data-icon-preview>
                                            <span class="text-[10px] font-bold text-slate-400 {{ $hasImagePreview ? 'hidden' : '' }}" data-icon-placeholder>ICON</span>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <input type="file" name="utility_links[{{ $index }}][icon_file]" accept="image/png,image/jpeg,image/webp" class="block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-bold file:text-slate-700 hover:file:bg-slate-200" data-icon-file>
                                            <p class="mt-1 text-[11px] text-slate-500">PNG, JPG or WEBP. Max 2 MB. Transparent PNG recommended.</p>
                                            @if($currentIcon !== '')
                                                <label class="mt-2 flex items-center gap-2 text-[11px] font-bold text-slate-600">
                                                    <input type="hidden" name="utility_links[{{ $index }}][remove_icon]" value="0">
                                                    <input type="checkbox" name="utility_links[{{ $index }}][remove_icon]" value="1" data-remove-icon>
                                                    Remove current icon
                                                </label>
                                                @unless($hasImagePreview)
                                                    <p class="mt-1 text-[11px] text-amber-600">A legacy preset icon is currently active. Upload an image to replace it.</p>
                                                @endunless
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Label</label>
                                    <input name="utility_links[{{ $index }}][label]" value="{{ $item['label'] ?? '' }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="Track Your Order">
                                </div>
                                <div>
                                    <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">URL</label>
                                    <input name="utility_links[{{ $index }}][url]" value="{{ $item['url'] ?? '' }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="/account/orders">
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 p-5 text-sm text-slate-500" data-empty-state>No utility links yet.</div>
                    @endforelse
                </div>
                @error('utility_links')<p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                @error('utility_links.*')<p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-5">
                    <h3 class="text-base font-black text-slate-900">Header Actions</h3>
                    <p class="mt-1 text-sm text-slate-500">These fields control the Vue header action area. The menu and mega menu are managed above in this same Header Settings page.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    @foreach([
                        'account_enabled' => ['Account', $actions['account_enabled'] ?? true],
                        'wishlist_enabled' => ['Wishlist', $actions['wishlist_enabled'] ?? true],
                        'cart_enabled' => ['Cart', $actions['cart_enabled'] ?? true],
                    ] as $field => [$label, $value])
                        <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-4 text-sm font-bold text-slate-800">
                            <input type="hidden" name="{{ $field }}" value="0"><input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $value))>
                            Show {{ $label }}
                        </label>
                    @endforeach
                </div>

                <div class="mt-5 grid gap-4 rounded-xl border border-slate-200 p-4 lg:grid-cols-[180px_1fr_1.4fr] lg:items-end">
                    <label class="flex items-center gap-3 pb-2 text-sm font-bold text-slate-800">
                        <input type="hidden" name="search_enabled" value="0"><input type="checkbox" name="search_enabled" value="1" @checked(old('search_enabled', $search['enabled'] ?? true))>
                        Show search
                    </label>
                    <div>
                        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Search label</label>
                        <input name="search_label" value="{{ old('search_label', $search['label'] ?? 'Search') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Search URL</label>
                        <input name="search_url" value="{{ old('search_url', $search['url'] ?? '/products') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    </div>
                </div>

                <div class="mt-4 grid gap-4 rounded-xl border border-slate-200 p-4 lg:grid-cols-[180px_1fr_1.4fr] lg:items-end">
                    <label class="flex items-center gap-3 pb-2 text-sm font-bold text-slate-800">
                        <input type="hidden" name="quote_enabled" value="0"><input type="checkbox" name="quote_enabled" value="1" @checked(old('quote_enabled', $quote['enabled'] ?? true))>
                        Show quote CTA
                    </label>
                    <div>
                        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Button label</label>
                        <input name="quote_label" value="{{ old('quote_label', $quote['label'] ?? 'GET A QUOTE') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Button URL</label>
                        <input name="quote_url" value="{{ old('quote_url', $quote['url'] ?? '/bulk-quote') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    </div>
                </div>
            </section>

            <div class="sticky bottom-4 flex justify-end rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-lg backdrop-blur">
                <button type="submit" class="min-h-11 rounded-xl bg-brand-dark px-6 text-sm font-black text-white hover:opacity-90">Save Header Settings</button>
            </div>
        </form>
    </div>

    <script>
        (() => {
            const root = document.querySelector('[data-header-settings]');
            if (!root) return;

            let sequence = Date.now();
            const uniqueKey = () => `new_${sequence++}`;

            const removeEmpty = (list) => list.querySelector('[data-empty-state]')?.remove();
            const addEmptyIfNeeded = (list, text) => {
                if (!list.querySelector('[data-sortable-row]')) {
                    list.insertAdjacentHTML('beforeend', `<div class="rounded-xl border border-dashed border-slate-300 p-5 text-sm text-slate-500" data-empty-state>${text}</div>`);
                }
            };

            const announcementRow = (key) => `
                <div class="rounded-xl border border-slate-200 p-4" data-sortable-row>
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-4">
                            <label class="flex items-center gap-2 text-sm font-bold text-slate-800">
                                <input type="hidden" name="announcements[${key}][enabled]" value="0">
                                <input type="checkbox" name="announcements[${key}][enabled]" value="1" checked> Show
                            </label>
                            <label class="flex items-center gap-2 text-sm font-bold text-slate-800">
                                <input type="hidden" name="announcements[${key}][dismissible]" value="0">
                                <input type="checkbox" name="announcements[${key}][dismissible]" value="1" checked> Allow close
                            </label>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" data-action="move-up" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-50">Up</button>
                            <button type="button" data-action="move-down" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-50">Down</button>
                            <button type="button" data-action="remove-row" class="rounded-lg border border-red-200 px-2.5 py-1.5 text-xs font-bold text-red-600 hover:bg-red-50">Remove</button>
                        </div>
                    </div>
                    <div class="grid gap-4 lg:grid-cols-[1.3fr_1fr]">
                        <div><label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Announcement text</label><input name="announcements[${key}][text]" maxlength="255" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="Shop $200 and get free delivery"></div>
                        <div><label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Optional destination</label><input name="announcements[${key}][url]" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="/offers or https://..."></div>
                    </div>
                </div>`;

            const utilityRow = (key) => `
                <div class="rounded-xl border border-slate-200 p-4" data-sortable-row>
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <label class="flex items-center gap-2 text-sm font-bold text-slate-800"><input type="hidden" name="utility_links[${key}][enabled]" value="0"><input type="checkbox" name="utility_links[${key}][enabled]" value="1" checked> Show link</label>
                        <div class="flex items-center gap-2">
                            <button type="button" data-action="move-up" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-50">Up</button>
                            <button type="button" data-action="move-down" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-50">Down</button>
                            <button type="button" data-action="remove-row" class="rounded-lg border border-red-200 px-2.5 py-1.5 text-xs font-bold text-red-600 hover:bg-red-50">Remove</button>
                        </div>
                    </div>
                    <div class="grid gap-4 lg:grid-cols-[300px_1fr_1.35fr]">
                        <div data-icon-field>
                            <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Icon image</label>
                            <input type="hidden" name="utility_links[${key}][icon]" value="">
                            <div class="flex items-center gap-3">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50"><img alt="" class="hidden h-8 w-8 object-contain" data-icon-preview><span class="text-[10px] font-bold text-slate-400" data-icon-placeholder>ICON</span></div>
                                <div class="min-w-0 flex-1"><input type="file" name="utility_links[${key}][icon_file]" accept="image/png,image/jpeg,image/webp" class="block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-bold file:text-slate-700 hover:file:bg-slate-200" data-icon-file><p class="mt-1 text-[11px] text-slate-500">PNG, JPG or WEBP. Max 2 MB.</p></div>
                            </div>
                        </div>
                        <div><label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Label</label><input name="utility_links[${key}][label]" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="Utility link"></div>
                        <div><label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">URL</label><input name="utility_links[${key}][url]" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="/page or https://..."></div>
                    </div>
                </div>`;

            root.addEventListener('change', (event) => {
                const input = event.target;
                if (!(input instanceof HTMLInputElement)) return;

                if (input.matches('[data-logo-file]')) {
                    const section = input.closest('[data-branding-section]');
                    const preview = section?.querySelector('[data-logo-preview]');
                    const placeholder = section?.querySelector('[data-logo-placeholder]');
                    const remove = section?.querySelector('[data-remove-logo]');
                    const file = input.files?.[0];
                    if (!file || !(preview instanceof HTMLImageElement)) return;

                    preview.src = URL.createObjectURL(file);
                    preview.classList.remove('hidden');
                    placeholder?.classList.add('hidden');
                    if (remove instanceof HTMLInputElement) remove.checked = false;
                    return;
                }

                if (input.matches('[data-remove-logo]') && input.checked) {
                    const section = input.closest('[data-branding-section]');
                    const fileInput = section?.querySelector('[data-logo-file]');
                    const preview = section?.querySelector('[data-logo-preview]');
                    const placeholder = section?.querySelector('[data-logo-placeholder]');
                    if (fileInput instanceof HTMLInputElement) fileInput.value = '';
                    if (preview instanceof HTMLImageElement) {
                        preview.src = '';
                        preview.classList.add('hidden');
                    }
                    placeholder?.classList.remove('hidden');
                    return;
                }

                if (input.matches('[data-icon-file]')) {
                    const field = input.closest('[data-icon-field]');
                    const preview = field?.querySelector('[data-icon-preview]');
                    const placeholder = field?.querySelector('[data-icon-placeholder]');
                    const remove = field?.querySelector('[data-remove-icon]');
                    const file = input.files?.[0];
                    if (!file || !preview) return;

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

                if (action === 'add-announcement') {
                    const list = root.querySelector('[data-announcement-list]');
                    if (list.querySelectorAll('[data-sortable-row]').length >= 12) return;
                    removeEmpty(list);
                    list.insertAdjacentHTML('beforeend', announcementRow(uniqueKey()));
                    return;
                }

                if (action === 'add-utility') {
                    const list = root.querySelector('[data-utility-list]');
                    if (list.querySelectorAll('[data-sortable-row]').length >= 12) return;
                    removeEmpty(list);
                    list.insertAdjacentHTML('beforeend', utilityRow(uniqueKey()));
                    return;
                }

                const row = button.closest('[data-sortable-row]');
                if (!row) return;
                const list = row.parentElement;

                if (action === 'remove-row') {
                    const isAnnouncement = list.matches('[data-announcement-list]');
                    row.remove();
                    addEmptyIfNeeded(list, isAnnouncement ? 'No announcements yet. Add one to show a promotional message.' : 'No utility links yet.');
                } else if (action === 'move-up' && row.previousElementSibling?.matches('[data-sortable-row]')) {
                    list.insertBefore(row, row.previousElementSibling);
                } else if (action === 'move-down' && row.nextElementSibling?.matches('[data-sortable-row]')) {
                    list.insertBefore(row.nextElementSibling, row);
                }
            });
        })();
    </script>
</x-layouts.admin>
