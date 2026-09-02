<?php

namespace App\Support;

use App\Enums\WorldCupCustomizationType;
use LogicException;

final class WorldCupCustomizationRegistry
{
    /** @var array<string, array{number:string,label:string,options:array<string,array<string,mixed>>}> */
    private const CATEGORIES = [
        'drawstring' => [
            'number' => '1.24.1',
            'label' => 'Drawstring',
            'options' => [
                'world_cup_drawstring_materials_option' => [
                    'label' => 'Materials Option',
                    'placeholder' => 'Example: Polyester',
                    'help_text' => 'Add material choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'world_cup_drawstring_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: Standard',
                    'help_text' => 'Add size or dimension choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'size',
                ],
                'world_cup_drawstring_sample_charge_option' => [
                    'label' => 'Sample Charge Option',
                    'placeholder' => 'Example: Sample Setup Charge',
                    'help_text' => 'Add Drawstring sample-charge choices that describe when a sample or setup charge can apply. Configure the actual amount per product with the existing Additional charge controls.',
                    'visual' => 'charge',
                ],
            ],
        ],
        'fan_cap' => [
            'number' => '1.24.2',
            'label' => 'Fan Cap',
            'options' => [
                'world_cup_fan_cap_materials_option' => [
                    'label' => 'Materials Option',
                    'placeholder' => 'Example: Polyester',
                    'help_text' => 'Add material choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'world_cup_fan_cap_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: Standard',
                    'help_text' => 'Add size or dimension choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'size',
                ],
            ],
        ],
        'car_flag_with_elastic' => [
            'number' => '1.24.3',
            'label' => 'Car Flag with Elastic',
            'options' => [
                'world_cup_car_flag_with_elastic_materials_option' => [
                    'label' => 'Materials Option',
                    'placeholder' => 'Example: Polyester',
                    'help_text' => 'Add material choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'world_cup_car_flag_with_elastic_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: Standard',
                    'help_text' => 'Add size or dimension choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'size',
                ],
            ],
        ],
        'rearview_mirror_cover' => [
            'number' => '1.24.4',
            'label' => 'Rearview Mirror Cover',
            'options' => [
                'world_cup_rearview_mirror_cover_materials_option' => [
                    'label' => 'Materials Option',
                    'placeholder' => 'Example: Polyester',
                    'help_text' => 'Add material choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'world_cup_rearview_mirror_cover_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: Standard',
                    'help_text' => 'Add size or dimension choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'size',
                ],
            ],
        ],
        'jacquard_scarf' => [
            'number' => '1.24.5',
            'label' => 'Jacquard Scarf',
            'options' => [
                'world_cup_jacquard_scarf_materials_option' => [
                    'label' => 'Materials Option',
                    'placeholder' => 'Example: Polyester',
                    'help_text' => 'Add material choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'world_cup_jacquard_scarf_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: Standard',
                    'help_text' => 'Add size or dimension choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'size',
                ],
            ],
        ],
        'dye_sub_scarf' => [
            'number' => '1.24.6',
            'label' => 'Dye Sub Scarf',
            'options' => [
                'world_cup_dye_sub_scarf_materials_option' => [
                    'label' => 'Materials Option',
                    'placeholder' => 'Example: Polyester',
                    'help_text' => 'Add material choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'world_cup_dye_sub_scarf_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: Standard',
                    'help_text' => 'Add size or dimension choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'size',
                ],
            ],
        ],
        'string_flag' => [
            'number' => '1.24.7',
            'label' => 'String Flag',
            'options' => [
                'world_cup_string_flag_materials_option' => [
                    'label' => 'Materials Option',
                    'placeholder' => 'Example: Polyester',
                    'help_text' => 'Add material choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'world_cup_string_flag_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: Standard',
                    'help_text' => 'Add size or dimension choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'size',
                ],
            ],
        ],
        'hand_flag' => [
            'number' => '1.24.8',
            'label' => 'Hand Flag',
            'options' => [
                'world_cup_hand_flag_materials_option' => [
                    'label' => 'Materials Option',
                    'placeholder' => 'Example: Polyester',
                    'help_text' => 'Add material choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'world_cup_hand_flag_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: Standard',
                    'help_text' => 'Add size or dimension choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'size',
                ],
            ],
        ],
        'hooded_cape_flag' => [
            'number' => '1.24.9',
            'label' => 'Hooded Cape Flag',
            'options' => [
                'world_cup_hooded_cape_flag_materials_option' => [
                    'label' => 'Materials Option',
                    'placeholder' => 'Example: Polyester',
                    'help_text' => 'Add material choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'world_cup_hooded_cape_flag_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: Standard',
                    'help_text' => 'Add size or dimension choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'size',
                ],
            ],
        ],
        'body_cape_flag' => [
            'number' => '1.24.10',
            'label' => 'Body Cape Flag',
            'options' => [
                'world_cup_body_cape_flag_materials_option' => [
                    'label' => 'Materials Option',
                    'placeholder' => 'Example: Polyester',
                    'help_text' => 'Add material choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'world_cup_body_cape_flag_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: Standard',
                    'help_text' => 'Add size or dimension choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'size',
                ],
            ],
        ],
        'fan_shirt' => [
            'number' => '1.24.11',
            'label' => 'Fan Shirt',
            'options' => [
                'world_cup_fan_shirt_materials_option' => [
                    'label' => 'Materials Option',
                    'placeholder' => 'Example: Polyester',
                    'help_text' => 'Add material choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'world_cup_fan_shirt_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: Standard',
                    'help_text' => 'Add size or dimension choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'size',
                ],
            ],
        ],
        'inflatable_cheer_sticks' => [
            'number' => '1.24.12',
            'label' => 'Inflatable Cheer Sticks',
            'options' => [
                'world_cup_inflatable_cheer_sticks_materials_option' => [
                    'label' => 'Materials Option',
                    'placeholder' => 'Example: Polyester',
                    'help_text' => 'Add material choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'world_cup_inflatable_cheer_sticks_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: Standard',
                    'help_text' => 'Add size or dimension choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'size',
                ],
            ],
        ],
        'pvc_key_ring' => [
            'number' => '1.24.13',
            'label' => 'PVC Key Ring',
            'options' => [
                'world_cup_pvc_key_ring_materials_option' => [
                    'label' => 'Materials Option',
                    'placeholder' => 'Example: Polyester',
                    'help_text' => 'Add material choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'world_cup_pvc_key_ring_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: Standard',
                    'help_text' => 'Add size or dimension choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'size',
                ],
            ],
        ],
        'metal_key_ring' => [
            'number' => '1.24.14',
            'label' => 'Metal Key Ring',
            'options' => [
                'world_cup_metal_key_ring_materials_option' => [
                    'label' => 'Materials Option',
                    'placeholder' => 'Example: Polyester',
                    'help_text' => 'Add material choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'world_cup_metal_key_ring_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: Standard',
                    'help_text' => 'Add size or dimension choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'size',
                ],
            ],
        ],
        'fan_face_paint' => [
            'number' => '1.24.15',
            'label' => 'Fan Face Paint',
            'options' => [
                'world_cup_fan_face_paint_materials_option' => [
                    'label' => 'Materials Option',
                    'placeholder' => 'Example: Polyester',
                    'help_text' => 'Add material choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'world_cup_fan_face_paint_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: Standard',
                    'help_text' => 'Add size or dimension choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'size',
                ],
            ],
        ],
        'fan_hat' => [
            'number' => '1.24.16',
            'label' => 'Fan Hat',
            'options' => [
                'world_cup_fan_hat_materials_option' => [
                    'label' => 'Materials Option',
                    'placeholder' => 'Example: Polyester',
                    'help_text' => 'Add material choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'world_cup_fan_hat_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: Standard',
                    'help_text' => 'Add size or dimension choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'size',
                ],
            ],
        ],
        'fan_glass' => [
            'number' => '1.24.17',
            'label' => 'Fan Glass',
            'options' => [
                'world_cup_fan_glass_materials_option' => [
                    'label' => 'Materials Option',
                    'placeholder' => 'Example: Polyester',
                    'help_text' => 'Add material choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'world_cup_fan_glass_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: Standard',
                    'help_text' => 'Add size or dimension choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'size',
                ],
            ],
        ],
        'trophy' => [
            'number' => '1.24.18',
            'label' => 'Trophy',
            'options' => [
                'world_cup_trophy_materials_option' => [
                    'label' => 'Materials Option',
                    'placeholder' => 'Example: Polyester',
                    'help_text' => 'Add material choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'world_cup_trophy_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: Standard',
                    'help_text' => 'Add size or dimension choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'size',
                ],
            ],
        ],
        'armband' => [
            'number' => '1.24.19',
            'label' => 'Armband',
            'options' => [
                'world_cup_armband_materials_option' => [
                    'label' => 'Materials Option',
                    'placeholder' => 'Example: Polyester',
                    'help_text' => 'Add material choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'world_cup_armband_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: Standard',
                    'help_text' => 'Add size or dimension choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'size',
                ],
            ],
        ],
        'fan_wig' => [
            'number' => '1.24.20',
            'label' => 'Fan Wig',
            'options' => [
                'world_cup_fan_wig_materials_option' => [
                    'label' => 'Materials Option',
                    'placeholder' => 'Example: Polyester',
                    'help_text' => 'Add material choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'world_cup_fan_wig_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: Standard',
                    'help_text' => 'Add size or dimension choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'size',
                ],
            ],
        ],
        'fan_towel' => [
            'number' => '1.24.21',
            'label' => 'Fan Towel',
            'options' => [
                'world_cup_fan_towel_materials_option' => [
                    'label' => 'Materials Option',
                    'placeholder' => 'Example: Polyester',
                    'help_text' => 'Add material choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'world_cup_fan_towel_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: Standard',
                    'help_text' => 'Add size or dimension choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'size',
                ],
            ],
        ],
        'hand_clapper' => [
            'number' => '1.24.22',
            'label' => 'Hand Clapper',
            'options' => [
                'world_cup_hand_clapper_materials_option' => [
                    'label' => 'Materials Option',
                    'placeholder' => 'Example: Polyester',
                    'help_text' => 'Add material choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'world_cup_hand_clapper_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: Standard',
                    'help_text' => 'Add size or dimension choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'size',
                ],
            ],
        ],
        'combo' => [
            'number' => '1.24.23',
            'label' => 'Combo',
            'options' => [
                'world_cup_combo_materials_option' => [
                    'label' => 'Materials Option',
                    'placeholder' => 'Example: Polyester',
                    'help_text' => 'Add material choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'world_cup_combo_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: Standard',
                    'help_text' => 'Add size or dimension choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'size',
                ],
            ],
        ],
        'headband' => [
            'number' => '1.24.24',
            'label' => 'Headband',
            'options' => [
                'world_cup_headband_materials_option' => [
                    'label' => 'Materials Option',
                    'placeholder' => 'Example: Polyester',
                    'help_text' => 'Add material choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'world_cup_headband_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: Standard',
                    'help_text' => 'Add size or dimension choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'size',
                ],
            ],
        ],
        'pennant' => [
            'number' => '1.24.25',
            'label' => 'Pennant',
            'options' => [
                'world_cup_pennant_materials_option' => [
                    'label' => 'Materials Option',
                    'placeholder' => 'Example: Polyester',
                    'help_text' => 'Add material choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'world_cup_pennant_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: Standard',
                    'help_text' => 'Add size or dimension choices for this World Cup product. Values stay isolated inside this product category.',
                    'visual' => 'size',
                ],
            ],
        ],
    ];

    /** @var array<string, array{image_title:string,image_description:string,image_cta:string}> */
    private const VISUALS = [
        'material' => [
            'image_title' => 'Optional material image',
            'image_description' => 'Optional. Add a close-up when material or finish needs a visual reference.',
            'image_cta' => 'Choose material image',
        ],
        'size' => [
            'image_title' => 'Optional size reference image',
            'image_description' => 'Optional. Add a reference when dimensions need a visual explanation.',
            'image_cta' => 'Choose size image',
        ],
        'charge' => [
            'image_title' => 'Optional sample reference image',
            'image_description' => 'Optional. Add a sample reference image only when it helps explain the charge.',
            'image_cta' => 'Choose sample image',
        ],
    ];

    /** @return array<string, array{number:string,label:string,types:array<int,WorldCupCustomizationType>}> */
    public static function menuCategories(): array
    {
        return collect(self::CATEGORIES)->map(fn (array $definition, string $category): array => [
            'number' => $definition['number'],
            'label' => $definition['label'],
            'types' => self::typesForCategory($category),
        ])->all();
    }

    /** @return array<int, WorldCupCustomizationType> */
    public static function typesForCategory(string $category): array
    {
        return array_map(
            static fn (string $value): WorldCupCustomizationType => WorldCupCustomizationType::from($value),
            array_keys(self::CATEGORIES[$category]['options'] ?? [])
        );
    }

    /** @return array<string,string> */
    public static function configurationOptions(): array
    {
        return WorldCupCustomizationType::options();
    }

    public static function categoryFor(WorldCupCustomizationType $type): string
    {
        foreach (self::CATEGORIES as $category => $definition) {
            if (isset($definition['options'][$type->value])) {
                return $category;
            }
        }

        throw new LogicException("Missing World Cup category metadata for [{$type->value}].");
    }

    public static function categoryMetadata(string $category, string $key): string
    {
        $value = self::CATEGORIES[$category][$key] ?? null;
        if (! is_string($value) || $value === '') {
            throw new LogicException("Missing {$key} metadata for World Cup category [{$category}].");
        }

        return $value;
    }

    public static function optionMetadata(WorldCupCustomizationType $type, string $key): string
    {
        $definition = self::optionDefinition($type);
        $value = $definition[$key] ?? self::VISUALS[$definition['visual'] ?? ''][$key] ?? null;
        if (! is_string($value) || $value === '') {
            throw new LogicException("Missing {$key} metadata for World Cup customization type [{$type->value}].");
        }

        return $value;
    }

    public static function usesDescription(WorldCupCustomizationType $type): bool
    {
        return (bool) (self::optionDefinition($type)['uses_description'] ?? false);
    }

    /** @return array<string,mixed> */
    private static function optionDefinition(WorldCupCustomizationType $type): array
    {
        $category = self::categoryFor($type);
        $definition = self::CATEGORIES[$category]['options'][$type->value] ?? null;
        if (! is_array($definition)) {
            throw new LogicException("Missing World Cup option metadata for [{$type->value}].");
        }

        return $definition;
    }
}
