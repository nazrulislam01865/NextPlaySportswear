<?php

namespace App\Support;

use App\Enums\JerseyCustomizationType;
use LogicException;

final class ProductCustomizationOptionRegistry
{
    /** @var array<string, array{number: string, label: string, menu_offset?: int, options: array<string, array<string, mixed>>}> */
    private const GROUPS = [
        'training_vest' => [
            'number' => '1.19',
            'label' => 'Training Vest Customization',
            'options' => [
                // These four values mirror the legacy Training Vest master-data system.
                // They stay hidden from the generic Master Data menu but are available
                // in Add Product so they use the shared surcharge component.
                'training_vest_color_option' => [
                    'label' => 'Color',
                    'placeholder' => 'Example: Safety Orange',
                    'help_text' => 'Mirrors Training Vest Color master data into the shared product customization system.',
                    'visual' => 'color',
                    'uses_color' => true,
                    'hidden_from_menu' => true,
                ],
                'training_vest_fabric_option' => [
                    'label' => 'Fabric',
                    'placeholder' => 'Example: Breathable Mesh Polyester',
                    'help_text' => 'Mirrors Training Vest Fabric master data into the shared product customization system.',
                    'visual' => 'material',
                    'uses_description' => true,
                    'hidden_from_menu' => true,
                ],
                'training_vest_size_option' => [
                    'label' => 'Size',
                    'placeholder' => 'Example: Adult Medium',
                    'help_text' => 'Mirrors Training Vest size values into the shared product customization system.',
                    'visual' => 'size',
                    'hidden_from_menu' => true,
                ],
                'training_vest_vest_type_option' => [
                    'label' => 'Vest Type',
                    'placeholder' => 'Example: Scrimmage Training Vest',
                    'help_text' => 'Mirrors Training Vest type master data into the shared product customization system.',
                    'visual' => 'customization',
                    'hidden_from_menu' => true,
                ],
                'training_vest_imprint_option' => [
                    'label' => 'Imprint Option',
                    'placeholder' => 'Example: Dye Sublimation',
                    'help_text' => 'Add training vest imprint choices through the shared product customization system.',
                    'visual' => 'imprint',
                ],
                'training_vest_logo_option' => [
                    'label' => 'Logo Option',
                    'placeholder' => 'Example: Team Logo',
                    'help_text' => 'Add training vest logo choices through the shared product customization system.',
                    'visual' => 'imprint',
                ],
            ],
        ],
        'towel' => [
            'number' => '1.20',
            'label' => 'Towel Customization',
            'options' => [
                'towel_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: 16 x 24 in',
                    'help_text' => 'Add towel size choices used during product configuration.',
                    'visual' => 'size',
                ],
                'towel_material_option' => [
                    'label' => 'Material Option',
                    'placeholder' => 'Example: Microfiber',
                    'help_text' => 'Add towel material choices with a short description when composition or finish needs explanation.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'towel_front_fabric_option' => [
                    'label' => 'Front Fabric Option',
                    'placeholder' => 'Example: Soft Velour',
                    'help_text' => 'Add front-side towel fabric choices separately from the back fabric.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'towel_back_fabric_option' => [
                    'label' => 'Back Fabric Option',
                    'placeholder' => 'Example: Terry Cotton',
                    'help_text' => 'Add back-side towel fabric choices separately from the front fabric.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'towel_imprint_size_option' => [
                    'label' => 'Imprint Size Option',
                    'placeholder' => 'Example: 8 x 10 in',
                    'help_text' => 'Add towel imprint-size choices used for artwork sizing.',
                    'visual' => 'size',
                ],
                'towel_available_backing_color_option' => [
                    'label' => 'Available Backing Color Option',
                    'placeholder' => 'Example: White',
                    'help_text' => 'Add available towel backing colors using the shared exact-color controls.',
                    'visual' => 'color',
                    'uses_color' => true,
                ],
            ],
        ],
        'silicone_wristband' => [
            'number' => '1.21',
            'label' => 'Silicone Wristband Customization',
            'options' => [
                'silicone_wristband_product_size_option' => [
                    'label' => 'Product Size Option',
                    'placeholder' => 'Example: Adult Medium',
                    'help_text' => 'Add silicone wristband product-size choices such as youth, adult, or custom circumference.',
                    'visual' => 'size',
                ],
                'silicone_wristband_material_option' => [
                    'label' => 'Material Option',
                    'placeholder' => 'Example: 100% Silicone',
                    'help_text' => 'Add silicone wristband material choices with a short description when composition or finish needs explanation.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'silicone_wristband_imprint_method_option' => [
                    'label' => 'Imprint Method Option',
                    'placeholder' => 'Example: Debossed',
                    'help_text' => 'Add silicone wristband imprint methods such as debossed, embossed, printed, or color filled.',
                    'visual' => 'imprint',
                ],
                'silicone_wristband_customized_options' => [
                    'label' => 'Customized Options',
                    'placeholder' => 'Example: Segmented Color',
                    'help_text' => 'Add reusable silicone wristband customization choices that do not belong to size, material, or imprint method.',
                    'visual' => 'customization',
                ],
            ],
        ],
        'armsleeve' => [
            'number' => '1.22',
            'label' => 'Armsleeve Customization',
            'options' => [
                'armsleeve_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: Adult Large',
                    'help_text' => 'Add armsleeve size choices used during product configuration.',
                    'visual' => 'size',
                ],
                'armsleeve_fabric_option' => [
                    'label' => 'Fabric Option',
                    'placeholder' => 'Example: Compression Polyester',
                    'help_text' => 'Add armsleeve fabric choices with a short construction or performance description.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'armsleeve_imprint_method_option' => [
                    'label' => 'Imprint Method Option',
                    'placeholder' => 'Example: Dye Sublimation',
                    'help_text' => 'Add armsleeve imprint methods used during product configuration.',
                    'visual' => 'imprint',
                ],
            ],
        ],
        'baseball_belt' => [
            'number' => '1.23',
            'label' => 'Baseball Belt Customization',
            'options' => [
                'baseball_belt_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: Adult Large',
                    'help_text' => 'Add baseball belt size choices used during product configuration.',
                    'visual' => 'size',
                ],
                'baseball_belt_material_option' => [
                    'label' => 'Material Option',
                    'placeholder' => 'Example: Stretch Polyester',
                    'help_text' => 'Add baseball belt material choices with short details about construction, stretch, or finish.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'baseball_belt_imprint_option' => [
                    'label' => 'Imprint Option',
                    'placeholder' => 'Example: Printed Team Name',
                    'help_text' => 'Add baseball belt imprint choices used during product configuration.',
                    'visual' => 'imprint',
                ],
                'baseball_belt_imprint_area_option' => [
                    'label' => 'Imprint Area Option',
                    'placeholder' => 'Example: Belt End',
                    'help_text' => 'Add baseball belt imprint-area choices so artwork placement stays separate from the imprint method itself.',
                    'visual' => 'area',
                ],
                'baseball_belt_imprint_size_option' => [
                    'label' => 'Imprint Size Option',
                    'placeholder' => 'Example: 3 in × 1 in',
                    'help_text' => 'Add baseball belt imprint-size choices used for artwork sizing during product configuration.',
                    'visual' => 'size',
                ],
                'baseball_belt_color_option' => [
                    'label' => 'Color Option',
                    'placeholder' => 'Example: Royal Blue',
                    'help_text' => 'Add baseball belt color choices with the exact display color. Upload a swatch only when a visual reference is useful.',
                    'visual' => 'color',
                    'uses_color' => true,
                ],
            ],
        ],
        'fabric_wristband' => [
            'number' => '1.25',
            'label' => 'Fabric Wristband Customization',
            'options' => [
                'fabric_wristband_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: 350 x 15 mm',
                    'help_text' => 'Add fabric wristband size choices used during product configuration.',
                    'visual' => 'size',
                ],
                'fabric_wristband_material_option' => [
                    'label' => 'Material Option',
                    'placeholder' => 'Example: Woven Polyester',
                    'help_text' => 'Add fabric wristband material choices with a short description when construction needs explanation.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'fabric_wristband_standard_attachment_option' => [
                    'label' => 'Standard Attachment Option',
                    'placeholder' => 'Example: Plastic Slider',
                    'help_text' => 'Add standard fabric wristband attachment choices.',
                    'visual' => 'customization',
                ],
                'fabric_wristband_imprint_method_option' => [
                    'label' => 'Imprint Method Option',
                    'placeholder' => 'Example: Woven',
                    'help_text' => 'Add fabric wristband imprint methods used during product configuration.',
                    'visual' => 'imprint',
                ],
                'fabric_wristband_locking_closures_option' => [
                    'label' => 'Wristband Locking Closures Option',
                    'placeholder' => 'Example: One-Way Locking Slider',
                    'help_text' => 'Add reusable locking-closure choices for fabric wristbands.',
                    'visual' => 'customization',
                ],
            ],
        ],
        'knitted_gloves' => [
            'number' => '1.26',
            'label' => 'Knitted Gloves Customization',
            'options' => [
                'knitted_gloves_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: Adult Large',
                    'help_text' => 'Add knitted glove size choices used during product configuration.',
                    'visual' => 'size',
                ],
                'knitted_gloves_logo_option' => [
                    'label' => 'Logo Option',
                    'placeholder' => 'Example: Embroidered Team Logo',
                    'help_text' => 'Add knitted glove logo choices used during product configuration.',
                    'visual' => 'imprint',
                ],
                'knitted_gloves_material_option' => [
                    'label' => 'Material Option',
                    'placeholder' => 'Example: Acrylic Knit',
                    'help_text' => 'Add knitted glove material choices with a short description when composition or finish needs explanation.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'knitted_gloves_color_option' => [
                    'label' => 'Color Option',
                    'placeholder' => 'Example: Black',
                    'help_text' => 'Add knitted glove color choices using the shared exact-color controls.',
                    'visual' => 'color',
                    'uses_color' => true,
                ],
                'knitted_gloves_touch_screen_function_option' => [
                    'label' => 'Touch Screen Function Option',
                    'placeholder' => 'Example: Thumb and Index Finger',
                    'help_text' => 'Add available touch-screen functionality choices for knitted gloves.',
                    'visual' => 'customization',
                ],
                'knitted_gloves_inner_lining_option' => [
                    'label' => 'Inner Lining Option',
                    'placeholder' => 'Example: Brushed Fleece',
                    'help_text' => 'Add knitted glove inner-lining choices with a short description when construction needs explanation.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'knitted_gloves_cuff_type_option' => [
                    'label' => 'Cuff Type Option',
                    'placeholder' => 'Example: Ribbed Fold-Over Cuff',
                    'help_text' => 'Add knitted glove cuff-type choices used during product configuration.',
                    'visual' => 'customization',
                ],
                'knitted_gloves_fabric_feature_option' => [
                    'label' => 'Fabric Feature Option',
                    'placeholder' => 'Example: Thermal Insulation',
                    'help_text' => 'Add knitted glove fabric-feature choices with a short description for performance or construction details.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
            ],
        ],
        'bandana' => [
            'number' => '1.27',
            'label' => 'Bandana Customization',
            'options' => [
                'bandana_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: 22 x 22 in',
                    'help_text' => 'Add bandana size choices used during product configuration.',
                    'visual' => 'size',
                ],
                'bandana_fabric_option' => [
                    'label' => 'Fabric Option',
                    'placeholder' => 'Example: Lightweight Polyester',
                    'help_text' => 'Add bandana fabric choices with a short description when construction or finish needs explanation.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'bandana_mask_layers_option' => [
                    'label' => 'Mask Layers Option',
                    'placeholder' => 'Example: Two Layers',
                    'help_text' => 'Add available bandana mask-layer configurations.',
                    'visual' => 'customization',
                ],
                'bandana_imprint_method_option' => [
                    'label' => 'Imprint Method Option',
                    'placeholder' => 'Example: Dye Sublimation',
                    'help_text' => 'Add bandana imprint methods used during product configuration.',
                    'visual' => 'imprint',
                ],
            ],
        ],
        'premium_scarf' => [
            'number' => '1.28',
            'label' => 'Premium Scarf Customization',
            'options' => [
                'premium_scarf_size_option' => [
                    'label' => 'Size Option',
                    'placeholder' => 'Example: 145 x 18 cm',
                    'help_text' => 'Add premium scarf size choices used during product configuration.',
                    'visual' => 'size',
                ],
                'premium_scarf_material_option' => [
                    'label' => 'Material Option',
                    'placeholder' => 'Example: Acrylic Knit',
                    'help_text' => 'Add premium scarf material choices with a short description when composition or finish needs explanation.',
                    'visual' => 'material',
                    'uses_description' => true,
                ],
                'premium_scarf_craft_option' => [
                    'label' => 'Craft Option',
                    'placeholder' => 'Example: Jacquard Knit',
                    'help_text' => 'Add premium scarf craft or construction choices.',
                    'visual' => 'customization',
                ],
                'premium_scarf_layer_option' => [
                    'label' => 'Layer Option',
                    'placeholder' => 'Example: Double Layer',
                    'help_text' => 'Add premium scarf layer-construction choices.',
                    'visual' => 'customization',
                ],
                'premium_scarf_imprint_size_option' => [
                    'label' => 'Imprint Size Option',
                    'placeholder' => 'Example: 8 x 4 cm',
                    'help_text' => 'Add premium scarf imprint-size choices for artwork sizing.',
                    'visual' => 'size',
                ],
                'premium_scarf_yarn_color_option' => [
                    'label' => 'Yarn Color Option',
                    'placeholder' => 'Example: Royal Blue',
                    'help_text' => 'Add premium scarf yarn colors using the shared exact-color controls.',
                    'visual' => 'color',
                    'uses_color' => true,
                ],
            ],
        ],
    ];

    /** @var array<string, array{image_title: string, image_description: string, image_cta: string}> */
    private const VISUALS = [
        'size' => [
            'image_title' => 'Optional size reference image',
            'image_description' => 'Optional. Add a size reference only when dimensions need a visual explanation.',
            'image_cta' => 'Choose size image',
        ],
        'material' => [
            'image_title' => 'Optional material image',
            'image_description' => 'Optional. Add a close-up only when the material or finish needs a visual preview.',
            'image_cta' => 'Choose material image',
        ],
        'imprint' => [
            'image_title' => 'Optional imprint image',
            'image_description' => 'Optional. Add a reference image when the imprint style needs a visual preview.',
            'image_cta' => 'Choose imprint image',
        ],
        'customization' => [
            'image_title' => 'Optional customization image',
            'image_description' => 'Optional. Add a reference image when the customization needs a visual preview.',
            'image_cta' => 'Choose customization image',
        ],
        'area' => [
            'image_title' => 'Optional imprint area image',
            'image_description' => 'Optional. Add a placement reference when the imprint area needs a visual explanation.',
            'image_cta' => 'Choose area image',
        ],
        'color' => [
            'image_title' => 'Optional color swatch',
            'image_description' => 'Optional. Add a real swatch only when the HEX color needs a visual reference.',
            'image_cta' => 'Choose swatch image',
        ],
    ];

    public static function metadata(JerseyCustomizationType $type, string $key): string
    {
        $definition = self::optionDefinition($type);
        $value = $definition[$key] ?? self::VISUALS[$definition['visual'] ?? ''][$key] ?? null;

        if (! is_string($value) || $value === '') {
            throw new LogicException("Missing {$key} metadata for customization type [{$type->value}].");
        }

        return $value;
    }

    public static function groupFor(JerseyCustomizationType $type): string
    {
        foreach (self::GROUPS as $group => $definition) {
            if (array_key_exists($type->value, $definition['options'])) {
                return $group;
            }
        }

        throw new LogicException("Missing group metadata for customization type [{$type->value}].");
    }

    public static function menuOffset(string $group): int
    {
        return (int) (self::GROUPS[$group]['menu_offset'] ?? 0);
    }

    public static function groupLabel(string $group): string
    {
        return self::groupMetadata($group, 'label');
    }

    public static function groupNumber(string $group): string
    {
        return self::groupMetadata($group, 'number');
    }

    /** @return array<int, JerseyCustomizationType> */
    public static function typesForGroup(string $group): array
    {
        return array_map(
            static fn (string $value): JerseyCustomizationType => JerseyCustomizationType::from($value),
            array_keys(self::GROUPS[$group]['options'] ?? [])
        );
    }

    /** @return array<string, array{number: string, label: string, types: array<int, JerseyCustomizationType>}> */
    public static function menuGroups(): array
    {
        return collect(self::GROUPS)->map(fn (array $definition, string $group): array => [
            'number' => $definition['number'],
            'label' => $definition['label'],
            'types' => collect(self::typesForGroup($group))
                ->reject(static fn (JerseyCustomizationType $type): bool => self::isHiddenFromMenu($type))
                ->values()
                ->all(),
        ])->all();
    }

    /** @return array<int, JerseyCustomizationType> */
    public static function hiddenTypes(): array
    {
        return collect(self::GROUPS)
            ->flatMap(static fn (array $definition): array => array_keys($definition['options']))
            ->map(static fn (string $value): JerseyCustomizationType => JerseyCustomizationType::from($value))
            ->filter(static fn (JerseyCustomizationType $type): bool => self::isHiddenFromMenu($type))
            ->values()
            ->all();
    }

    public static function isHiddenFromMenu(JerseyCustomizationType $type): bool
    {
        return (bool) (self::optionDefinition($type, false)['hidden_from_menu'] ?? false);
    }

    public static function usesColorValue(JerseyCustomizationType $type): bool
    {
        return (bool) (self::optionDefinition($type, false)['uses_color'] ?? false);
    }

    /** @return array<int, JerseyCustomizationType> */
    public static function descriptionTypes(): array
    {
        $values = [];
        foreach (self::GROUPS as $definition) {
            foreach ($definition['options'] as $value => $option) {
                if (($option['uses_description'] ?? false) === true) {
                    $values[] = $value;
                }
            }
        }

        return array_map(static fn (string $value): JerseyCustomizationType => JerseyCustomizationType::from($value), $values);
    }

    /** @return array<string, mixed> */
    private static function optionDefinition(JerseyCustomizationType $type, bool $required = true): array
    {
        foreach (self::GROUPS as $definition) {
            if (isset($definition['options'][$type->value])) {
                return $definition['options'][$type->value];
            }
        }

        if ($required) {
            throw new LogicException("Missing registry metadata for customization type [{$type->value}].");
        }

        return [];
    }

    private static function groupMetadata(string $group, string $key): string
    {
        $value = self::GROUPS[$group][$key] ?? null;
        if (! is_string($value) || $value === '') {
            throw new LogicException("Missing {$key} metadata for customization group [{$group}].");
        }

        return $value;
    }
}
