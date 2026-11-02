<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TermsPageRequest;
use App\Services\Storefront\TermsPageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class TermsPageController extends Controller
{
    public function __construct(
        private readonly TermsPageService $service
    ) {
    }

    public function edit(): View
    {
        return view('admin.terms-page.edit', [
            'content' => $this->service->settings(),
            'canManage' => (bool) (auth('admin')->user()?->canAdmin('terms_page.manage') ?? false),
        ]);
    }

    public function update(TermsPageRequest $request): RedirectResponse
    {
        try {
            $this->service->update(
                $request->validated(),
                auth('admin')->id()
            );
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.terms-page.edit')
                ->withInput()
                ->withErrors(['terms_page' => 'The Terms and Conditions page could not be saved: ' . $e->getMessage()]);
        }

        return redirect()
            ->route('admin.terms-page.edit')
            ->with('status', 'Terms and Conditions updated successfully.');
    }
}

