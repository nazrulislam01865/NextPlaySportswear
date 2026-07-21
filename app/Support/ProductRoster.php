<?php

namespace App\Support;

class ProductRoster
{
    /**
     * Product profiles that should not show roster/personalized row fields.
     * The misspelled aliases are kept as defensive guards for old/manual data.
     *
     * @var array<int, string>
     */
    public const EXCLUDED_PRODUCT_PROFILES = [
        'bag',
        'headwear',
        'drinkware',
        'drinkwear',
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
