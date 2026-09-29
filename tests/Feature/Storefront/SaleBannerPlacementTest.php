<?php

namespace Tests\Feature\Storefront;

use App\Models\SaleBanner;
use App\Support\PromotionBannerPlacement;
use Tests\TestCase;

class SaleBannerPlacementTest extends TestCase
{
    public function test_sale_controller_resolves_page_level_top_and_middle_slots_once(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Storefront/ProductController.php'));
        $start = strpos($source, 'public function sale(ProductFilterRequest $request): View');
        $end = strpos($source, 'private function insertionIndices', $start ?: 0);

        $this->assertIsString($source);
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $saleBlock = substr($source, $start, $end - $start);

        $this->assertStringContainsString('PromotionBannerPlacement::SALE_TOP', $saleBlock);
        $this->assertStringContainsString('PromotionBannerPlacement::SALE_MIDDLE', $saleBlock);
        $this->assertStringContainsString('$saleTopBanner = $this->saleBanners->resolvePageSlot(', $saleBlock);
        $this->assertStringContainsString('$saleMiddleBanner = $this->saleBanners->resolvePageSlot(', $saleBlock);
        $this->assertStringContainsString('$saleMiddleInsertionIndices = $this->insertionIndices($saleProducts->count());', $saleBlock);
        $this->assertStringNotContainsString('resolveForCampaignPlacement(', $saleBlock);
        $this->assertStringNotContainsString("'top_banner'", $saleBlock);
        $this->assertStringNotContainsString("'after_row_banner'", $saleBlock);
    }

    public function test_top_banner_is_before_summary_and_middle_banner_uses_breakpoint_midpoint_map_without_intervals(): void
    {
        $sale = file_get_contents(resource_path('views/storefront/products/sale.blade.php'));
        $results = file_get_contents(resource_path('views/storefront/products/_sale-results.blade.php'));

        $this->assertIsString($sale);
        $this->assertIsString($results);

        $topPosition = strpos($sale, 'np-sale-page__top-banner');
        $summaryPosition = strpos($sale, 'np-catalog-active-bar');
        $this->assertNotFalse($topPosition);
        $this->assertNotFalse($summaryPosition);
        $this->assertLessThan($summaryPosition, $topPosition);

        $this->assertStringContainsString(':banner="$saleTopBanner"', $sale);
        $this->assertStringContainsString(':banner="$saleMiddleBanner"', $results);
        $this->assertStringContainsString('np-promotion-midpoint-banner--cols-', $results);
        foreach ([2, 4, 6, 8, 10] as $interval) {
            $this->assertStringNotContainsString('% '.$interval, $results);
        }
    }

    public function test_category_top_uses_category_aware_page_slot_resolution(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Storefront/CategoryController.php'));

        $this->assertIsString($source);
        $this->assertStringContainsString('PromotionBannerPlacement::CATEGORY_TOP', $source);
        $this->assertStringContainsString('->bannerCandidates(', $source);
        $this->assertStringContainsString('->resolvePageSlot(', $source);
        $this->assertStringNotContainsString('->firstForPlacement(', $source);
    }

    public function test_only_shared_supported_storefront_placements_exist(): void
    {
        $this->assertSame(PromotionBannerPlacement::ALL, SaleBanner::PLACEMENTS);
        $this->assertSame([
            'sale_top',
            'sale_middle',
            'all_products_top',
            'all_products_middle',
            'category_top',
        ], SaleBanner::PLACEMENTS);
    }
}
