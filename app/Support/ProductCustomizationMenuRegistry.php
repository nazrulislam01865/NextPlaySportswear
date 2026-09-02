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

    /** @return array{production_methods: string, shipping_methods: string, faqs: string} */
    public static function trailingMasterDataNumbers(): array
    {
        return [
            'production_methods' => '1.30',
            'shipping_methods' => '1.31',
            'faqs' => '1.32',
        ];
    }
}
