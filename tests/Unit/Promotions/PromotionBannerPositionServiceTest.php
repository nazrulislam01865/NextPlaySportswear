<?php

namespace Tests\Unit\Promotions;

use App\Services\Promotions\PromotionBannerPositionService;
use PHPUnit\Framework\TestCase;

class PromotionBannerPositionServiceTest extends TestCase
{
    public function test_even_middle_row_uses_nearest_even_row_with_earlier_tie(): void
    {
        $service = new PromotionBannerPositionService();

        $this->assertSame(2, $service->evenMiddleRow(2));
        $this->assertSame(2, $service->evenMiddleRow(3));
        $this->assertSame(2, $service->evenMiddleRow(5));
        $this->assertSame(4, $service->evenMiddleRow(6));
        $this->assertSame(4, $service->evenMiddleRow(7));
        $this->assertSame(4, $service->evenMiddleRow(9));
        $this->assertSame(6, $service->evenMiddleRow(10));
        $this->assertSame(6, $service->evenMiddleRow(11));
        $this->assertNull($service->evenMiddleRow(1));
    }

    public function test_insertion_index_uses_visual_rows_for_current_breakpoint(): void
    {
        $service = new PromotionBannerPositionService();

        $this->assertSame(8, $service->insertionIndex(17, 4));
        $this->assertNull($service->insertionIndex(4, 5));
    }

    public function test_grouped_insertion_point_counts_rows_across_campaign_sections(): void
    {
        $service = new PromotionBannerPositionService();

        $this->assertSame(
            ['section_index' => 1, 'product_index' => 8, 'row' => 4],
            $service->groupedInsertionPoint([8, 8, 12], 4)
        );
        $this->assertNull($service->groupedInsertionPoint([3], 4));
    }
}
