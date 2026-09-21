<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HeaderSettingsRequest;
use App\Services\Storefront\StorefrontSettingsMediaService;
use App\Services\Storefront\StorefrontSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class HeaderSettingsController extends Controller
{
    public function __construct(
        private readonly StorefrontSettingsService $settings,
        private readonly StorefrontSettingsMediaService $media,
    ) {
    }

    public function edit(): View
    {
        return view('admin.storefront-settings.header', [
            'settings' => $this->settings->header(),
        ]);
    }

    public function update(HeaderSettingsRequest $request): RedirectResponse
    {
        $before = $this->settings->header();
        $prepared = $this->media->prepareHeader($request, $request->settings());

        try {
            $this->settings->updateHeader($prepared, $request->user()?->id);
        } catch (Throwable $exception) {
            $this->media->cleanupNewUploads($before, $prepared);
            throw $exception;
        }

        $this->media->cleanupRemovedMedia($before, $prepared);

        return redirect()
            ->route('admin.header-settings.edit')
            ->with('status', 'Header settings updated successfully.');
    }
}
