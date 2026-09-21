<?php

namespace App\Support;

final class HomepageImageAspectRatios
{
    public const DEFAULT_TOLERANCE = 0.05;

    /** @return array{ratio:string,width:int,height:int,label:string,tolerance:float} */
    public static function heroSlide(): array
    {
        return self::definition('hero_slide');
    }

    /** @return array{ratio:string,width:int,height:int,label:string,tolerance:float}|null */
    public static function forSectionImage(string $sectionKey): ?array
    {
        return match ($sectionKey) {
            'season_sale' => self::definition('season_sale'),
            default => null,
        };
    }

    /** @return array{ratio:string,width:int,height:int,label:string,tolerance:float}|null */
    public static function forSectionItem(string $sectionKey): ?array
    {
        return match ($sectionKey) {
            'audience' => self::definition('audience'),
            'shop_by_sport' => self::definition('shop_by_sport'),
            'shop_by_category' => self::definition('shop_by_category'),
            'design_process' => self::definition('design_process'),
            default => null,
        };
    }

    /** @param array{width:int,height:int,tolerance?:float} $definition */
    public static function matches(int $width, int $height, array $definition): bool
    {
        if ($width <= 0 || $height <= 0 || ($definition['width'] ?? 0) <= 0 || ($definition['height'] ?? 0) <= 0) {
            return false;
        }

        $actual = $width / $height;
        $target = $definition['width'] / $definition['height'];
        $tolerance = (float) ($definition['tolerance'] ?? self::DEFAULT_TOLERANCE);

        return abs($actual - $target) / $target <= $tolerance;
    }

    /** @param array{ratio:string,tolerance?:float} $definition */
    public static function hint(array $definition): string
    {
        $percent = (int) round(((float) ($definition['tolerance'] ?? self::DEFAULT_TOLERANCE)) * 100);

        return sprintf(
            'Target aspect ratio: %s (±%d%% accepted). Resolution is flexible.',
            $definition['ratio'],
            $percent,
        );
    }

    /** @return array{ratio:string,width:int,height:int,label:string,tolerance:float} */
    private static function definition(string $key): array
    {
        $definitions = [
            'hero_slide' => [
                'ratio' => '8:3',
                'width' => 8,
                'height' => 3,
                'label' => 'hero banner',
                'tolerance' => 0.05,
            ],
            'audience' => [
                'ratio' => '672:427',
                'width' => 672,
                'height' => 427,
                'label' => 'audience tile',
                'tolerance' => 0.05,
            ],
            'shop_by_sport' => [
                'ratio' => '3:1',
                'width' => 3,
                'height' => 1,
                'label' => 'sport banner',
                'tolerance' => 0.05,
            ],
            'shop_by_category' => [
                'ratio' => '26:25',
                'width' => 26,
                'height' => 25,
                'label' => 'category tile',
                'tolerance' => 0.05,
            ],
            'season_sale' => [
                'ratio' => '4:1',
                'width' => 4,
                'height' => 1,
                'label' => 'season sale banner',
                'tolerance' => 0.05,
            ],
            'design_process' => [
                'ratio' => '16:9',
                'width' => 16,
                'height' => 9,
                'label' => 'design-process image',
                'tolerance' => 0.05,
            ],
        ];

        return $definitions[$key];
    }
}
