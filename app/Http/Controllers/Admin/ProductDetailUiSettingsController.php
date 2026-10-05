<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductDetailUiSettingsRequest;
use App\Models\ProductDetailUiSetting;
use App\Services\Storefront\ProductCatalogCacheService;
use App\Support\ProductStorefrontUi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductDetailUiSettingsController extends Controller
{
    public function __construct(
        private readonly ProductCatalogCacheService $productCatalogCache,
    ) {
    }

    public function edit(): View
    {
        return view('admin.product-detail-controls.edit', [
            'settings' => ProductStorefrontUi::globalSettings(),
            'canManage' => (bool) ((auth('admin')->user()?->canAdmin('product_detail_controls.manage') ?? false)
                || (auth('admin')->user()?->canAdmin('products.manage') ?? false)),
        ]);
    }

    public function update(ProductDetailUiSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $defaults = ProductStorefrontUi::defaults();
        $current = ProductStorefrontUi::globalSettings();
        $submitted = is_array($validated['settings'] ?? null) ? $validated['settings'] : [];
        $settings = $current;
        $iconKeys = ProductStorefrontUi::iconKeys();
        $booleanKeys = ProductStorefrontUi::booleanKeys();

        foreach ($defaults as $key => $default) {
            if (in_array($key, $iconKeys, true)) {
                continue;
            }

            if (in_array($key, $booleanKeys, true)) {
                $settings[$key] = filter_var($submitted[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
                continue;
            }

            if (! array_key_exists($key, $submitted)) {
                continue;
            }

            $value = trim((string) ($submitted[$key] ?? ''));
            $settings[$key] = $value !== '' ? $value : $default;
        }

        foreach ($iconKeys as $key) {
            $existingPath = trim((string) ($current[$key] ?? ''));
            $path = $existingPath;
            $clear = filter_var(data_get($validated, "clear_icons.{$key}", false), FILTER_VALIDATE_BOOLEAN);
            $upload = $request->file("icon_files.{$key}");

            if ($clear) {
                $path = '';
            }

            if ($upload) {
                $path = $upload->store('storefront/product-detail-ui', 'public');
            }

            if (
                $existingPath !== ''
                && $existingPath !== $path
                && str_starts_with(ltrim(str_replace('\\', '/', $existingPath), '/'), 'storefront/product-detail-ui/')
            ) {
                Storage::disk('public')->delete($existingPath);
            }

            $settings[$key] = $path !== '' ? $path : null;
        }

        ProductDetailUiSetting::query()->updateOrCreate(
            ['id' => 1],
            ['settings' => $settings]
        );

        ProductStorefrontUi::flushCache();
        $this->productCatalogCache->flush();

        return redirect()
            ->route('admin.product-detail-controls.edit')
            ->with('status', 'Product detail controls updated. The changes now apply to all product detail pages.');
    }
}
