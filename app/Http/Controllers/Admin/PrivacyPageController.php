<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PrivacyPageRequest;
use App\Services\Storefront\PrivacyPageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class PrivacyPageController extends Controller
{
    public function __construct(
        private readonly PrivacyPageService $service
    ) {
    }

    public function edit(): View
    {
        return view('admin.privacy-page.edit', [
            'content' => $this->service->settings(),
            'canManage' => (bool) (auth('admin')->user()?->canAdmin('privacy_page.manage') ?? false),
        ]);
    }

    public function update(PrivacyPageRequest $request): RedirectResponse
    {
        try {
            $this->service->update(
                $request->validated(),
                auth('admin')->id()
            );
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.privacy-page.edit')
                ->withInput()
                ->withErrors(['privacy_page' => 'The Privacy Policy page could not be saved: ' . $e->getMessage()]);
        }

        return redirect()
            ->route('admin.privacy-page.edit')
            ->with('status', 'Privacy Policy updated successfully.');
    }
}

