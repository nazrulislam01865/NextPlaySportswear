<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AdminCategoryIndexExperienceTest extends TestCase
{
    public function test_category_manager_keeps_existing_routes_and_exposes_secondary_settings_on_demand(): void
    {
        $view = file_get_contents(__DIR__.'/../../resources/views/admin/categories/index.blade.php');

        self::assertNotFalse($view);
        self::assertLessThan(strpos($view, 'id="catalog-page-banner"'), strpos($view, 'id="category-list"'));
        self::assertLessThan(strpos($view, 'id="category-list"'), strpos($view, 'class="category-stats-grid"'));
        self::assertStringContainsString('<details id="category-import"', $view);
        self::assertStringContainsString('<details id="catalog-page-banner"', $view);
        self::assertStringContainsString("route('admin.categories.import')", $view);
        self::assertStringContainsString("route('admin.categories.catalog-banner.update')", $view);
        self::assertStringContainsString("route('admin.categories.bulk')", $view);
        self::assertStringContainsString("route('admin.categories.products.index', \$category)", $view);
        self::assertStringContainsString('data-category-delete-trigger', $view);
    }

    public function test_selection_controls_require_checked_categories_without_changing_form_contract(): void
    {
        $view = file_get_contents(__DIR__.'/../../resources/views/admin/categories/index.blade.php');

        self::assertStringContainsString('data-category-selected-count', $view);
        self::assertStringContainsString('data-category-bulk-submit', $view);
        self::assertStringContainsString('categoryBulkSubmit.disabled = selectedCount === 0', $view);
        self::assertStringContainsString('categoryCheckAll.indeterminate', $view);
        self::assertStringContainsString('name="category_ids[]"', $view);
        self::assertStringContainsString('form="bulk-category-form"', $view);
        self::assertStringContainsString('data-category-delete-form', $view);
    }
}
