<?php

namespace Tests\Unit;

use App\Support\ProductionTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ProductionTimeTest extends TestCase
{
    #[DataProvider('validValues')]
    public function test_it_parses_single_values_and_ranges(string $value, int $minimum, int $maximum, string $display): void
    {
        self::assertSame([
            'minimum_days' => $minimum,
            'maximum_days' => $maximum,
            'display' => $display,
        ], ProductionTime::parse($value));
    }

    public static function validValues(): array
    {
        return [
            ['7', 7, 7, '7 days'],
            ['1 day', 1, 1, '1 day'],
            ['5-15 days', 5, 15, '5-15 days'],
            ['5 to 15 working days', 5, 15, '5-15 days'],
        ];
    }

    public function test_it_rejects_invalid_or_reversed_ranges(): void
    {
        self::assertNull(ProductionTime::parse(''));
        self::assertNull(ProductionTime::parse('later'));
        self::assertNull(ProductionTime::parse('15-5 days'));
    }
}
