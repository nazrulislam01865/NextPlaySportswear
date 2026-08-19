<?php

namespace Tests\Unit;

use App\Support\ProductSizing;
use PHPUnit\Framework\TestCase;

class ProductSizingTest extends TestCase
{
    public function test_customizations_with_dedicated_size_option_do_not_expose_generic_size_options_master_data(): void
    {
        $groups = [
            'premium_scarf',
            'bandana',
            'knitted_gloves',
            'fabric_wristband',
            'baseball_belt',
            'armsleeve',
            'silicone_wristband',
            'towel',
        ];

        foreach ($groups as $group) {
            $this->assertTrue(ProductSizing::supports($group), "{$group} should keep normal product sizing compatibility.");
            $this->assertFalse(ProductSizing::supportsMasterDataSizeOptions($group), "{$group} should not expose duplicate generic Size Options master data.");
        }
    }

    public function test_other_size_enabled_customization_groups_keep_generic_size_options_master_data(): void
    {
        foreach (['jersey', 'shorts', 'hoodie', 'polo', 'tshirt'] as $group) {
            $this->assertTrue(ProductSizing::supportsMasterDataSizeOptions($group));
        }
    }
}
