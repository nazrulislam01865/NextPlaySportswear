<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorefrontBrandingRequest;
use App\Models\StorefrontBrandingSetting;
use App\Support\StorefrontBranding;
use Illuminate\Http\RedirectResponse;
use App\Services\Catalog\StoredPublicImage;
use Illuminate\View\View;

class StorefrontBrandingController extends Controller
{
    public function edit(): View
    {
        return view('admin.storefront-branding.edit', [
            'logoUrl' => StorefrontBranding::logoUrl(),
            'hasCustomLogo' => StorefrontBranding::hasCustomLogo(),
            'footerLogoUrl' => StorefrontBranding::footerLogoUrl(),
            'hasCustomFooterLogo' => StorefrontBranding::hasCustomFooterLogo(),
            'faviconUrl' => StorefrontBranding::faviconUrl(),
            'hasCustomFavicon' => StorefrontBranding::hasCustomFavicon(),
            'canManageBranding' => (bool) (auth('admin')->user()?->canAdmin('storefront_branding.manage') ?? false),
        ]);
    }

    public function update(StorefrontBrandingRequest $request): RedirectResponse
    {
        $branding = StorefrontBrandingSetting::query()->firstOrCreate([]);
        $previousPath = filled($branding->logo_path) ? (string) $branding->logo_path : null;
        $newPath = null;
        $changes = [];
        $removeLogo = $request->boolean('remove_logo');

        if ($request->hasFile('logo')) {
            $newPath = StoredPublicImage::replace($request->file('logo'), 'branding', null);
            $changes['logo_path'] = $newPath;
        } elseif ($removeLogo && $previousPath !== null) {
            $changes['logo_path'] = null;
        }

        $previousFooterPath = filled($branding->footer_logo_path) ? (string) $branding->footer_logo_path : null;
        $newFooterPath = null;
        $removeFooterLogo = $request->boolean('remove_footer_logo');

        if ($request->hasFile('footer_logo')) {
            $newFooterPath = StoredPublicImage::replace($request->file('footer_logo'), 'branding', null);
            $changes['footer_logo_path'] = $newFooterPath;
        } elseif ($removeFooterLogo && $previousFooterPath !== null) {
            $changes['footer_logo_path'] = null;
        }

        $previousFaviconPath = filled($branding->favicon_path) ? (string) $branding->favicon_path : null;
        $newFaviconPath = null;
        $removeFavicon = $request->boolean('remove_favicon');

        if ($request->hasFile('favicon')) {
            $newFaviconPath = StoredPublicImage::replace($request->file('favicon'), 'branding', null);
            $changes['favicon_path'] = $newFaviconPath;
        } elseif ($removeFavicon && $previousFaviconPath !== null) {
            $changes['favicon_path'] = null;
        }

        if ($changes !== []) {
            $branding->update($changes);
        }

        $shouldDeletePrevious = $previousPath !== null
            && $previousPath !== $newPath
            && ($newPath !== null || $removeLogo)
            && $previousPath !== ($changes['footer_logo_path'] ?? $branding->footer_logo_path)
            && str_starts_with(ltrim(str_replace('\\', '/', $previousPath), '/'), 'branding/');

        if ($shouldDeletePrevious) {
            StoredPublicImage::retire($previousPath);
        }

        if ($previousFooterPath !== null
            && $previousFooterPath !== $newFooterPath
            && ($newFooterPath !== null || $removeFooterLogo)
            && $previousFooterPath !== ($changes['logo_path'] ?? $branding->logo_path)
            && str_starts_with(ltrim(str_replace('\\', '/', $previousFooterPath), '/'), 'branding/')) {
            StoredPublicImage::retire($previousFooterPath);
        }

        if ($previousFaviconPath !== null
            && $previousFaviconPath !== $newFaviconPath
            && ($newFaviconPath !== null || $removeFavicon)
            && str_starts_with(ltrim(str_replace('\\', '/', $previousFaviconPath), '/'), 'branding/')) {
            StoredPublicImage::retire($previousFaviconPath);
        }

        StorefrontBranding::flushCache();
        // Branding changes are rare: publish only on update, never on storefront page loads.
        if ($changes !== []) {
            app(\App\Services\FlowTrackBrandingPublisher::class)->publish();
        }

        return redirect()
            ->route('admin.storefront-branding.edit')
            ->with('status', ($newPath !== null || $removeLogo || $newFooterPath !== null || $removeFooterLogo || $newFaviconPath !== null || $removeFavicon)
                ? 'Branding updated successfully.'
                : 'Storefront branding is unchanged.');
    }
}
