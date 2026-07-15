<?php

namespace Tests\Unit;

use App\Enums\JerseyCustomizationType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class JerseyCustomizationTypeTest extends TestCase
{
    #[DataProvider('types')]
    public function test_each_type_has_a_valid_product_option_group_code(JerseyCustomizationType $type): void
    {
        $code = $type->productCode();

        $this->assertNotSame('', $code);
        $this->assertMatchesRegularExpression('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $code);
    }


    public function test_sweatshirt_and_jacket_have_independent_master_data_groups(): void
    {
        $groups = JerseyCustomizationType::menuGroups();

        $this->assertArrayHasKey('sweatshirt', $groups);
        $this->assertArrayHasKey('jacket', $groups);
        $this->assertSame('1.12', $groups['sweatshirt']['number']);
        $this->assertSame('1.13', $groups['jacket']['number']);
        $this->assertSame('sweatshirt', JerseyCustomizationType::SweatshirtFabric->group());
        $this->assertSame('jacket', JerseyCustomizationType::JacketOuterFabric->group());
        $this->assertNotSame(
            JerseyCustomizationType::SweatshirtColor->value,
            JerseyCustomizationType::JacketColor->value
        );
    }

    public function test_sweatshirt_and_jacket_sizes_use_separate_size_option_contexts(): void
    {
        $this->assertTrue(JerseyCustomizationType::SweatshirtSize->isSizeChartType());
        $this->assertTrue(JerseyCustomizationType::JacketSize->isSizeChartType());
        $this->assertArrayNotHasKey(
            JerseyCustomizationType::SweatshirtSize->value,
            JerseyCustomizationType::masterDataOptions()
        );
        $this->assertArrayNotHasKey(
            JerseyCustomizationType::JacketSize->value,
            JerseyCustomizationType::masterDataOptions()
        );
        $this->assertSame('1.12.9', JerseyCustomizationType::sizeOptionMenuNumberForGroup('sweatshirt'));
        $this->assertSame('1.13.11', JerseyCustomizationType::sizeOptionMenuNumberForGroup('jacket'));
    }

    /** @return array<string, array{JerseyCustomizationType}> */
    public static function types(): array
    {
        return collect(JerseyCustomizationType::cases())
            ->mapWithKeys(static fn (JerseyCustomizationType $type): array => [$type->value => [$type]])
            ->all();
    }
}
