<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ArtworkGuidelinesPageRequest;
use App\Services\Storefront\ArtworkGuidelinesPageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class ArtworkGuidelinesPageController extends Controller
{
    public function __construct(
        private readonly ArtworkGuidelinesPageService $service
    ) {
    }

    public function edit(): View
    {
        return view('admin.artwork-guidelines-page.edit', [
            'content' => $this->service->settings(),
            'canManage' => (bool) (auth('admin')->user()?->canAdmin('artwork_guidelines_page.manage') ?? false),
        ]);
    }

    public function update(ArtworkGuidelinesPageRequest $request): RedirectResponse
    {
        try {
            $this->service->update(
                $request->validated(),
                auth('admin')->id()
            );
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.artwork-guidelines-page.edit')
                ->withInput()
                ->withErrors(['artwork_guidelines' => 'The Artwork Guidelines page could not be saved: ' . $e->getMessage()]);
        }

        return redirect()
            ->route('admin.artwork-guidelines-page.edit')
            ->with('status', 'Artwork Guidelines page updated successfully.');
    }
}

