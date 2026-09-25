<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Str;

final class ProductTypeOptions
{
    /** @return array<int, string> */
    public static function all(): array
    {
        return Product::query()
            ->whereNotNull('product_type')
            ->where('product_type', '!=', '')
            ->distinct()
            ->orderBy('product_type')
            ->pluck('product_type')
            ->map(fn ($value): string => trim((string) $value))
            ->filter()
            ->unique(fn (string $value): string => Str::lower($value))
            ->values()
            ->all();
    }
}
