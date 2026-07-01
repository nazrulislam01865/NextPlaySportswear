<?php

use App\Services\Catalog\CategoryProductAssignmentSyncService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('catalog:sync-category-products {--keep-existing : Keep existing category_product rows and only add missing trusted rows}', function () {
    $resetExisting = ! (bool) $this->option('keep-existing');

    if ($resetExisting) {
        $this->warn('Existing category_product rows will be cleaned and rebuilt from trusted product data.');
    }

    $stats = app(CategoryProductAssignmentSyncService::class)->syncAllProductCategoryAssignments($resetExisting);

    $this->info('Category product assignments repaired.');
    $this->line('Total products in products table: '.$stats['total_products_in_table']);
    $this->line('Published/visible products: '.$stats['published_products_in_table']);
    $this->line('Old category_product rows deleted: '.$stats['assignments_deleted']);
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
})->purpose('Clean and rebuild category_product assignments from trusted product category fields and safe category rules');
