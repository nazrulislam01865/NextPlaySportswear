from pathlib import Path
r=Path('/mnt/data/nextplay_logo_work')
def edit(path,a,b):
 p=r/path; x=p.read_text(); assert a in x, f'not found in {path}: {a[:55]}'; p.write_text(x.replace(a,b,1))

(r/'database/migrations/2026_10_26_000001_add_footer_logo_to_storefront_branding_settings_table.php').write_text('''<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefront_branding_settings', function (Blueprint $table): void {
            $table->text('footer_logo_path')->nullable()->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('storefront_branding_settings', function (Blueprint $table): void {
            $table->dropColumn('footer_logo_path');
        });
    }
};
''')
edit('app/Models/StorefrontBrandingSetting.php', "    'logo_path',\n", "    'logo_path',\n    'footer_logo_path',\n")
p=r/'app/Support/StorefrontBranding.php'
s=p.read_text().replace("storefront.branding.assets.v2", "storefront.branding.assets.v3")
s=s.replace('array{logo_path:?string,favicon_path:?string}', 'array{logo_path:?string,footer_logo_path:?string,favicon_path:?string}')
s=s.replace('    public static function faviconUrl(): string\n', '''    public static function footerLogoUrl(): string
    {
        $path = self::settings()['footer_logo_path'];

        return filled($path) ? (string) PublicMedia::url($path) : self::logoUrl();
    }

    public static function hasCustomFooterLogo(): bool
    {
        return filled(self::settings()['footer_logo_path']);
    }

    public static function hasFooterImageLogo(): bool
    {
        return self::hasCustomFooterLogo() || self::hasCustomLogo();
    }

    public static function faviconUrl(): string
''')
s=s.replace("['logo_path' => null, 'favicon_path' => null]", "['logo_path' => null, 'footer_logo_path' => null, 'favicon_path' => null]")
s=s.replace("'logo_path' => filled($setting?->logo_path) ? (string) $setting->logo_path : null,", "'logo_path' => filled($setting?->logo_path) ? (string) $setting->logo_path : null,\n                    'footer_logo_path' => filled($setting?->footer_logo_path) ? (string) $setting->footer_logo_path : null,")
p.write_text(s)

p=r/'app/Http/Controllers/Admin/StorefrontBrandingController.php'
s=p.read_text()
s=s.replace("'hasCustomLogo' => StorefrontBranding::hasCustomLogo(),", "'hasCustomLogo' => StorefrontBranding::hasCustomLogo(),\n            'footerLogoUrl' => StorefrontBranding::footerLogoUrl(),\n            'hasCustomFooterLogo' => StorefrontBranding::hasCustomFooterLogo(),")
s=s.replace("        $previousFaviconPath =", '''        $previousFooterPath = filled($branding->footer_logo_path) ? (string) $branding->footer_logo_path : null;
        $newFooterPath = null;
        $removeFooterLogo = $request->boolean('remove_footer_logo');

        if ($request->hasFile('footer_logo')) {
            $newFooterPath = StoredPublicImage::replace($request->file('footer_logo'), 'branding', null);
            $changes['footer_logo_path'] = $newFooterPath;
        } elseif ($removeFooterLogo && $previousFooterPath !== null) {
            $changes['footer_logo_path'] = null;
        }

        $previousFaviconPath =''')
s=s.replace("            && str_starts_with(ltrim(str_replace('\\\\', '/', $previousPath), '/'), 'branding/');", "            && $previousPath !== ($changes['footer_logo_path'] ?? $branding->footer_logo_path)\n            && str_starts_with(ltrim(str_replace('\\\\', '/', $previousPath), '/'), 'branding/');")
s=s.replace("        if ($previousFaviconPath !== null", '''        if ($previousFooterPath !== null
            && $previousFooterPath !== $newFooterPath
            && ($newFooterPath !== null || $removeFooterLogo)
            && $previousFooterPath !== ($changes['logo_path'] ?? $branding->logo_path)
            && str_starts_with(ltrim(str_replace('\\\\', '/', $previousFooterPath), '/'), 'branding/')) {
            StoredPublicImage::retire($previousFooterPath);
        }

        if ($previousFaviconPath !== null''')
s=s.replace("($newPath !== null || $removeLogo || $newFaviconPath !== null || $removeFavicon)", "($newPath !== null || $removeLogo || $newFooterPath !== null || $removeFooterLogo || $newFaviconPath !== null || $removeFavicon)")
s=s.replace('Storefront branding updated successfully.', 'Branding updated successfully.')
p.write_text(s)

edit('app/Http/Requests/Admin/StorefrontBrandingRequest.php', "            'remove_logo' => ['nullable', 'boolean'],", """            'remove_logo' => ['nullable', 'boolean'],
            'footer_logo' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,avif',
                'mimetypes:image/jpeg,image/png,image/webp,image/avif',
                'max:5120',
            ],
            'remove_footer_logo' => ['nullable', 'boolean'],""")

p=r/'resources/views/components/storefront/brand-logo.blade.php'
s=p.read_text()
s=s.replace("    $logoUrl = \\App\\Support\\StorefrontBranding::logoUrl();\n    $hasCustomLogo = \\App\\Support\\StorefrontBranding::hasCustomLogo();", """    $logoUrl = $variant === 'footer'
        ? \\App\\Support\\StorefrontBranding::footerLogoUrl()
        : \\App\\Support\\StorefrontBranding::logoUrl();
    $hasCustomLogo = $variant === 'footer'
        ? \\App\\Support\\StorefrontBranding::hasFooterImageLogo()
        : \\App\\Support\\StorefrontBranding::hasCustomLogo();""")
s=s.replace("'footer' => 'display:block;max-width:190px;width:auto;height:48px;object-fit:contain;object-position:left center;'", "'footer' => 'display:block;max-width:190px;width:auto;max-height:48px;height:auto;object-fit:contain;object-position:left center;background:transparent;'")
s=s.replace("'document' => 'display:block;max-width:210px;width:auto;height:58px;object-fit:contain;object-position:left center;'", "'document' => 'display:block;max-width:210px;width:auto;height:58px;object-fit:contain;object-position:left center;background:transparent;'")
s=s.replace("'error' => 'display:block;max-width:210px;width:auto;height:48px;object-fit:contain;object-position:left center;'", "'error' => 'display:block;max-width:210px;width:auto;height:48px;object-fit:contain;object-position:left center;background:transparent;'")
s=s.replace("default => 'display:block;max-width:220px;width:auto;height:46px;object-fit:contain;object-position:left center;'", "default => 'display:block;max-width:220px;width:auto;height:46px;object-fit:contain;object-position:left center;background:transparent;'")
p.write_text(s)

p=r/'resources/views/admin/storefront-branding/edit.blade.php'
s=p.read_text()
s=s.replace('Manage the logo and browser favicon used across NextPlay. Existing brand assets remain active until you replace them.', 'Manage the main logo for the storefront and admin panel, a separate footer logo, and the browser favicon.')
s=s.replace('The logo appears across the storefront and supported documents. Leave the upload empty to keep the current one.', 'The main logo appears in the storefront header, admin sidebar, admin login and supported documents. Leave empty to keep the current one.')
s=s.replace('JPG, PNG, WebP or AVIF · Maximum 5 MB · Transparent PNG or WebP recommended.', 'JPG, PNG, WebP or AVIF · Maximum 5 MB · Use a PNG or WebP with a transparent background for a logo without a colored box.', 1)
anchor='''        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Branding</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Website Favicon</h2>'''
new='''        <section class="rounded-3xl border border-slate-200 bg-white shadow-card">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <p class="text-xs font-black uppercase tracking-[.22em] text-brand-red">Branding</p>
                <h2 class="mt-2 text-2xl font-black text-brand-ink">Footer Logo</h2>
                <p class="mt-2 text-sm font-semibold leading-6 text-slate-500">Upload a separate logo for the dark footer. If none is uploaded, the footer uses your main logo (or its built-in fallback).</p>
            </div>
            <div class="grid gap-6 p-5 sm:p-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                <div class="space-y-5">
                    <div>
                        <label class="admin-label" for="storefront-footer-logo">Upload / replace footer logo</label>
                        <input id="storefront-footer-logo" type="file" name="footer_logo" accept="image/jpeg,image/png,image/webp,image/avif" class="admin-input h-auto py-3 @error('footer_logo') border-red-400 @enderror" data-content-file @disabled(! $canManageBranding)>
                        <p class="mt-2 text-xs font-semibold leading-5 text-slate-500">JPG, PNG, WebP or AVIF · Maximum 5 MB · A transparent PNG or WebP avoids white/colored backgrounds on the dark footer.</p>
                        @error('footer_logo')<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                    </div>
                    @if($hasCustomFooterLogo)
                        <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm font-bold text-slate-700">
                            <input type="hidden" name="remove_footer_logo" value="0">
                            <input type="checkbox" name="remove_footer_logo" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-red focus:ring-brand-red" @checked(old('remove_footer_logo')) @disabled(! $canManageBranding)>
                            <span><span class="block text-brand-ink">Remove uploaded footer logo</span><span class="mt-1 block text-xs font-semibold leading-5 text-slate-500">Use the main logo in the footer again.</span></span>
                        </label>
                    @endif
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-black uppercase tracking-[.18em] text-slate-500">Current footer logo</p>
                    <div class="mt-3 flex min-h-36 items-center justify-center rounded-xl border border-slate-200 bg-brand-dark p-5">
                        @if($hasCustomFooterLogo || $hasCustomLogo)
                            <img src="{{ $footerLogoUrl }}" alt="Current NextPlay footer logo" class="max-h-28 w-auto max-w-full object-contain" style="background:transparent">
                        @else
                            <span class="text-lg font-bold text-white">NextPlay <span class="text-brand-red">Sportswear</span></span>
                        @endif
                    </div>
                    <p class="mt-3 text-xs font-semibold text-slate-500">{{ $hasCustomFooterLogo ? 'Separate footer logo is active.' : 'Using the main logo or built-in footer branding.' }}</p>
                </div>
            </div>
        </section>

'''+anchor
assert anchor in s; s=s.replace(anchor,new,1)
s=s.replace('Changes appear after saving. Your existing storefront pages remain unchanged.', 'Changes appear across the storefront and admin after saving.')
p.write_text(s)

(r/'resources/views/components/admin/brand-identity.blade.php').write_text('''@props(['variant' => 'sidebar'])

@php
    $isLogin = $variant === 'login';
    $hasCustomLogo = \\App\\Support\\StorefrontBranding::hasCustomLogo();
@endphp

@if($hasCustomLogo)
    <span class="flex min-w-0 flex-col items-start justify-center gap-1">
        <img
            src="{{ \\App\\Support\\StorefrontBranding::logoUrl() }}"
            alt="{{ config('storefront.name', 'NextPlay Sportswear') }}"
            class="block w-auto max-w-full object-contain object-left"
            style="max-width:{{ $isLogin ? '220px' : '175px' }};max-height:{{ $isLogin ? '54px' : '38px' }};background:transparent"
        >
        <span class="block text-[10px] font-black uppercase tracking-[.25em] text-slate-400">Commerce Admin</span>
    </span>
@elseif($isLogin)
    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl border-2 border-brand-red font-black text-brand-red">✓</span>
    <span class="min-w-0">
        <span class="block text-xl font-black text-brand-dark">NextPlay</span>
        <span class="block text-xs font-black uppercase tracking-[.2em] text-slate-400">Commerce Admin</span>
    </span>
@else
    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border-2 border-brand-red text-brand-red">✓</span>
    <span class="min-w-0">
        <span class="block truncate text-lg">NextPlay</span>
        <span class="block truncate text-[10px] uppercase tracking-[.25em] text-slate-400">Commerce Admin</span>
    </span>
@endif
''')
edit('resources/views/components/layouts/admin.blade.php', '''                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border-2 border-brand-red text-brand-red">✓</span>
                    <span class="min-w-0"><span class="block truncate text-lg">NextPlay</span><span class="block truncate text-[10px] uppercase tracking-[.25em] text-slate-400">Commerce Admin</span></span>''', '''                    <x-admin.brand-identity variant="sidebar" />''')
edit('resources/views/admin/auth/login.blade.php', '''            {{-- Admin branding --}}
            <div class="mb-8 flex items-center gap-3">

                <span
                    class="grid h-12 w-12 place-items-center rounded-2xl border-2 border-brand-red font-black text-brand-red"
                >
                    ✓
                </span>

                <div>
                    <p class="text-xl font-black text-brand-dark">
                        NextPlay
                    </p>

                    <p class="text-xs font-black uppercase tracking-[.2em] text-slate-400">
                        Commerce Admin
                    </p>
                </div>

            </div>''', '''            {{-- Shared admin branding --}}
            <div class="mb-8 flex min-w-0 items-center gap-3">
                <x-admin.brand-identity variant="login" />
            </div>''')
print('Branding patch applied')
