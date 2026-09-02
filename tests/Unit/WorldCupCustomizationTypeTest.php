<?php

namespace Tests\Unit;

use App\Enums\WorldCupCustomizationType;
use App\Support\WorldCupCustomizationRegistry;
use PHPUnit\Framework\TestCase;

class WorldCupCustomizationTypeTest extends TestCase
{
    public function test_registry_contains_twenty_five_unique_categories(): void
    {
        $categories = WorldCupCustomizationRegistry::menuCategories();

        $this->assertCount(25, $categories);
        $this->assertCount(25, array_unique(array_keys($categories)));
        $this->assertSame('1.24.1', $categories['drawstring']['number']);
        $this->assertSame('1.24.24', $categories['headband']['number']);
        $this->assertSame('1.24.25', $categories['pennant']['number']);
        $this->assertSame('Fan Cap', $categories['fan_cap']['label']);
    }

    public function test_every_category_has_its_own_materials_and_size_options(): void
    {
        foreach (WorldCupCustomizationRegistry::menuCategories() as $categoryKey => $category) {
            $types = WorldCupCustomizationRegistry::typesForCategory($categoryKey);
            $labels = array_map(static fn (WorldCupCustomizationType $type) => $type->label(), $types);

            $this->assertContains('Materials Option', $labels, $category['label']);
            $this->assertContains('Size Option', $labels, $category['label']);
        }
    }

    public function test_drawstring_also_has_sample_charge_option(): void
    {
        $types = WorldCupCustomizationRegistry::typesForCategory('drawstring');
        $labels = array_map(static fn (WorldCupCustomizationType $type) => $type->label(), $types);

        $this->assertCount(3, $types);
        $this->assertContains('Sample Charge Option', $labels);
    }

    public function test_headband_is_isolated_under_world_cup(): void
    {
        $types = WorldCupCustomizationRegistry::typesForCategory('headband');

        $this->assertCount(2, $types);
        foreach ($types as $type) {
            $this->assertSame('headband', $type->categoryKey());
            $this->assertSame('World Cup Customization', $type->groupLabel());
            $this->assertStringStartsWith('1.24.24.', $type->menuNumber());
        }
    }

    public function test_type_values_are_unique_and_fit_database_column(): void
    {
        $values = array_map(static fn (WorldCupCustomizationType $type) => $type->value, WorldCupCustomizationType::cases());

        $this->assertCount(51, $values);
        $this->assertCount(count($values), array_unique($values));

        foreach ($values as $value) {
            $this->assertLessThanOrEqual(80, strlen($value), $value);
        }
    }
}
