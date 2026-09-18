<?php

namespace Tests\Unit;

use App\Support\HomepageSectionRegistry;
use PHPUnit\Framework\TestCase;

class HomepageSectionOrderTest extends TestCase
{
    public function test_registry_contains_exactly_the_approved_vue_homepage_sections_in_order(): void
    {
        $this->assertSame([
            'hero',
            'audience',
            'shop_by_sport',
            'new_arrivals',
            'shop_by_category',
            'best_choices',
            'season_sale',
            'make_it_yours',
            'design_process',
        ], array_column(HomepageSectionRegistry::orderedDefinitions(), 'key'));
    }

    public function test_legacy_homepage_sections_are_retired(): void
    {
        foreach ([
            'slider', 'categories', 'buyer_paths', 'process', 'featured_products',
            'latest_products', 'best_selling_products', 'best_selling_gear',
            'why_choose', 'testimonials', 'faq', 'customization_options', 'support',
            'final_cta', 'popular_categories', 'use_cases', 'design_jersey', 'bulk_order',
        ] as $key) {
            $this->assertTrue(HomepageSectionRegistry::isRetired($key), $key.' must be retired');
        }
    }
}
