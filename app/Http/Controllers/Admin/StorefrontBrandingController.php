<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorefrontBrandingRequest;
use App\Models\StorefrontBrandingSetting;
use App\Support\StorefrontBranding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StorefrontBrandingController extends Controller
{
    public function edit(): View
    {
        return view('admin.storefront-branding.edit', [
            'logoUrl' => StorefrontBranding::logoUrl(),
            'hasCustomLogo' => StorefrontBranding::hasCustomLogo(),
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
            $newPath = $request->file('logo')->store('branding', 'public');
            $changes['logo_path'] = $newPath;
        } elseif ($removeLogo && $previousPath !== null) {
            $changes['logo_path'] = null;
        }

        $previousFaviconPath = filled($branding->favicon_path) ? (string) $branding->favicon_path : null;
        $newFaviconPath = null;
        $removeFavicon = $request->boolean('remove_favicon');

        if ($request->hasFile('favicon')) {
            $newFaviconPath = $request->file('favicon')->store('branding', 'public');
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
            && str_starts_with(ltrim(str_replace('\\', '/', $previousPath), '/'), 'branding/');

        if ($shouldDeletePrevious) {
            Storage::disk('public')->delete($previousPath);
        }

        if ($previousFaviconPath !== null
            && $previousFaviconPath !== $newFaviconPath
            && ($newFaviconPath !== null || $removeFavicon)
            && str_starts_with(ltrim(str_replace('\\', '/', $previousFaviconPath), '/'), 'branding/')) {
            Storage::disk('public')->delete($previousFaviconPath);
        }

        StorefrontBranding::flushCache();

        return redirect()
            ->route('admin.storefront-branding.edit')
            ->with('status', ($newPath !== null || $removeLogo || $newFaviconPath !== null || $removeFavicon)
                ? 'Storefront branding updated successfully.'
                : 'Storefront branding is unchanged.');
    }
}
