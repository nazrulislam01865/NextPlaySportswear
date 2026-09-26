<x-layouts.admin
    title="Storefront Branding"
    subtitle="Upload the logo used across the storefront, documents, and storefront error pages."
>
    <div class="mx-auto max-w-4xl space-y-5">
        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Branding</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Storefront Logo</h2>
                <p class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-slate-500">Upload one logo here and it will be used anywhere the storefront displays the NextPlay brand logo. If no custom logo is set, the current default logo remains active.</p>
            </div>

            <form method="POST" action="{{ route('admin.storefront-branding.update') }}" enctype="multipart/form-data" class="space-y-6 p-5 sm:p-6">
                @csrf
                @method('PUT')

                @if(session('status'))
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
                @endif

                <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(280px,.8fr)]">
                    <div>
                        <label class="admin-label" for="storefront-logo">Upload logo</label>
                        <input
                            id="storefront-logo"
                            type="file"
                            name="logo"
                            accept="image/jpeg,image/png,image/webp,image/avif"
                            class="admin-input h-auto py-3 @error('logo') border-red-400 @enderror"
                            @disabled(! $canManageBranding)
                        >
                        <p class="mt-2 text-xs font-semibold leading-5 text-slate-500">JPG, PNG, WebP or AVIF. Maximum file size: 5 MB. A transparent PNG or WebP is recommended.</p>
                        @error('logo')
                            <p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>
                        @enderror

                        @if($hasCustomLogo)
                            <label class="mt-5 flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm font-bold text-slate-700">
                                <input type="hidden" name="remove_logo" value="0">
                                <input type="checkbox" name="remove_logo" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-red focus:ring-brand-red" @disabled(! $canManageBranding)>
                                <span><span class="block text-brand-ink">Remove uploaded logo</span><span class="mt-1 block text-xs font-semibold leading-5 text-slate-500">The current built-in NextPlay logo will be used again.</span></span>
                            </label>
                        @endif
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <p class="text-xs font-black uppercase tracking-[.18em] text-slate-500">Current logo</p>
                        <div class="mt-4 flex min-h-32 items-center justify-center rounded-xl border border-slate-200 bg-white p-5">
                            <img src="{{ $logoUrl }}" alt="{{ config('storefront.name', 'NextPlay Sportswear') }}" style="display:block;max-width:100%;width:auto;max-height:96px;object-fit:contain;">
                        </div>
                        <p class="mt-3 text-xs font-semibold text-slate-500">{{ $hasCustomLogo ? 'Uploaded logo is active.' : 'Default logo is active.' }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-5">
                    <a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn btn-white">Preview Storefront</a>
                    @if($canManageBranding)
                        <button type="submit" class="btn btn-primary">Save Branding</button>
                    @endif
                </div>
            </form>
        </section>
    </div>
</x-layouts.admin>
