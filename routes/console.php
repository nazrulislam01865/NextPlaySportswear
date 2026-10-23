<?php

use App\Services\Catalog\CategoryProductAssignmentSyncService;
use App\Services\Catalog\ProductDetailIconNormalizer;
use App\Services\Catalog\StoredPublicImage;
use App\Models\ProductDetailUiSetting;
use App\Support\ProductStorefrontUi;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('catalog:sync-category-products {--keep-existing : Legacy flag; existing category links are always preserved}', function () {
    $stats = app(CategoryProductAssignmentSyncService::class)->syncAllProductCategoryAssignments(resetExisting: false);

    $this->info('Category product assignments repaired.');
    $this->line('Total products in products table: '.$stats['total_products_in_table']);
    $this->line('Published/visible products: '.$stats['published_products_in_table']);
    $this->line('Existing category_product rows removed: '.$stats['assignments_deleted']);
    $this->line('Products checked by legacy fields: '.$stats['products_scanned']);
    $this->line('Products with category_id/subcategory_id: '.$stats['products_with_legacy_category']);
    $this->line('Legacy/ancestor assignments created: '.$stats['legacy_assignments_created']);
    $this->line('Parent/ancestor assignments created: '.$stats['ancestor_assignments_created']);
    $this->line('Trusted rule products checked: '.$stats['trusted_rule_products_scanned']);
    $this->line('Trusted rule products matched: '.$stats['trusted_rule_products_matched']);
    $this->line('Trusted rule assignments created: '.$stats['trusted_rule_assignments_created']);
    $this->line('Trusted rule parent assignments created: '.$stats['trusted_rule_ancestor_assignments_created']);
    $this->line('Existing assignments kept: '.$stats['assignments_existing']);
    $this->line('Primary placements fixed: '.$stats['primary_fixed']);
    $this->line('Invalid category references skipped: '.$stats['invalid_category_references']);
    $this->line('Products without legacy category: '.$stats['products_without_category']);
})->purpose('Add missing category-product assignments without removing existing links');

// One-time repair for icons uploaded before whitespace removal was introduced.
// Updates only the centrally configured icon paths; unrelated images are untouched.
Artisan::command('product-detail:normalize-icons', function () {
    if (! ProductDetailIconNormalizer::available()) {
        $this->error('PHP GD is required to normalize uploaded icons. Enable the gd extension or upload the prepared icon files.');
        return 1;
    }

    $record = ProductDetailUiSetting::query()->find(1);
    if (! $record || ! is_array($record->settings)) {
        $this->warn('No product detail icon settings were found.');
        return 0;
    }

    $settings = $record->settings;
    $changes = [];
    foreach (ProductStorefrontUi::iconKeys() as $key) {
        $old = (string) ($settings[$key] ?? '');
        if (! str_starts_with($old, 'storefront/product-detail-ui/')) {
            continue;
        }
        $updated = ProductDetailIconNormalizer::normalize($old);
        if ($updated !== $old) {
            $settings[$key] = $updated;
            $changes[$old] = $updated;
        }
    }

    if ($changes === []) {
        $this->info('All configured icons are already normalized (or do not need trimming).');
        return 0;
    }

    try {
        $record->settings = $settings;
        $record->save();
    } catch (\Throwable $exception) {
        foreach ($changes as $updated) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($updated);
        }
        throw $exception;
    }

    foreach ($changes as $old => $updated) {
        StoredPublicImage::retire($old, $updated);
    }
    ProductStorefrontUi::flushCache();
    app(\App\Services\Storefront\ProductCatalogCacheService::class)->flush();
    $this->info('Normalized '.count($changes).' icon image(s). Existing selections and icon settings were preserved.');

    return 0;
})->purpose('Remove blank margins from existing uploaded product-detail UI icons');

// Webhooks are primary; reconciliation is the safety net for delayed or missed provider events.
Schedule::command('payments:reconcile')->everyFifteenMinutes()->withoutOverlapping();

// Sync branding changes caused by deployments/configuration updates, not only admin uploads.
// NPS is authoritative; these are rare background requests, never storefront queries.
if (config('flowtrack.enabled')) {
    Schedule::command('flowtrack:push-branding')->hourly()->withoutOverlapping();
}
