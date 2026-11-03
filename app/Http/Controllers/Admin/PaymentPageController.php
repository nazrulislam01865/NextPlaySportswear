<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentPageRequest;
use App\Services\Storefront\PaymentPageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaymentPageController extends Controller
{
    public function __construct(private readonly PaymentPageService $service)
    {
    }

    public function edit(): View
    {
        return view('admin.payment-page.edit', [
            'content' => $this->service->settings(),
            'canManage' => (bool) (auth('admin')->user()?->canAdmin('payment_page.manage') ?? false),
        ]);
    }

    public function update(PaymentPageRequest $request): RedirectResponse
    {
        $this->service->update($request->validated(), auth('admin')->id());

        return redirect()
            ->route('admin.payment-page.edit')
            ->with('status', 'Payment Information page updated successfully.');
    }
}
