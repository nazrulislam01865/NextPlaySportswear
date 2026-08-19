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

    /**
     * Customization groups that manage size as a normal customization option.
     * They should not also expose the generic Master Data -> Size Options flow.
     *
     * @var array<int, string>
     */
    public const DEDICATED_CUSTOMIZATION_SIZE_PROFILES = [
        'towel',
        'silicone_wristband',
        'armsleeve',
        'baseball_belt',
        'fabric_wristband',
        'knitted_gloves',
        'bandana',
        'premium_scarf',
    ];

    public static function supports(?string $productProfile): bool
    {
        $profile = strtolower(trim((string) $productProfile));

        return ! in_array($profile, self::EXCLUDED_PRODUCT_PROFILES, true);
    }

    public static function supportsMasterDataSizeOptions(?string $productProfile): bool
    {
        $profile = strtolower(trim((string) $productProfile));

        return self::supports($profile)
            && ! in_array($profile, self::DEDICATED_CUSTOMIZATION_SIZE_PROFILES, true);
    }
}
