<?php

namespace Tests\Unit;

use App\Support\HomepageSectionRegistry;
use PHPUnit\Framework\TestCase;

class HomepageSectionOrderTest extends TestCase
{
    public function test_shop_by_sport_is_immediately_after_the_homepage_slider(): void
    {
        $keys = array_column(HomepageSectionRegistry::orderedDefinitions(), 'key');

        $sliderPosition = array_search('slider', $keys, true);
        $shopBySportPosition = array_search('shop_by_sport', $keys, true);

        $this->assertIsInt($sliderPosition);
        $this->assertIsInt($shopBySportPosition);
        $this->assertSame($sliderPosition + 1, $shopBySportPosition);
    }

    public function test_removed_homepage_sections_are_retired_and_not_manageable(): void
    {
        $keys = array_column(HomepageSectionRegistry::orderedDefinitions(), 'key');

        $this->assertNotContains('popular_categories', $keys);
        $this->assertNotContains('use_cases', $keys);
        $this->assertTrue(HomepageSectionRegistry::isRetired('popular_categories'));
        $this->assertTrue(HomepageSectionRegistry::isRetired('use_cases'));
    }

}
