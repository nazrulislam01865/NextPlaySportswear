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

    /** @return array<string, array{JerseyCustomizationType}> */
    public static function types(): array
    {
        return collect(JerseyCustomizationType::cases())
            ->mapWithKeys(static fn (JerseyCustomizationType $type): array => [$type->value => [$type]])
            ->all();
    }
}
