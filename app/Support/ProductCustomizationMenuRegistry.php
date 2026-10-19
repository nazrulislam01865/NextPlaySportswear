<?php

namespace App\Support;

final class ProductCustomizationMenuRegistry
{
    /** @return array<string, array{number: string, label: string}> */
    public static function menuOnlyGroups(): array
    {
        return [
            'world_cup' => ['number' => '1.24', 'label' => 'World Cup Customization'],
        ];
    }

    /**
     * Keep the post-Training-Vest sidebar in numeric order while allowing
     * menu-only groups and fully configured reusable groups to coexist.
     *
     * @param  array<string, array{number: string, label: string, types: array}>  $activeGroups
     * @return array<string, array{number: string, label: string, types?: array}>
     */
    public static function afterTrainingVestGroups(array $activeGroups): array
    {
        $groups = self::menuOnlyGroups();

        foreach ($activeGroups as $key => $group) {
            if (version_compare($group['number'], '1.19', '>')) {
                $groups[$key] = $group;
            }
        }

        uasort($groups, static fn (array $left, array $right): int => version_compare($left['number'], $right['number']));

        return $groups;
    }

    /** @return array<string, string> */
    public static function trailingMasterDataNumbers(): array
    {
        return [
            'production_methods' => '1.30',
            'shipping_methods' => '1.31',
            'holiday_calendars' => '1.32',
            'faqs' => '1.33',
            'genders' => '1.34',
            'time_zones' => '1.35',
            'country_calling_codes' => '1.36',
            'bulk_quote_budget_ranges' => '1.37',
            'pantone_colors' => '1.38',
            'design_styles' => '1.39',
        ];
    }
}
