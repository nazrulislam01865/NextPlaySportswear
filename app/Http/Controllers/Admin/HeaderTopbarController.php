<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HeaderTopbarRequest;
use App\Services\Storefront\HeaderTopbarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HeaderTopbarController extends Controller
{
    public function __construct(private readonly HeaderTopbarService $service)
    {
    }

    public function edit(): View
    {
        return view('admin.header-topbar.edit', [
            'settings' => $this->service->settings(),
            'canManage' => (bool) auth('admin')->user()?->canAdmin('header_topbar.manage'),
        ]);
    }

    public function update(HeaderTopbarRequest $request): RedirectResponse
    {
        $this->service->update($request->validated(), auth('admin')->id());

        return redirect()->route('admin.header-topbar-settings.edit')
            ->with('status', 'Header & Top Bar settings saved successfully.');
    }
}
