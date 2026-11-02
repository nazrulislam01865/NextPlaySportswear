<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContactPageRequest;
use App\Services\Storefront\ContactPageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class ContactPageController extends Controller
{
    public function __construct(
        private readonly ContactPageService $service
    ) {
    }

    public function edit(): View
    {
        return view('admin.contact-page.edit', [
            'content' => $this->service->settings(),
            'canManage' => (bool) (auth('admin')->user()?->canAdmin('contact_page.manage') ?? false),
        ]);
    }

    public function update(ContactPageRequest $request): RedirectResponse
    {
        try {
            $this->service->update(
                $request->validated(),
                auth('admin')->id()
            );
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.contact-page.edit')
                ->withInput()
                ->withErrors(['contact_page' => 'The Contact Us page could not be saved: ' . $e->getMessage()]);
        }

        return redirect()
            ->route('admin.contact-page.edit')
            ->with('status', 'Contact Us page updated successfully.');
    }
}

