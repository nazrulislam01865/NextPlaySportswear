<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HowToOrderPageRequest;
use App\Services\Storefront\HowToOrderPageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class HowToOrderPageController extends Controller
{
    public function __construct(
        private readonly HowToOrderPageService $service
    ) {
    }

    public function edit(): View
    {
        return view('admin.how-to-order-page.edit', [
            'content' => $this->service->settings(),
            'canManage' => (bool) (auth('admin')->user()?->canAdmin('how_to_order_page.manage') ?? false),
        ]);
    }

    public function update(HowToOrderPageRequest $request): RedirectResponse
    {
        try {
            $this->service->update(
                $request->validated(),
                auth('admin')->id()
            );
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.how-to-order-page.edit')
                ->withInput()
                ->withErrors(['how_to_order' => 'The How to Order page could not be saved: ' . $e->getMessage()]);
        }

        return redirect()
            ->route('admin.how-to-order-page.edit')
            ->with('status', 'How to Order page updated successfully.');
    }
}

