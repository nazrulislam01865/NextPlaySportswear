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
        $branding = StorefrontBrandingSetting::query()->first();

        return view('admin.storefront-branding.edit', [
            'branding' => $branding,
            'logoUrl' => StorefrontBranding::logoUrl(),
            'hasCustomLogo' => StorefrontBranding::hasCustomLogo(),
            'canManageBranding' => (bool) (auth('admin')->user()?->canAdmin('storefront_branding.manage') ?? false),
        ]);
    }

    public function update(StorefrontBrandingRequest $request): RedirectResponse
    {
        $branding = StorefrontBrandingSetting::query()->firstOrCreate([]);
        $previousPath = filled($branding->logo_path) ? (string) $branding->logo_path : null;
        $newPath = null;
        $removeLogo = $request->boolean('remove_logo');

        if ($request->hasFile('logo')) {
            $newPath = $request->file('logo')->store('branding', 'public');
            $branding->update(['logo_path' => $newPath]);
        } elseif ($removeLogo && $previousPath !== null) {
            $branding->update(['logo_path' => null]);
        }

        $shouldDeletePrevious = $previousPath !== null
            && $previousPath !== $newPath
            && ($newPath !== null || $removeLogo)
            && str_starts_with(ltrim(str_replace('\\', '/', $previousPath), '/'), 'branding/');

        if ($shouldDeletePrevious) {
            Storage::disk('public')->delete($previousPath);
        }

        StorefrontBranding::flushCache();

        return redirect()
            ->route('admin.storefront-branding.edit')
            ->with('status', $newPath !== null
                ? 'Storefront logo updated successfully.'
                : ($removeLogo ? 'Custom storefront logo removed. The default logo is now active.' : 'Storefront branding is unchanged.'));
    }
}
