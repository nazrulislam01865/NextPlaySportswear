<?php

namespace App\Support;

class ProductSizing
{
    /**
     * Product profiles that should not show size-chart / size-quantity options.
     * The misspelled aliases are kept as defensive guards for old/manual data.
     *
     * @var array<int, string>
     */
    public const EXCLUDED_PRODUCT_PROFILES = [
        'bag',
        'headwear',
        'drinkware',
        'lanyard',
        'lyniard',
        'headband',
    ];

    public static function supports(?string $productProfile): bool
    {
        $profile = strtolower(trim((string) $productProfile));

        return ! in_array($profile, self::EXCLUDED_PRODUCT_PROFILES, true);
    }
}
