<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SizeGuidePageRequest;
use App\Services\Storefront\SizeGuidePageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class SizeGuidePageController extends Controller
{
    public function edit(SizeGuidePageService $service): View
    {
        return view('admin.size-guide-page.edit', [
            'content' => $service->settings(),
            'canManage' => (bool) (auth('admin')->user()?->canAdmin('size_guide_page.manage') ?? false),
        ]);
    }

    public function update(SizeGuidePageRequest $request, SizeGuidePageService $service): RedirectResponse
    {
        try {
            $service->update($request->validated(), auth('admin')->id());
        } catch (Throwable $error) {
            report($error);
            return redirect()->route('admin.size-guide-page.edit')->withInput()
                ->withErrors(['size_guide' => 'The Size Guide could not be saved. Your previous content is unchanged.']);
        }

        return redirect()->route('admin.size-guide-page.edit')->with('status', 'Size Guide updated successfully.');
    }
}
