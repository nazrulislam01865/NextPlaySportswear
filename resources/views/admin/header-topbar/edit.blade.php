<x-layouts.admin title="Header & Top Bar Settings" :storefront-url="route('home')">
    @php
        $enabled = static fn (string $key): bool => (bool) old($key, $settings[$key] ?? false);
        $value = static fn (string $key): mixed => old($key, $settings[$key] ?? '');
    @endphp

    <form method="POST" action="{{ route('admin.header-topbar-settings.update') }}" class="np-storefront-editor space-y-6">
        @csrf
        @method('PUT')

        <x-admin.storefront-editor-toolbar
            description="Control the announcement, Track Order link, WhatsApp and social icons in the storefront's top header. Saving does not change the existing header layout or other pages."
            :preview-url="route('home')"
            preview-label="Preview Storefront"
            save-label="Save Top Bar Settings"
            :can-manage="$canManage"
        />

        @if(session('status'))
            <div data-admin-flash role="status" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div role="alert" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">Some settings could not be saved. Please review the highlighted fields.</div>
        @endif

        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Visibility &amp; announcement</p>
                <h2 class="mt-2 text-xl font-black text-brand-ink">Top header bar</h2>
                <p class="mt-2 text-sm text-slate-500">The announcement repeats across the original scrolling ticker. Disabling the entire bar hides all its links.</p>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                @foreach([
                    ['key' => 'bar_enabled', 'label' => 'Show top header bar', 'hint' => 'Master switch for the entire top utility bar.'],
                    ['key' => 'announcement_enabled', 'label' => 'Show scrolling announcement', 'hint' => 'Keep the animated announcement and its star icon visible.'],
                ] as $toggle)
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <input type="hidden" name="{{ $toggle['key'] }}" value="0">
                        <input type="checkbox" name="{{ $toggle['key'] }}" value="1" class="mt-1 h-4 w-4 rounded border-slate-300 text-brand-red focus:ring-brand-red" @checked($enabled($toggle['key'])) @disabled(! $canManage)>
                        <span class="block"><strong class="block text-sm text-brand-ink">{{ $toggle['label'] }}</strong><span class="mt-1 block text-xs text-slate-500">{{ $toggle['hint'] }}</span></span>
                    </label>
                @endforeach
                <div>
                    <label class="admin-label" for="topbar-announcement">Announcement text</label>
                    <input id="topbar-announcement" class="admin-input" name="announcement_text" type="text" maxlength="180" required value="{{ $value('announcement_text') }}" @disabled(! $canManage)>
                    <p class="mt-1 text-xs text-slate-500">Use the • character to separate phrases. The original ticker appearance remains in place.</p>
                    @error('announcement_text')<p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Quick links</p>
                <h2 class="mt-2 text-xl font-black text-brand-ink">Track Order &amp; WhatsApp</h2>
                <p class="mt-2 text-sm text-slate-500">Track Order continues to use the site's existing order-tracking route. For WhatsApp, the custom link takes priority over the number.</p>
            </div>
            <div class="grid gap-5 p-5 sm:p-6 lg:grid-cols-2">
                <div class="space-y-4 rounded-2xl border border-slate-200 p-4">
                    <label class="flex items-center gap-3 text-sm font-bold text-brand-ink">
                        <input type="hidden" name="track_order_enabled" value="0">
                        <input type="checkbox" name="track_order_enabled" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-red focus:ring-brand-red" @checked($enabled('track_order_enabled')) @disabled(! $canManage)>
                        Show Track Order link
                    </label>
                    <div>
                        <label class="admin-label" for="topbar-track-label">Link label</label>
                        <input id="topbar-track-label" class="admin-input" name="track_order_label" maxlength="50" required value="{{ $value('track_order_label') }}" @disabled(! $canManage)>
                        @error('track_order_label')<p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="space-y-4 rounded-2xl border border-slate-200 p-4">
                    <label class="flex items-center gap-3 text-sm font-bold text-brand-ink">
                        <input type="hidden" name="whatsapp_enabled" value="0">
                        <input type="checkbox" name="whatsapp_enabled" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-red focus:ring-brand-red" @checked($enabled('whatsapp_enabled')) @disabled(! $canManage)>
                        Show WhatsApp link
                    </label>
                    <div>
                        <label class="admin-label" for="topbar-wa-label">Link label</label>
                        <input id="topbar-wa-label" class="admin-input" name="whatsapp_label" maxlength="50" required value="{{ $value('whatsapp_label') }}" @disabled(! $canManage)>
                        @error('whatsapp_label')<p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="admin-label" for="topbar-wa-number">WhatsApp number (with country code)</label>
                        <input id="topbar-wa-number" class="admin-input" type="tel" name="whatsapp_number" maxlength="60" placeholder="+8801XXXXXXXXX" value="{{ $value('whatsapp_number') }}" @disabled(! $canManage)>
                        @error('whatsapp_number')<p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="admin-label" for="topbar-wa-url">Custom WhatsApp URL (optional)</label>
                        <input id="topbar-wa-url" class="admin-input" type="url" name="whatsapp_url" maxlength="500" placeholder="https://wa.me/8801XXXXXXXXX" value="{{ $value('whatsapp_url') }}" @disabled(! $canManage)>
                        <p class="mt-1 text-xs text-slate-500">Leave blank to create a wa.me link automatically from the number above.</p>
                        @error('whatsapp_url')<p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Social media</p>
                <h2 class="mt-2 text-xl font-black text-brand-ink">Follow us</h2>
                <p class="mt-2 text-sm text-slate-500">Each network can be shown or hidden independently. Networks with empty links are hidden automatically.</p>
            </div>
            <div class="space-y-5 p-5 sm:p-6">
                <div class="max-w-md">
                    <label class="admin-label" for="topbar-follow-label">Section label</label>
                    <input id="topbar-follow-label" class="admin-input" name="follow_label" maxlength="50" required value="{{ $value('follow_label') }}" @disabled(! $canManage)>
                    @error('follow_label')<p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-4 lg:grid-cols-2">
                    @foreach(['instagram' => 'Instagram', 'facebook' => 'Facebook', 'tiktok' => 'TikTok', 'youtube' => 'YouTube'] as $network => $label)
                        <div class="space-y-3 rounded-2xl border border-slate-200 p-4">
                            <label class="flex items-center gap-3 text-sm font-bold text-brand-ink">
                                <input type="hidden" name="{{ $network }}_enabled" value="0">
                                <input type="checkbox" name="{{ $network }}_enabled" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-red focus:ring-brand-red" @checked($enabled($network.'_enabled')) @disabled(! $canManage)>
                                Show {{ $label }} icon
                            </label>
                            <div>
                                <label class="admin-label" for="topbar-{{ $network }}">{{ $label }} profile URL</label>
                                <input id="topbar-{{ $network }}" class="admin-input" type="url" name="{{ $network }}_url" maxlength="500" placeholder="https://" value="{{ $value($network.'_url') }}" @disabled(! $canManage)>
                                @error($network.'_url')<p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.18em] text-brand-red">Optional styling</p>
                <h2 class="mt-2 text-xl font-black text-brand-ink">Appearance overrides</h2>
                <p class="mt-2 text-sm text-slate-500">Leave fields blank to preserve the current theme, colors and spacing exactly as they are.</p>
            </div>
            <div class="grid gap-5 p-5 sm:p-6 lg:grid-cols-3">
                <div>
                    <label class="admin-label" for="topbar-bg">Background color (hex)</label>
                    <input id="topbar-bg" class="admin-input" name="background_color" maxlength="7" placeholder="#0A254A" value="{{ $value('background_color') }}" @disabled(! $canManage)>
                    @error('background_color')<p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="admin-label" for="topbar-text">Text color (hex)</label>
                    <input id="topbar-text" class="admin-input" name="text_color" maxlength="7" placeholder="#FFFFFF" value="{{ $value('text_color') }}" @disabled(! $canManage)>
                    @error('text_color')<p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="admin-label" for="topbar-gap">Quick-link spacing in pixels</label>
                    <input id="topbar-gap" class="admin-input" name="links_gap_px" type="number" min="4" max="40" step="1" placeholder="Use current spacing" value="{{ $value('links_gap_px') }}" @disabled(! $canManage)>
                    @error('links_gap_px')<p class="mt-1 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <div class="flex flex-wrap items-center justify-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-card">
            <a href="{{ route('home') }}" target="_blank" rel="noopener noreferrer" class="btn btn-white">Preview Storefront</a>
            @if($canManage)
                <button type="submit" class="btn btn-red">Save Top Bar Settings</button>
            @endif
        </div>
    </form>
</x-layouts.admin>
