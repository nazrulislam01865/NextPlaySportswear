<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FooterSettingsRequest;
use App\Services\Storefront\StorefrontSettingsMediaService;
use App\Services\Storefront\StorefrontSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class FooterSettingsController extends Controller
{
    public function __construct(
        private readonly StorefrontSettingsService $settings,
        private readonly StorefrontSettingsMediaService $media,
    ) {
    }

    public function edit(): View
    {
        return view('admin.storefront-settings.footer', [
            'settings' => $this->settings->footer(),
        ]);
    }

    public function update(FooterSettingsRequest $request): RedirectResponse
    {
        $before = $this->settings->footer();
        $prepared = $this->media->prepareFooter($request, $request->settings());

        try {
            $this->settings->updateFooter($prepared, $request->user()?->id);
        } catch (Throwable $exception) {
            $this->media->cleanupNewUploads($before, $prepared);
            throw $exception;
        }

        $this->media->cleanupRemovedMedia($before, $prepared);

        return redirect()
            ->route('admin.footer-settings.edit')
            ->with('status', 'Footer settings updated successfully.');
    }
}
