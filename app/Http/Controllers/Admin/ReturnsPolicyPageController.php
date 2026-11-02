<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReturnsPolicyPageRequest;
use App\Services\Storefront\ReturnsPolicyPageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class ReturnsPolicyPageController extends Controller
{
    public function __construct(
        private readonly ReturnsPolicyPageService $service
    ) {
    }

    public function edit(): View
    {
        return view('admin.returns-policy-page.edit', [
            'content' => $this->service->settings(),
            'canManage' => (bool) (auth('admin')->user()?->canAdmin('returns_policy_page.manage') ?? false),
        ]);
    }

    public function update(ReturnsPolicyPageRequest $request): RedirectResponse
    {
        try {
            $this->service->update(
                $request->validated(),
                auth('admin')->id()
            );
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.returns-policy-page.edit')
                ->withInput()
                ->withErrors(['returns_policy' => 'The Returns Policy page could not be saved: ' . $e->getMessage()]);
        }

        return redirect()
            ->route('admin.returns-policy-page.edit')
            ->with('status', 'Returns & Refunds policy updated successfully.');
    }
}

