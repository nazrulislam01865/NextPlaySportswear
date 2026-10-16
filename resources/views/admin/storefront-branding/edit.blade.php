<x-layouts.admin
    title="Storefront Branding"
    :storefront-url="route('home')"
>
    <form method="POST" action="{{ route('admin.storefront-branding.update') }}" enctype="multipart/form-data" class="np-storefront-editor space-y-4">
        @csrf
        @method('PUT')


        <x-admin.storefront-editor-toolbar
            description="Manage the logo and browser favicon used across NextPlay. Existing brand assets remain active until you replace them."
            :preview-url="route('home')"
            preview-label="Preview Storefront"
            save-label="Save Branding"
            :can-manage="$canManageBranding"
        />

        @if(session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">Please check the highlighted upload fields. Your current branding has not been replaced.</div>
        @endif

        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Branding</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Storefront Logo</h2>
                <p class="mt-2 text-sm font-semibold leading-6 text-slate-500">The logo appears across the storefront and supported documents. Leave the upload empty to keep the current one.</p>
            </div>
            <div class="grid gap-6 p-5 sm:p-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                <div class="space-y-5">
                    <div>
                        <label class="admin-label" for="storefront-logo">Upload / replace logo</label>
                        <input id="storefront-logo" type="file" name="logo" accept="image/jpeg,image/png,image/webp,image/avif" class="admin-input h-auto py-3 @error('logo') border-red-400 @enderror" data-content-file @disabled(! $canManageBranding)>
                        <p class="mt-2 text-xs font-semibold leading-5 text-slate-500">JPG, PNG, WebP or AVIF · Maximum 5 MB · Transparent PNG or WebP recommended.</p>
                        @error('logo')<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                    </div>
                    @if($hasCustomLogo)
                        <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm font-bold text-slate-700">
                            <input type="hidden" name="remove_logo" value="0">
                            <input type="checkbox" name="remove_logo" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-red focus:ring-brand-red" @checked(old('remove_logo')) @disabled(! $canManageBranding)>
                            <span><span class="block text-brand-ink">Remove uploaded logo</span><span class="mt-1 block text-xs font-semibold leading-5 text-slate-500">Revert to the built-in NextPlay logo.</span></span>
                        </label>
                    @endif
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-black uppercase tracking-[.18em] text-slate-500">Current logo</p>
                    <div class="mt-3 flex min-h-36 items-center justify-center rounded-xl border border-slate-200 bg-white p-5">
                        <img src="{{ $logoUrl }}" alt="Current NextPlay storefront logo" class="max-h-28 w-auto max-w-full object-contain">
                    </div>
                    <p class="mt-3 text-xs font-semibold text-slate-500">{{ $hasCustomLogo ? 'Uploaded logo is active.' : 'Default logo is active.' }}</p>
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Branding</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Website Favicon</h2>
                <p class="mt-2 text-sm font-semibold leading-6 text-slate-500">The small icon shown in browser tabs for the storefront and admin pages. A square 32×32 or 48×48 image works best.</p>
            </div>
            <div class="grid gap-6 p-5 sm:p-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                <div class="space-y-5">
                    <div>
                        <label class="admin-label" for="storefront-favicon">Upload / replace favicon</label>
                        <input id="storefront-favicon" type="file" name="favicon" accept=".ico,image/x-icon,image/vnd.microsoft.icon,image/jpeg,image/png,image/webp,image/avif" class="admin-input h-auto py-3 @error('favicon') border-red-400 @enderror" data-content-file @disabled(! $canManageBranding)>
                        <p class="mt-2 text-xs font-semibold leading-5 text-slate-500">ICO, PNG, JPG, WebP or AVIF · Maximum 2 MB · A square PNG is recommended.</p>
                        @error('favicon')<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                    </div>
                    @if($hasCustomFavicon)
                        <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm font-bold text-slate-700">
                            <input type="hidden" name="remove_favicon" value="0">
                            <input type="checkbox" name="remove_favicon" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-red focus:ring-brand-red" @checked(old('remove_favicon')) @disabled(! $canManageBranding)>
                            <span><span class="block text-brand-ink">Remove uploaded favicon</span><span class="mt-1 block text-xs font-semibold leading-5 text-slate-500">Revert to the built-in NextPlay icon.</span></span>
                        </label>
                    @endif
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-black uppercase tracking-[.18em] text-slate-500">Current favicon</p>
                    <div class="mt-3 flex min-h-36 items-center justify-center rounded-xl border border-slate-200 bg-white p-5">
                        <img src="{{ $faviconUrl }}" alt="Current NextPlay browser favicon" class="h-14 w-14 object-contain">
                    </div>
                    <p class="mt-3 text-xs font-semibold text-slate-500">{{ $hasCustomFavicon ? 'Uploaded favicon is active.' : 'Default favicon is active.' }}</p>
                </div>
            </div>
        </section>

        <div class="sticky bottom-4 z-20 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-lg backdrop-blur">
            <p class="text-xs font-semibold text-slate-500">Changes appear after saving. Your existing storefront pages remain unchanged.</p>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn btn-white">Preview Storefront</a>
                @if($canManageBranding)<button type="submit" class="btn btn-red">Save Branding</button>@endif
            </div>
        </div>
    </form>
</x-layouts.admin>
