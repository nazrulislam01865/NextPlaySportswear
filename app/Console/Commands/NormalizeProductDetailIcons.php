<?php

namespace App\Console\Commands;

use App\Models\ProductDetailUiSetting;
use App\Services\Catalog\ProductDetailIconNormalizer;
use App\Services\Catalog\StoredPublicImage;
use App\Support\ProductStorefrontUi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class NormalizeProductDetailIcons extends Command
{
    protected $signature = 'nextplay:normalize-product-detail-icons {--dry-run : List existing uploaded icons without changing them}';

    protected $description = 'Standardize existing product detail icons on transparent, evenly sized canvases';

    public function handle(): int
    {
        if (! ProductDetailIconNormalizer::available()) {
            $this->error('PHP GD is required to normalize uploaded icons. Enable gd for the CLI PHP version, then retry.');
            return self::FAILURE;
        }

        $record = ProductDetailUiSetting::query()->whereKey(1)->first();
        if (! $record) {
            $this->info('No customized product detail icons to repair.');
            return self::SUCCESS;
        }

        $settings = (array) ($record->settings ?? []);
        $replaced = 0;
        $checked = 0;
        $dryRun = (bool) $this->option('dry-run');

        foreach (ProductStorefrontUi::iconKeys() as $key) {
            $path = trim((string) ($settings[$key] ?? ''));
            if (! str_starts_with($path, 'storefront/product-detail-ui/')) {
                continue;
            }

            $checked++;
            if ($dryRun) {
                $this->line("Existing icon: {$key}");
                continue;
            }

            $normalized = ProductDetailIconNormalizer::normalize($path);
            if ($normalized === $path) {
                continue;
            }

            // Save each replacement first and retire the original only after
            // the corresponding DB transaction succeeds. Other icons remain untouched.
            DB::transaction(function () use ($record, &$settings, $key, $path, $normalized): void {
                $settings[$key] = $normalized;
                $record->settings = $settings;
                $record->save();
                StoredPublicImage::retire($path, $normalized);
            });
            $replaced++;
        }

        if ($replaced > 0) {
            ProductStorefrontUi::flushCache();
        }

        $this->info("Checked {$checked} uploaded icons; normalized {$replaced}.".($dryRun ? ' (dry run)' : ''));
        return self::SUCCESS;
    }
}
