<?php

namespace Tests\Unit;

use App\Enums\JerseyCustomizationType;
use App\Support\ProductCustomizationMenuRegistry;
use PHPUnit\Framework\TestCase;

class ProductCustomizationMenuRegistryTest extends TestCase
{
    public function test_menu_only_customization_groups_keep_only_categories_without_options(): void
    {
        $groups = ProductCustomizationMenuRegistry::menuOnlyGroups();

        $this->assertSame(['world_cup'], array_keys($groups));
        $this->assertSame(['1.24'], array_column($groups, 'number'));

        foreach (['towel', 'silicone_wristband', 'armsleeve', 'baseball_belt', 'fabric_wristband', 'knitted_gloves', 'bandana', 'premium_scarf', 'wristbands'] as $configuredGroup) {
            $this->assertArrayNotHasKey($configuredGroup, $groups);
        }
    }

    public function test_configured_and_menu_only_groups_are_combined_after_training_vest_in_numeric_order(): void
    {
        $groups = ProductCustomizationMenuRegistry::afterTrainingVestGroups(JerseyCustomizationType::menuGroups());

        $this->assertSame([
            'towel',
            'silicone_wristband',
            'armsleeve',
            'baseball_belt',
            'world_cup',
            'fabric_wristband',
            'knitted_gloves',
            'bandana',
            'premium_scarf',
            'wristbands',
        ], array_keys($groups));

        $this->assertSame([
            '1.20', '1.21', '1.22', '1.23', '1.24', '1.25', '1.26', '1.27', '1.28', '1.29',
        ], array_column($groups, 'number'));

        foreach (['towel', 'silicone_wristband', 'armsleeve', 'baseball_belt', 'fabric_wristband', 'knitted_gloves', 'bandana', 'premium_scarf', 'wristbands'] as $configuredGroup) {
            $this->assertNotEmpty($groups[$configuredGroup]['types']);
        }
        $this->assertArrayNotHasKey('types', $groups['world_cup']);
    }

    public function test_following_master_data_numbers_stay_after_the_reserved_customization_menus(): void
    {
        $this->assertSame([
            'production_methods' => '1.30',
            'shipping_methods' => '1.31',
            'faqs' => '1.32',
        ], ProductCustomizationMenuRegistry::trailingMasterDataNumbers());
    }
}
