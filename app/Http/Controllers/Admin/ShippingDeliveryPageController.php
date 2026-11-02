<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ShippingDeliveryPageRequest;
use App\Models\ShippingDeliveryPageSetting;
use App\Services\Catalog\ShippingDeliveryPageMediaService;
use App\Services\Storefront\ShippingDeliveryPageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class ShippingDeliveryPageController extends Controller
{
    public function __construct(
        private readonly ShippingDeliveryPageService $shippingDeliveryPage,
        private readonly ShippingDeliveryPageMediaService $media,
    ) {
    }

    public function edit(): View
    {
        return view('admin.shipping-delivery-page.edit', [
            'shippingDelivery' => $this->shippingDeliveryPage->settings(),
            'canManageShippingDeliveryPage' => (bool) (auth('admin')->user()?->canAdmin('shipping_delivery_page.manage') ?? false),
        ]);
    }

    public function update(ShippingDeliveryPageRequest $request): RedirectResponse
    {
        $current = $this->shippingDeliveryPage->settings();
        $adminId = auth('admin')->id();
        $mutation = null;

        try {
            $mutation = $this->media->prepare($request, $current, $request->validatedContent());

            DB::transaction(function () use ($request, $mutation, $adminId): void {
                if ($request->has('sections')) {
                    $storefrontSetting = \App\Models\StorefrontPageSetting::firstOrNew(['page_key' => 'shipping-delivery']);
                    $storefrontSetting->title = $request->input('display_header.title', 'Shipping & Delivery');
                    $storefrontSetting->content = [
                        'display_header' => $request->input('display_header', []),
                        'sections' => $request->input('sections', []),
                    ];
                    $storefrontSetting->seo = $request->input('seo', []);
                    if (! $storefrontSetting->exists && $adminId) {
                        $storefrontSetting->created_by = $adminId;
                    }
                    if ($adminId) {
                        $storefrontSetting->updated_by = $adminId;
                    }
                    $storefrontSetting->save();
                }

                $setting = ShippingDeliveryPageSetting::query()->first() ?? new ShippingDeliveryPageSetting();
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
                ->route('admin.shipping-delivery-page.edit')
                ->withInput()
                ->withErrors(['shipping_delivery_page' => 'The Shipping & Delivery page could not be saved. Your existing content and media were kept unchanged.']);
        }

        try {
            $this->media->commitCleanup($mutation);
        } catch (Throwable $cleanupException) {
            report($cleanupException);
        }

        return redirect()
            ->route('admin.shipping-delivery-page.edit')
            ->with('status', 'Shipping & Delivery page updated successfully.');
    }
}
