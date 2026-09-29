<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AboutPageRequest;
use App\Models\AboutPageSetting;
use App\Services\Catalog\AboutPageMediaService;
use App\Services\Storefront\AboutPageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class AboutPageController extends Controller
{
    public function __construct(
        private readonly AboutPageService $aboutPage,
        private readonly AboutPageMediaService $media,
    ) {
    }

    public function edit(): View
    {
        return view('admin.about-page.edit', [
            'about' => $this->aboutPage->settings(),
            'canManageAboutPage' => (bool) (auth('admin')->user()?->canAdmin('about_page.manage') ?? false),
        ]);
    }

    public function update(AboutPageRequest $request): RedirectResponse
    {
        $current = $this->aboutPage->settings();
        $adminId = auth('admin')->id();
        $mutation = null;

        try {
            $mutation = $this->media->prepare($request, $current, $request->validatedContent());

            DB::transaction(function () use ($mutation, $adminId): void {
                $setting = AboutPageSetting::query()->first() ?? new AboutPageSetting();

                $setting->fill($mutation['payload']);
                if (! $setting->exists) {
                    $setting->created_by = $adminId;
                }
                $setting->updated_by = $adminId;
                $setting->save();
            });
        } catch (Throwable $exception) {
            if (is_array($mutation)) {
                $this->media->rollback($mutation);
            }
            report($exception);

            return redirect()
                ->route('admin.about-page.edit')
                ->withInput()
                ->withErrors(['about_page' => 'The About page could not be saved. Your existing content and media were kept unchanged.']);
        }

        try {
            $this->media->commitCleanup($mutation);
        } catch (Throwable $cleanupException) {
            // Content is already committed; leaving an old About-owned file is safer than
            // reporting a failed save or deleting newly-active media.
            report($cleanupException);
        }

        return redirect()
            ->route('admin.about-page.edit')
            ->with('status', 'About page updated successfully.');
    }
}
