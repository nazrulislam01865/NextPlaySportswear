<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NavigationSettingsRequest;
use App\Services\Storefront\StorefrontSettingsMediaService;
use App\Services\Storefront\StorefrontSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class NavigationSettingsController extends Controller
{
    public function __construct(
        private readonly StorefrontSettingsService $settings,
        private readonly StorefrontSettingsMediaService $media,
    ) {
    }

    public function edit(): View
    {
        return view('admin.storefront-settings.navigation', [
            'settings' => $this->settings->navigation(),
        ]);
    }

    public function update(NavigationSettingsRequest $request): RedirectResponse
    {
        $before = $this->settings->navigation();
        $prepared = $this->media->prepareNavigation($request, $request->settings());

        try {
            $this->settings->updateNavigation($prepared, $request->user()?->id);
        } catch (Throwable $exception) {
            $this->media->cleanupNewUploads($before, $prepared);
            throw $exception;
        }

        $this->media->cleanupRemovedMedia($before, $prepared);

        return redirect()
            ->route('admin.navigation-settings.edit')
            ->with('status', 'Navigation menu updated successfully.');
    }
}
