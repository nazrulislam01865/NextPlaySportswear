<?php

namespace App\Console\Commands;

use App\Models\ProductDetailUiSetting;
use App\Models\ShippingMethod;
use App\Services\Catalog\ProductDetailIconNormalizer;
use App\Services\Storefront\ProductCatalogCacheService;
use App\Support\ProductStorefrontUi;
use Illuminate\Console\Command;

/**
 * Repairs existing locally stored icons once; never processes product photos
 * or remote images. Old source files remain available for rollback.
 */
final class RepairProductIconBackgrounds extends Command
{
    protected $signature = 'nextplay:repair-icon-backgrounds {--apply : Save transparent PNG replacements; without this flag only inspect the records}';

    protected $description = 'Remove flat light backgrounds from locally uploaded product-detail and shipping-method icons';

    public function handle(ProductCatalogCacheService $catalogCache): int
    {
        if (! ProductDetailIconNormalizer::available()) {
            $this->error('PHP GD is required for icon repair. Enable GD for your CLI PHP version.');
            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $checked = 0;
        $changed = 0;
        $shippingChanged = 0;
        $record = ProductDetailUiSetting::query()->find(1);

        if ($record) {
            $settings = (array) ($record->settings ?? []);
            foreach (ProductStorefrontUi::iconKeys() as $key) {
                $path = trim((string) ($settings[$key] ?? ''));
                if (! str_starts_with($path, 'storefront/product-detail-ui/')) {
                    continue;
                }

                $checked++;
                if (! $apply) {
                    $this->line("Product detail icon: {$key}");
                    continue;
                }

                $replacement = ProductDetailIconNormalizer::normalize($path);
                if ($replacement !== $path) {
                    $settings[$key] = $replacement;
                    $record->settings = $settings;
                    $record->save();
                    $changed++;
                }
            }
        }

        // Read only locally uploaded master shipping-method icons. No
        // additional image queries are added to customer-facing requests.
        foreach (ShippingMethod::query()
            ->select(['id', 'image_path'])
            ->where('image_path', 'like', 'master-data/shipping-methods/%')
            ->get() as $method) {
            $path = trim((string) $method->image_path);
            $checked++;
            if (! $apply) {
                $this->line("Shipping method icon: {$method->id}");
                continue;
            }

            $replacement = ProductDetailIconNormalizer::normalize($path);
            if ($replacement !== $path) {
                $method->image_path = $replacement;
                $method->save();
                $changed++;
                $shippingChanged++;
            }
        }

        if ($changed && $apply) {
            ProductStorefrontUi::flushCache();
            if ($shippingChanged) {
                $catalogCache->flush();
            }
        }

        if (! $apply) {
            $this->info("Found {$checked} local icon references. No files or database records changed. Use --apply to repair them.");
        } else {
            $this->info("Checked {$checked} local icon references; generated {$changed} normalized versions. Original files were preserved.");
        }

        return self::SUCCESS;
    }
}
