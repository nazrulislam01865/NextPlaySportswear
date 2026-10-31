<?php

namespace Tests\Regression;

use Tests\TestCase;

class NextPlayCategoryFilterStylePreservationTest extends TestCase
{
    public function test_category_filter_typography_keeps_original_storefront_rules(): void
    {
        $original = file_get_contents(resource_path('css/storefront.css'));
        $extension = file_get_contents(resource_path('css/storefront-typography.css'));

        // Avoid page-level !important overrides of the original compact filter UI.
        foreach ([
            '.np-catalog-filter-header h3',
            '.np-catalog-filter-title',
            '.np-catalog-filter-option',
            '.np-catalog-filter-search input',
            '.np-catalog-filter-reset',
            '.np-catalog-money-input input',
            '.np-catalog-sort select',
            '.np-catalog-size-option',
        ] as $selector) {
            $this->assertStringContainsString($selector, $original);
            $this->assertStringNotContainsString($selector, $extension);
        }

        $categoryPanel = file_get_contents(resource_path('views/components/storefront/category/filter-panel.blade.php'));
        $categoryPage = file_get_contents(resource_path('views/storefront/categories/show.blade.php'));
        $catalogPage = file_get_contents(resource_path('views/storefront/products/index.blade.php'));

        $this->assertStringContainsString('class="btn btn-outline btn-sm">Clear</a>', $categoryPanel);
        $this->assertStringNotContainsString('np-category-action w-full sm:w-auto lg:hidden', $categoryPage);
        $this->assertStringNotContainsString('np-category-action np-filter-mobile-open', $catalogPage);

        // Other category typography remains active and the product card is untouched.
        $this->assertStringContainsString('.np-category-section-title', $extension);
        $this->assertStringContainsString('.np-category-hero__title', $extension);
        $this->assertStringContainsString('<x-storefront.product-card', $categoryPage);
    }
}
