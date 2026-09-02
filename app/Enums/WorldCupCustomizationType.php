<?php

namespace App\Enums;

use App\Support\WorldCupCustomizationRegistry;
use Illuminate\Support\Str;

enum WorldCupCustomizationType: string
{
    case DrawstringMaterialsOption = 'world_cup_drawstring_materials_option';
    case DrawstringSizeOption = 'world_cup_drawstring_size_option';
    case DrawstringSampleChargeOption = 'world_cup_drawstring_sample_charge_option';
    case FanCapMaterialsOption = 'world_cup_fan_cap_materials_option';
    case FanCapSizeOption = 'world_cup_fan_cap_size_option';
    case CarFlagWithElasticMaterialsOption = 'world_cup_car_flag_with_elastic_materials_option';
    case CarFlagWithElasticSizeOption = 'world_cup_car_flag_with_elastic_size_option';
    case RearviewMirrorCoverMaterialsOption = 'world_cup_rearview_mirror_cover_materials_option';
    case RearviewMirrorCoverSizeOption = 'world_cup_rearview_mirror_cover_size_option';
    case JacquardScarfMaterialsOption = 'world_cup_jacquard_scarf_materials_option';
    case JacquardScarfSizeOption = 'world_cup_jacquard_scarf_size_option';
    case DyeSubScarfMaterialsOption = 'world_cup_dye_sub_scarf_materials_option';
    case DyeSubScarfSizeOption = 'world_cup_dye_sub_scarf_size_option';
    case StringFlagMaterialsOption = 'world_cup_string_flag_materials_option';
    case StringFlagSizeOption = 'world_cup_string_flag_size_option';
    case HandFlagMaterialsOption = 'world_cup_hand_flag_materials_option';
    case HandFlagSizeOption = 'world_cup_hand_flag_size_option';
    case HoodedCapeFlagMaterialsOption = 'world_cup_hooded_cape_flag_materials_option';
    case HoodedCapeFlagSizeOption = 'world_cup_hooded_cape_flag_size_option';
    case BodyCapeFlagMaterialsOption = 'world_cup_body_cape_flag_materials_option';
    case BodyCapeFlagSizeOption = 'world_cup_body_cape_flag_size_option';
    case FanShirtMaterialsOption = 'world_cup_fan_shirt_materials_option';
    case FanShirtSizeOption = 'world_cup_fan_shirt_size_option';
    case InflatableCheerSticksMaterialsOption = 'world_cup_inflatable_cheer_sticks_materials_option';
    case InflatableCheerSticksSizeOption = 'world_cup_inflatable_cheer_sticks_size_option';
    case PvcKeyRingMaterialsOption = 'world_cup_pvc_key_ring_materials_option';
    case PvcKeyRingSizeOption = 'world_cup_pvc_key_ring_size_option';
    case MetalKeyRingMaterialsOption = 'world_cup_metal_key_ring_materials_option';
    case MetalKeyRingSizeOption = 'world_cup_metal_key_ring_size_option';
    case FanFacePaintMaterialsOption = 'world_cup_fan_face_paint_materials_option';
    case FanFacePaintSizeOption = 'world_cup_fan_face_paint_size_option';
    case FanHatMaterialsOption = 'world_cup_fan_hat_materials_option';
    case FanHatSizeOption = 'world_cup_fan_hat_size_option';
    case FanGlassMaterialsOption = 'world_cup_fan_glass_materials_option';
    case FanGlassSizeOption = 'world_cup_fan_glass_size_option';
    case TrophyMaterialsOption = 'world_cup_trophy_materials_option';
    case TrophySizeOption = 'world_cup_trophy_size_option';
    case ArmbandMaterialsOption = 'world_cup_armband_materials_option';
    case ArmbandSizeOption = 'world_cup_armband_size_option';
    case FanWigMaterialsOption = 'world_cup_fan_wig_materials_option';
    case FanWigSizeOption = 'world_cup_fan_wig_size_option';
    case FanTowelMaterialsOption = 'world_cup_fan_towel_materials_option';
    case FanTowelSizeOption = 'world_cup_fan_towel_size_option';
    case HandClapperMaterialsOption = 'world_cup_hand_clapper_materials_option';
    case HandClapperSizeOption = 'world_cup_hand_clapper_size_option';
    case ComboMaterialsOption = 'world_cup_combo_materials_option';
    case ComboSizeOption = 'world_cup_combo_size_option';
    case HeadbandMaterialsOption = 'world_cup_headband_materials_option';
    case HeadbandSizeOption = 'world_cup_headband_size_option';
    case PennantMaterialsOption = 'world_cup_pennant_materials_option';
    case PennantSizeOption = 'world_cup_pennant_size_option';

    public function label(): string
    {
        return WorldCupCustomizationRegistry::optionMetadata($this, 'label');
    }

    public function categoryKey(): string
    {
        return WorldCupCustomizationRegistry::categoryFor($this);
    }

    public function categoryLabel(): string
    {
        return WorldCupCustomizationRegistry::categoryMetadata($this->categoryKey(), 'label');
    }

    public function categoryNumber(): string
    {
        return WorldCupCustomizationRegistry::categoryMetadata($this->categoryKey(), 'number');
    }

    public function group(): string
    {
        return 'world_cup';
    }

    public function groupLabel(): string
    {
        return 'World Cup Customization';
    }

    public function groupNumber(): string
    {
        return '1.24';
    }

    public function menuNumber(): string
    {
        $types = WorldCupCustomizationRegistry::typesForCategory($this->categoryKey());
        $index = collect($types)->search(fn (self $type): bool => $type === $this);

        return $this->categoryNumber().'.'.(((int) $index) + 1);
    }

    public function placeholder(): string
    {
        return WorldCupCustomizationRegistry::optionMetadata($this, 'placeholder');
    }

    public function helpText(): string
    {
        return WorldCupCustomizationRegistry::optionMetadata($this, 'help_text');
    }

    public function imageTitle(): string
    {
        return WorldCupCustomizationRegistry::optionMetadata($this, 'image_title');
    }

    public function imageDescription(): string
    {
        return WorldCupCustomizationRegistry::optionMetadata($this, 'image_description');
    }

    public function imageCta(): string
    {
        return WorldCupCustomizationRegistry::optionMetadata($this, 'image_cta');
    }

    public function usesDescription(): bool
    {
        return WorldCupCustomizationRegistry::usesDescription($this);
    }

    public function usesColorValue(): bool
    {
        return false;
    }

    public function productCode(): string
    {
        return Str::slug($this->label());
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            static fn (self $type): array => [$type->value => $type->label()]
        )->all();
    }
}
