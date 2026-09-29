<?php

namespace Tests\Feature\Storefront;

use Tests\TestCase;

class AllProductsPromotionBannerIsolationTest extends TestCase
{
    public function test_all_products_top_slot_stays_outside_ajax_results_region(): void
    {
        $view = file_get_contents(resource_path('views/storefront/products/index.blade.php'));

        $this->assertIsString($view);
        $topPosition = strpos($view, 'np-products-page__top-banner');
        $resultsPosition = strpos($view, 'data-product-results');

        $this->assertNotFalse($topPosition);
        $this->assertNotFalse($resultsPosition);
        $this->assertLessThan($resultsPosition, $topPosition);
        $this->assertStringContainsString(':banner="$allProductsTopBanner"', $view);
    }

    public function test_all_products_ajax_partial_contains_only_the_middle_slot(): void
    {
        $partial = file_get_contents(resource_path('views/storefront/products/_results.blade.php'));

        $this->assertIsString($partial);
        $this->assertStringContainsString('$allProductsMiddleBanner', $partial);
        $this->assertStringContainsString('np-promotion-midpoint-banner--cols-', $partial);
        $this->assertStringNotContainsString('$allProductsTopBanner', $partial);
        $this->assertStringNotContainsString('all_products_top', $partial);
    }
}
