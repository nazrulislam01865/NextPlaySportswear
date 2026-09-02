<?php

namespace App\Enums;

use App\Support\ProductCustomizationOptionRegistry;
use Illuminate\Support\Str;

enum JerseyCustomizationType: string
{
    case NeckAndCollar = 'neck_and_collar';
    case Fabric = 'fabric';
    case Color = 'color';
    case SleevesAndCuffs = 'sleeves_and_cuffs';
    case JerseyStyle = 'jersey_style';
    case JerseyImprintOption = 'jersey_imprint_option';
    case JerseyLogoOption = 'jersey_logo_option';
    case JerseyPipingOption = 'jersey_piping_option';
    case JerseyFabricPatternOption = 'jersey_fabric_pattern_option';

    case ShortsColor = 'shorts_color';
    case ShortsFabric = 'shorts_fabric';
    case ShortsSize = 'shorts_size';
    case ShortsPocketOption = 'shorts_pocket_option';
    case ShortsRopeOption = 'shorts_rope_option';
    case ShortsElasticWaistDrawcordOption = 'shorts_elastic_waist_drawcord_option';
    case ShortsImprintOption = 'shorts_imprint_option';
    case ShortsImprintAreaOption = 'shorts_imprint_area_option';

    case UniformType = 'uniform_type';
    case UniformStyle = 'uniform_style';
    case UniformFabric = 'uniform_fabric';
    case UniformNeckline = 'uniform_neckline';
    case UniformSleeve = 'uniform_sleeve';
    case UniformSize = 'uniform_size';
    case UniformPocket = 'uniform_pocket';

    case PantsColor = 'pants_color';
    case PantsFabric = 'pants_fabric';
    case PantsCalfStyle = 'pants_calf_style';
    case PantsSize = 'pants_size';
    case PantsPocketOption = 'pants_pocket_option';
    case PantsRopeOption = 'pants_rope_option';
    case PantsElasticWaistDrawcordOption = 'pants_elastic_waist_drawcord_option';
    case PantsImprintOption = 'pants_imprint_option';
    case PantsImprintAreaOption = 'pants_imprint_area_option';
    case PantsLogoOption = 'pants_logo_option';
    case PantsPipingOption = 'pants_piping_option';

    case HoodieColor = 'hoodie_color';
    case HoodieFabric = 'hoodie_fabric';
    case HoodieHoodType = 'hoodie_hood_type';
    case HoodieClosure = 'hoodie_closure';
    case HoodieSleeve = 'hoodie_sleeve';
    case HoodiePocket = 'hoodie_pocket';
    case HoodieSize = 'hoodie_size';
    case HoodieCuff = 'hoodie_cuff';
    case HoodieDifferentNameAndNumberOption = 'hoodie_different_name_and_number_option';
    case HoodieImprintOption = 'hoodie_imprint_option';
    case HoodieImprintAreaOption = 'hoodie_imprint_area_option';
    case HoodieHoodDrawstringOption = 'hoodie_hood_drawstring_option';

    case PoloColor = 'polo_color';
    case PoloFabric = 'polo_fabric';
    case PoloCollarStyle = 'polo_collar_style';
    case PoloSleeve = 'polo_sleeve';
    case PoloPocketOption = 'polo_pocket_option';
    // Legacy value kept temporarily for safe migration of existing databases.
    case PoloImprintMethodOption = 'polo_imprint_method_option';
    case PoloImprintAreaOption = 'polo_imprint_area_option';
    case PoloBackDetailOption = 'polo_back_detail_option';
    case PoloImprintOption = 'polo_imprint_option';
    case PoloDifferentNameAndNumberOption = 'polo_different_name_and_number_option';
    case PoloSizeAdditionalChargesOption = 'polo_size_additional_charges_option';

    case TshirtColor = 'tshirt_color';
    case TshirtFabric = 'tshirt_fabric';
    case TshirtSleeve = 'tshirt_sleeve';
    case TshirtNeck = 'tshirt_neck';
    case TshirtPocketOption = 'tshirt_pocket_option';
    case TshirtImprintOption = 'tshirt_imprint_option';
    case TshirtImprintAreaOption = 'tshirt_imprint_area_option';
    case TshirtBackDetailOption = 'tshirt_back_detail_option';
    case TshirtDifferentNameAndNumberOption = 'tshirt_different_name_and_number_option';

    case QuarterZipColor = 'quarter_zip_color';
    case QuarterZipFabric = 'quarter_zip_fabric';
    case QuarterZipZipper = 'quarter_zip_zipper';
    case QuarterZipSleeve = 'quarter_zip_sleeve';
    case QuarterZipImprintOption = 'quarter_zip_imprint_option';
    case QuarterZipPocketOption = 'quarter_zip_pocket_option';
    case QuarterZipNeckOption = 'quarter_zip_neck_option';
    case QuarterZipSize = 'quarter_zip_size';

    case TankTopColor = 'tank_top_color';
    case TankTopFabric = 'tank_top_fabric';
    case TankTopStyle = 'tank_top_style';
    case TankTopImprintOption = 'tank_top_imprint_option';
    case TankTopImprintAreaOption = 'tank_top_imprint_area_option';
    case TankTopNeckOption = 'tank_top_neck_option';
    case TankTopBackDetailOption = 'tank_top_back_detail_option';
    case TankTopPocketOption = 'tank_top_pocket_option';
    case TankTopDifferentNameAndNumberChargesOption = 'tank_top_different_name_and_number_charges_option';
    case TankTopSize = 'tank_top_size';

    case CompressionWearColor = 'compression_wear_color';
    case CompressionWearMaterials = 'compression_wear_materials';
    case CompressionWearPattern = 'compression_wear_pattern';
    case CompressionWearImprintOption = 'compression_wear_imprint_option';
    case CompressionWearWaistType = 'compression_wear_waist_type';
    case CompressionWearLegLength = 'compression_wear_leg_length';
    case CompressionWearPocketDrawstringOption = 'compression_wear_pocket_drawstring_option';

    case SocksColor = 'socks_color';
    case SocksPattern = 'socks_pattern';
    case SocksMaterialConstruction = 'socks_material_construction';
    case SocksThicknessOption = 'socks_thickness_option';
    case SocksYarnOption = 'socks_yarn_option';
    case SocksTypesOption = 'socks_types_option';
    case SocksImprintMethodOption = 'socks_imprint_method_option';

    case SweatshirtColor = 'sweatshirt_color';
    case SweatshirtFabric = 'sweatshirt_fabric';
    case SweatshirtNeck = 'sweatshirt_neck';
    case SweatshirtSleeve = 'sweatshirt_sleeve';
    case SweatshirtCuff = 'sweatshirt_cuff';
    case SweatshirtPocket = 'sweatshirt_pocket';
    case SweatshirtHem = 'sweatshirt_hem';
    case SweatshirtStyle = 'sweatshirt_style';
    case SweatshirtImprintOption = 'sweatshirt_imprint_option';
    case SweatshirtImprintAreaOption = 'sweatshirt_imprint_area_option';
    case SweatshirtDifferentNameAndNumberSurchargeOption = 'sweatshirt_different_name_and_number_surcharge_option';
    case SweatshirtDBackOption = 'sweatshirt_d_back_option';
    case SweatshirtZipperOption = 'sweatshirt_zipper_option';
    case SweatshirtSize = 'sweatshirt_size';

    case JacketColor = 'jacket_color';
    case JacketOuterFabric = 'jacket_outer_fabric';
    case JacketInnerFabric = 'jacket_inner_fabric';
    case JacketType = 'jacket_type';
    case JacketClosure = 'jacket_closure';
    case JacketCollarHood = 'jacket_collar_hood';
    case JacketSleeve = 'jacket_sleeve';
    case JacketPocket = 'jacket_pocket';
    case JacketCuff = 'jacket_cuff';
    case JacketHem = 'jacket_hem';
    case JacketImprintOption = 'jacket_imprint_option';
    case JacketImprintAreaOption = 'jacket_imprint_area_option';
    case JacketDifferentNameAndNumberOption = 'jacket_different_name_and_number_option';
    case JacketSize = 'jacket_size';


    case BagLogo = 'bag_logo';
    case BagScreenPrint = 'bag_screen_print';
    case BagColor = 'bag_color';
    case BagPrintSize = 'bag_print_size';
    case BagColorMode = 'bag_color_mode';
    case BagSizeOption = 'bag_size_option';
    case BagFabricOption = 'bag_fabric_option';

    case HeadwearWovenLogo = 'headwear_woven_logo';
    case HeadwearHeatLogo = 'headwear_heat_logo';
    case HeadwearMultiPosition = 'headwear_multi_position';
    case Headwear3dPuff = 'headwear_3d_puff';
    case HeadwearFlatEmbroidery = 'headwear_flat_embroidery';
    case HeadwearDyeSublimation = 'headwear_dye_sublimation';
    case HeadwearColor = 'headwear_color';
    case HeadwearSoftPvcPatch = 'headwear_soft_pvc_patch';
    case Headwear2dLogo = 'headwear_2d_logo';
    case HeadwearTeamNameNumber = 'headwear_team_name_number';
    case HeadwearClosureOption = 'headwear_closure_option';
    case HeadwearCrownOption = 'headwear_crown_option';
    case HeadwearVisorOption = 'headwear_visor_option';
    case HeadwearPanelsOption = 'headwear_panels_option';
    case HeadwearFabricOption = 'headwear_fabric_option';
    case CapPipingOption = 'cap_piping_option';
    case BeanieSizeOption = 'beanie_size_option';
    case BeanieKnittingStyleOption = 'beanie_knitting_style_option';
    case BeanieImprintMethodOption = 'beanie_imprint_method_option';
    case BeanieColorOption = 'beanie_color_option';

    case DrinkwareLocation = 'drinkware_location';
    case DrinkwareLaserPrint = 'drinkware_laser_print';
    case DrinkwareBrandingLogo = 'drinkware_branding_logo';
    case DrinkwareCustomGraphics = 'drinkware_custom_graphics';
    case DrinkwareMaterialOption = 'drinkware_material_option';
    case DrinkwareSampleChargeOption = 'drinkware_sample_charge_option';

    case LanyardColor = 'lanyard_color';
    case LanyardPrint = 'lanyard_print';
    case LanyardBackgroundColor = 'lanyard_background_color';
    case LanyardWidth = 'lanyard_width';
    case LanyardAttachment = 'lanyard_attachment';
    case LanyardLogo = 'lanyard_logo';
    case LanyardMaterialOption = 'lanyard_material_option';
    case LanyardStandardAttachmentOption = 'lanyard_standard_attachment_option';
    case LanyardAttachmentSurchargeOptions = 'lanyard_attachment_surcharge_options';

    case HeadbandLogo = 'headband_logo';
    case HeadbandPattern = 'headband_pattern';
    case HeadbandAngle = 'headband_angle';
    case HeadbandPackaging = 'headband_packaging';
    case HeadbandSizeOption = 'headband_size_option';
    case HeadbandMaterialOption = 'headband_material_option';
    case HeadbandImprintMethodOption = 'headband_imprint_method_option';

    case SiliconeWristbandProductSizeOption = 'silicone_wristband_product_size_option';
    case SiliconeWristbandMaterialOption = 'silicone_wristband_material_option';
    case SiliconeWristbandImprintMethodOption = 'silicone_wristband_imprint_method_option';
    case SiliconeWristbandCustomizedOptions = 'silicone_wristband_customized_options';

    case BaseballBeltSizeOption = 'baseball_belt_size_option';
    case BaseballBeltMaterialOption = 'baseball_belt_material_option';
    case BaseballBeltImprintOption = 'baseball_belt_imprint_option';
    case BaseballBeltImprintAreaOption = 'baseball_belt_imprint_area_option';
    case BaseballBeltImprintSizeOption = 'baseball_belt_imprint_size_option';
    case BaseballBeltColorOption = 'baseball_belt_color_option';

    case TowelSizeOption = 'towel_size_option';
    case TowelMaterialOption = 'towel_material_option';
    case TowelFrontFabricOption = 'towel_front_fabric_option';
    case TowelBackFabricOption = 'towel_back_fabric_option';
    case TowelImprintSizeOption = 'towel_imprint_size_option';
    case TowelAvailableBackingColorOption = 'towel_available_backing_color_option';

    case ArmsleeveSizeOption = 'armsleeve_size_option';
    case ArmsleeveFabricOption = 'armsleeve_fabric_option';
    case ArmsleeveImprintMethodOption = 'armsleeve_imprint_method_option';

    case FabricWristbandSizeOption = 'fabric_wristband_size_option';
    case FabricWristbandMaterialOption = 'fabric_wristband_material_option';
    case FabricWristbandStandardAttachmentOption = 'fabric_wristband_standard_attachment_option';
    case FabricWristbandImprintMethodOption = 'fabric_wristband_imprint_method_option';
    case FabricWristbandLockingClosuresOption = 'fabric_wristband_locking_closures_option';

    case WristbandsSizeOption = 'wristbands_size_option';
    case WristbandsMaterialOption = 'wristbands_material_option';
    case WristbandsImprintMethodOption = 'wristbands_imprint_method_option';

    case KnittedGlovesSizeOption = 'knitted_gloves_size_option';
    case KnittedGlovesLogoOption = 'knitted_gloves_logo_option';
    case KnittedGlovesMaterialOption = 'knitted_gloves_material_option';
    case KnittedGlovesColorOption = 'knitted_gloves_color_option';
    case KnittedGlovesTouchScreenFunctionOption = 'knitted_gloves_touch_screen_function_option';
    case KnittedGlovesInnerLiningOption = 'knitted_gloves_inner_lining_option';
    case KnittedGlovesCuffTypeOption = 'knitted_gloves_cuff_type_option';
    case KnittedGlovesFabricFeatureOption = 'knitted_gloves_fabric_feature_option';

    case BandanaSizeOption = 'bandana_size_option';
    case BandanaFabricOption = 'bandana_fabric_option';
    case BandanaMaskLayersOption = 'bandana_mask_layers_option';
    case BandanaImprintMethodOption = 'bandana_imprint_method_option';

    case TrainingVestColorOption = 'training_vest_color_option';
    case TrainingVestFabricOption = 'training_vest_fabric_option';
    case TrainingVestSizeOption = 'training_vest_size_option';
    case TrainingVestVestTypeOption = 'training_vest_vest_type_option';
    case TrainingVestImprintOption = 'training_vest_imprint_option';
    case TrainingVestLogoOption = 'training_vest_logo_option';

    case PremiumScarfSizeOption = 'premium_scarf_size_option';
    case PremiumScarfMaterialOption = 'premium_scarf_material_option';
    case PremiumScarfCraftOption = 'premium_scarf_craft_option';
    case PremiumScarfLayerOption = 'premium_scarf_layer_option';
    case PremiumScarfImprintSizeOption = 'premium_scarf_imprint_size_option';
    case PremiumScarfYarnColorOption = 'premium_scarf_yarn_color_option';

    public function label(): string
    {
        return match ($this) {
            self::NeckAndCollar => 'Neck and Collar',
            self::Fabric => 'Fabric',
            self::Color => 'Color',
            self::SleevesAndCuffs => 'Sleeves and Cuffs',
            self::JerseyStyle => 'Jersey Style',
            self::JerseyImprintOption => 'Imprint Option',
            self::JerseyLogoOption => 'Logo Option',
            self::JerseyPipingOption => 'Piping Option',
            self::JerseyFabricPatternOption => 'Fabric Pattern Option',
            self::ShortsColor => 'Shorts Color',
            self::ShortsFabric => 'Shorts Fabric',
            self::ShortsSize => 'Shorts Size',
            self::ShortsPocketOption => 'Shorts Pocket Option',
            self::ShortsRopeOption => 'Rope Option',
            self::ShortsElasticWaistDrawcordOption => 'Elastic Waist Drawcord Option',
            self::ShortsImprintOption => 'Imprint Option',
            self::ShortsImprintAreaOption => 'Imprint Area Option',
            self::UniformType => 'Uniform Type',
            self::UniformStyle => 'Standard / Reversible',
            self::UniformFabric => 'Uniform Fabric',
            self::UniformNeckline => 'Uniform Neckline',
            self::UniformSleeve => 'Uniform Sleeve',
            self::UniformSize => 'Uniform Size',
            self::UniformPocket => 'Uniform Pocket',
            self::PantsColor => 'Pants Color',
            self::PantsFabric => 'Pants Fabric',
            self::PantsCalfStyle => 'Pants Calf Style',
            self::PantsSize => 'Pants Size',
            self::PantsPocketOption => 'Pocket Option',
            self::PantsRopeOption => 'Rope Option',
            self::PantsElasticWaistDrawcordOption => 'Elastic Waist Drawcord Option',
            self::PantsImprintOption => 'Imprint Option',
            self::PantsImprintAreaOption => 'Imprint Area Option',
            self::PantsLogoOption => 'Logo Option',
            self::PantsPipingOption => 'Piping Option',
            self::HoodieColor => 'Hoodie Color',
            self::HoodieFabric => 'Hoodie Fabric',
            self::HoodieHoodType => 'Hood Type',
            self::HoodieClosure => 'Zipper / Full-Zip / Half-Zip',
            self::HoodieSleeve => 'Hoodie Sleeve',
            self::HoodiePocket => 'Hoodie Pocket',
            self::HoodieSize => 'Hoodie Size',
            self::HoodieCuff => 'Hoodie Cuff',
            self::HoodieDifferentNameAndNumberOption => 'Different Name and Number Option',
            self::HoodieImprintOption => 'Imprint Option',
            self::HoodieImprintAreaOption => 'Imprint Area Option',
            self::HoodieHoodDrawstringOption => 'Hood Drawstring Option',
            self::PoloColor => 'Polo Color',
            self::PoloFabric => 'Polo Fabric',
            self::PoloCollarStyle => 'Polo Collar Style',
            self::PoloSleeve => 'Polo Sleeve',
            self::PoloPocketOption => 'Polo Pocket Option',
            self::PoloImprintMethodOption => 'Imprint Method Option',
            self::PoloImprintAreaOption => 'Imprint Area Option',
            self::PoloBackDetailOption => 'Back Detail Option',
            self::PoloImprintOption => 'Imprint Option',
            self::PoloDifferentNameAndNumberOption => 'Different Name and Number Option',
            self::PoloSizeAdditionalChargesOption => 'SIZE Additional Charges Option',
            self::TshirtColor => 'T-Shirt Color',
            self::TshirtFabric => 'T-Shirt Fabric',
            self::TshirtSleeve => 'T-Shirt Sleeve',
            self::TshirtNeck => 'T-Shirt Neck',
            self::TshirtPocketOption => 'Pocket Option',
            self::TshirtImprintOption => 'Imprint Option',
            self::TshirtImprintAreaOption => 'Imprint Area Option',
            self::TshirtBackDetailOption => 'Back Detail Option',
            self::TshirtDifferentNameAndNumberOption => 'Different Name and Number Option',
            self::QuarterZipColor => 'Quarter-Zip Color',
            self::QuarterZipFabric => 'Quarter-Zip Fabric',
            self::QuarterZipZipper => 'Quarter-Zip Zipper',
            self::QuarterZipSleeve => 'Quarter-Zip Sleeves',
            self::QuarterZipImprintOption => 'Imprint Option',
            self::QuarterZipPocketOption => 'Pocket Option',
            self::QuarterZipNeckOption => 'Neck Option',
            self::QuarterZipSize => 'Quarter-Zip Size',
            self::TankTopColor => 'Tank Top Color',
            self::TankTopFabric => 'Tank Top Fabric',
            self::TankTopStyle => 'Tank Top Style',
            self::TankTopImprintOption => 'Imprint Option',
            self::TankTopImprintAreaOption => 'Imprint Area Option',
            self::TankTopNeckOption => 'Neck Option',
            self::TankTopBackDetailOption => 'Back Detail Option',
            self::TankTopPocketOption => 'Pocket Option',
            self::TankTopDifferentNameAndNumberChargesOption => 'Different Name and Number Charges Option',
            self::TankTopSize => 'Tank Top Size',
            self::CompressionWearColor => 'Compression Wear Color',
            self::CompressionWearMaterials => 'Compression Wear Materials',
            self::CompressionWearPattern => 'Compression Wear Pattern',
            self::CompressionWearImprintOption => 'Imprint Option',
            self::CompressionWearWaistType => 'Waist Type',
            self::CompressionWearLegLength => 'Leg Length',
            self::CompressionWearPocketDrawstringOption => 'Pocket & Drawstring Option',
            self::SocksColor => 'Socks Color',
            self::SocksPattern => 'Socks Pattern',
            self::SocksMaterialConstruction => 'Socks Material Construction',
            self::SocksThicknessOption => 'Thickness Option',
            self::SocksYarnOption => 'Yarn Option',
            self::SocksTypesOption => 'Types Option',
            self::SocksImprintMethodOption => 'Imprint Method Option',
            self::SweatshirtColor => 'Sweatshirt Color',
            self::SweatshirtFabric => 'Sweatshirt Fabric',
            self::SweatshirtNeck => 'Sweatshirt Neck',
            self::SweatshirtSleeve => 'Sweatshirt Sleeve',
            self::SweatshirtCuff => 'Sweatshirt Cuff',
            self::SweatshirtPocket => 'Sweatshirt Pocket',
            self::SweatshirtHem => 'Sweatshirt Hem',
            self::SweatshirtStyle => 'Sweatshirt Style / Fit',
            self::SweatshirtImprintOption => 'Imprint Option',
            self::SweatshirtImprintAreaOption => 'Imprint Area Option',
            self::SweatshirtDifferentNameAndNumberSurchargeOption => 'Different Name and Number Surcharge Option',
            self::SweatshirtDBackOption => 'D Back Option',
            self::SweatshirtZipperOption => 'Zipper Option',
            self::SweatshirtSize => 'Sweatshirt Size',
            self::JacketColor => 'Jacket Color',
            self::JacketOuterFabric => 'Jacket Outer Fabric',
            self::JacketInnerFabric => 'Jacket Inner Fabric / Lining',
            self::JacketType => 'Jacket Type',
            self::JacketClosure => 'Jacket Closure',
            self::JacketCollarHood => 'Jacket Collar / Hood',
            self::JacketSleeve => 'Jacket Sleeve',
            self::JacketPocket => 'Jacket Pocket',
            self::JacketCuff => 'Jacket Cuff',
            self::JacketHem => 'Jacket Hem',
            self::JacketImprintOption => 'Imprint Option',
            self::JacketImprintAreaOption => 'Imprint Area Option',
            self::JacketDifferentNameAndNumberOption => 'Different Name and Number Option',
            self::JacketSize => 'Jacket Size',
            self::BagLogo => 'Logo',
            self::BagScreenPrint => 'Print',
            self::BagColor => 'Color',
            self::BagPrintSize => 'Print Size',
            self::BagColorMode => 'Single / Multicolor',
            self::BagSizeOption => 'Size Option',
            self::BagFabricOption => 'Fabric Option',
            self::HeadwearWovenLogo => 'Logo',
            self::HeadwearHeatLogo => 'Heat Logo',
            self::HeadwearMultiPosition => 'Multi Position',
            self::Headwear3dPuff => '3D Puff',
            self::HeadwearFlatEmbroidery => 'Flat Embroidery',
            self::HeadwearDyeSublimation => 'Print',
            self::HeadwearColor => 'Color',
            self::HeadwearSoftPvcPatch => 'Patch',
            self::Headwear2dLogo => '2D Logo',
            self::HeadwearTeamNameNumber => 'Custom Team Name / Number',
            self::HeadwearClosureOption => 'Closure Option',
            self::HeadwearCrownOption => 'Crown Option',
            self::HeadwearVisorOption => 'Visor Option',
            self::HeadwearPanelsOption => 'Panels Option',
            self::HeadwearFabricOption => 'Fabric Option',
            self::CapPipingOption => 'CAP Piping Option',
            self::BeanieSizeOption => 'Beanie Size Option',
            self::BeanieKnittingStyleOption => 'Beanie Knitting Style Option',
            self::BeanieImprintMethodOption => 'Beanie Imprint Method Option',
            self::BeanieColorOption => 'Beanie Color Option',
            self::DrinkwareLocation => 'Location of Print',
            self::DrinkwareLaserPrint => 'Print',
            self::DrinkwareBrandingLogo => 'Logo / Branding',
            self::DrinkwareCustomGraphics => 'Graphics',
            self::DrinkwareMaterialOption => 'Material Option',
            self::DrinkwareSampleChargeOption => 'Sample Charge Option',
            self::LanyardColor => 'Color',
            self::LanyardPrint => 'Print',
            self::LanyardBackgroundColor => 'Background Color',
            self::LanyardWidth => 'Lanyard Width',
            self::LanyardAttachment => 'Attachment',
            self::LanyardLogo => 'Logo',
            self::LanyardMaterialOption => 'Material Option',
            self::LanyardStandardAttachmentOption => 'Standard Attachment Option',
            self::LanyardAttachmentSurchargeOptions => 'Attachment Surcharge & Options',
            self::HeadbandLogo => 'Logo',
            self::HeadbandPattern => 'Pattern',
            self::HeadbandAngle => 'Wrap',
            self::HeadbandPackaging => 'Packaging',
            self::HeadbandSizeOption => 'Size Option',
            self::HeadbandMaterialOption => 'Material Option',
            self::HeadbandImprintMethodOption => 'Imprint Method Option',
            default => ProductCustomizationOptionRegistry::metadata($this, 'label'),
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::NeckAndCollar,
            self::Fabric,
            self::Color,
            self::SleevesAndCuffs,
            self::JerseyStyle,
            self::JerseyImprintOption,
            self::JerseyLogoOption,
            self::JerseyPipingOption,
            self::JerseyFabricPatternOption => 'jersey',
            self::ShortsColor,
            self::ShortsFabric,
            self::ShortsSize,
            self::ShortsPocketOption,
            self::ShortsRopeOption,
            self::ShortsElasticWaistDrawcordOption,
            self::ShortsImprintOption,
            self::ShortsImprintAreaOption => 'shorts',
            self::UniformType,
            self::UniformStyle,
            self::UniformFabric,
            self::UniformNeckline,
            self::UniformSleeve,
            self::UniformSize,
            self::UniformPocket => 'uniform',
            self::PantsColor,
            self::PantsFabric,
            self::PantsCalfStyle,
            self::PantsSize,
            self::PantsPocketOption,
            self::PantsRopeOption,
            self::PantsElasticWaistDrawcordOption,
            self::PantsImprintOption,
            self::PantsImprintAreaOption,
            self::PantsLogoOption,
            self::PantsPipingOption => 'pants',
            self::HoodieColor,
            self::HoodieFabric,
            self::HoodieHoodType,
            self::HoodieClosure,
            self::HoodieSleeve,
            self::HoodiePocket,
            self::HoodieSize,
            self::HoodieCuff,
            self::HoodieDifferentNameAndNumberOption,
            self::HoodieImprintOption,
            self::HoodieImprintAreaOption,
            self::HoodieHoodDrawstringOption => 'hoodie',
            self::PoloColor,
            self::PoloFabric,
            self::PoloCollarStyle,
            self::PoloSleeve,
            self::PoloPocketOption,
            self::PoloImprintMethodOption,
            self::PoloImprintAreaOption,
            self::PoloBackDetailOption,
            self::PoloImprintOption,
            self::PoloDifferentNameAndNumberOption,
            self::PoloSizeAdditionalChargesOption => 'polo',
            self::TshirtColor,
            self::TshirtFabric,
            self::TshirtSleeve,
            self::TshirtNeck,
            self::TshirtPocketOption,
            self::TshirtImprintOption,
            self::TshirtImprintAreaOption,
            self::TshirtBackDetailOption,
            self::TshirtDifferentNameAndNumberOption => 'tshirt',
            self::QuarterZipColor,
            self::QuarterZipFabric,
            self::QuarterZipZipper,
            self::QuarterZipSleeve,
            self::QuarterZipImprintOption,
            self::QuarterZipPocketOption,
            self::QuarterZipNeckOption,
            self::QuarterZipSize => 'quarter_zip',
            self::TankTopColor,
            self::TankTopFabric,
            self::TankTopStyle,
            self::TankTopImprintOption,
            self::TankTopImprintAreaOption,
            self::TankTopNeckOption,
            self::TankTopBackDetailOption,
            self::TankTopPocketOption,
            self::TankTopDifferentNameAndNumberChargesOption,
            self::TankTopSize => 'tank_top',
            self::CompressionWearColor,
            self::CompressionWearMaterials,
            self::CompressionWearPattern,
            self::CompressionWearImprintOption,
            self::CompressionWearWaistType,
            self::CompressionWearLegLength,
            self::CompressionWearPocketDrawstringOption => 'compression_wear',
            self::SocksColor,
            self::SocksPattern,
            self::SocksMaterialConstruction,
            self::SocksThicknessOption,
            self::SocksYarnOption,
            self::SocksTypesOption,
            self::SocksImprintMethodOption => 'socks',
            self::SweatshirtColor,
            self::SweatshirtFabric,
            self::SweatshirtNeck,
            self::SweatshirtSleeve,
            self::SweatshirtCuff,
            self::SweatshirtPocket,
            self::SweatshirtHem,
            self::SweatshirtStyle,
            self::SweatshirtImprintOption,
            self::SweatshirtImprintAreaOption,
            self::SweatshirtDifferentNameAndNumberSurchargeOption,
            self::SweatshirtDBackOption,
            self::SweatshirtZipperOption,
            self::SweatshirtSize => 'sweatshirt',
            self::JacketColor,
            self::JacketOuterFabric,
            self::JacketInnerFabric,
            self::JacketType,
            self::JacketClosure,
            self::JacketCollarHood,
            self::JacketSleeve,
            self::JacketPocket,
            self::JacketCuff,
            self::JacketHem,
            self::JacketImprintOption,
            self::JacketImprintAreaOption,
            self::JacketDifferentNameAndNumberOption,
            self::JacketSize => 'jacket',
            self::BagLogo,
            self::BagScreenPrint,
            self::BagColor,
            self::BagPrintSize,
            self::BagColorMode,
            self::BagSizeOption,
            self::BagFabricOption => 'bag',
            self::HeadwearWovenLogo,
            self::HeadwearHeatLogo,
            self::HeadwearMultiPosition,
            self::Headwear3dPuff,
            self::HeadwearFlatEmbroidery,
            self::HeadwearDyeSublimation,
            self::HeadwearColor,
            self::HeadwearSoftPvcPatch,
            self::Headwear2dLogo,
            self::HeadwearTeamNameNumber,
            self::HeadwearClosureOption,
            self::HeadwearCrownOption,
            self::HeadwearVisorOption,
            self::HeadwearPanelsOption,
            self::HeadwearFabricOption,
            self::CapPipingOption,
            self::BeanieSizeOption,
            self::BeanieKnittingStyleOption,
            self::BeanieImprintMethodOption,
            self::BeanieColorOption => 'headwear',
            self::DrinkwareLocation,
            self::DrinkwareLaserPrint,
            self::DrinkwareBrandingLogo,
            self::DrinkwareCustomGraphics,
            self::DrinkwareMaterialOption,
            self::DrinkwareSampleChargeOption => 'drinkware',
            self::LanyardColor,
            self::LanyardPrint,
            self::LanyardBackgroundColor,
            self::LanyardWidth,
            self::LanyardAttachment,
            self::LanyardLogo,
            self::LanyardMaterialOption,
            self::LanyardStandardAttachmentOption,
            self::LanyardAttachmentSurchargeOptions => 'lanyard',
            self::HeadbandLogo,
            self::HeadbandPattern,
            self::HeadbandAngle,
            self::HeadbandPackaging,
            self::HeadbandSizeOption,
            self::HeadbandMaterialOption,
            self::HeadbandImprintMethodOption => 'headband',
            default => ProductCustomizationOptionRegistry::groupFor($this),
        };
    }

    public function groupLabel(): string
    {
        return match ($this->group()) {
            'jersey' => 'Jersey Customization',
            'shorts' => 'Shorts Customization',
            'uniform' => 'Uniform Customization',
            'pants' => 'Pants Customization',
            'hoodie' => 'Hoodie Customization',
            'polo' => 'Polo Customization',
            'tshirt' => 'T-Shirt Customization',
            'quarter_zip' => 'Quarter-Zip Customization',
            'tank_top' => 'Tank Top Customization',
            'compression_wear' => 'Compression Wear Customization',
            'socks' => 'Socks Customization',
            'sweatshirt' => 'Sweatshirt Customization',
            'jacket' => 'Jacket Customization',
            'bag' => 'Bag Customization',
            'headwear' => 'Headwear Customization',
            'drinkware' => 'Drinkware Customization',
            'lanyard' => 'Lanyard Customization',
            'headband' => 'Headband Customization',
            default => ProductCustomizationOptionRegistry::groupLabel($this->group()),
        };
    }

    public function groupNumber(): string
    {
        return match ($this->group()) {
            'jersey' => '1.1',
            'shorts' => '1.2',
            'uniform' => '1.3',
            'pants' => '1.4',
            'hoodie' => '1.5',
            'polo' => '1.6',
            'tshirt' => '1.7',
            'quarter_zip' => '1.8',
            'tank_top' => '1.9',
            'compression_wear' => '1.10',
            'socks' => '1.11',
            'sweatshirt' => '1.12',
            'jacket' => '1.13',
            'bag' => '1.14',
            'headwear' => '1.15',
            'drinkware' => '1.16',
            'lanyard' => '1.17',
            'headband' => '1.18',
            default => ProductCustomizationOptionRegistry::groupNumber($this->group()),
        };
    }

    public function menuNumber(): string
    {
        if ($this->isSizeChartType()) {
            return self::sizeOptionMenuNumberForGroup($this->group());
        }

        $index = collect(self::menuTypesForGroup($this->group()))
            ->search(fn (self $type): bool => $type === $this);

        $offset = ProductCustomizationOptionRegistry::menuOffset($this->group());

        return $this->groupNumber().'.'.(((int) $index) + 1 + $offset);
    }

    public function isSizeChartType(): bool
    {
        return in_array($this, self::sizeChartTypes(), true);
    }

    public function productCode(): string
    {
        return Str::slug($this->label());
    }

    public function usesColorValue(): bool
    {
        return in_array($this, [
            self::Color,
            self::ShortsColor,
            self::PantsColor,
            self::HoodieColor,
            self::PoloColor,
            self::TshirtColor,
            self::QuarterZipColor,
            self::TankTopColor,
            self::CompressionWearColor,
            self::SocksColor,
            self::SweatshirtColor,
            self::JacketColor,
            self::BagColor,
            self::HeadwearColor,
            self::BeanieColorOption,
            self::LanyardColor,
            self::LanyardBackgroundColor,
        ], true) || ProductCustomizationOptionRegistry::usesColorValue($this);
    }

    public function usesDescription(): bool
    {
        return in_array($this, self::fabricTypes(), true);
    }

    public function placeholder(): string
    {
        return match ($this) {
            self::Color => 'Example: Navy Blue',
            self::NeckAndCollar => 'Example: V-Neck Collar',
            self::Fabric => 'Example: Dry Fit Mesh',
            self::SleevesAndCuffs => 'Example: Raglan Short Sleeve',
            self::JerseyStyle => 'Example: Pro Match Jersey',
            self::JerseyImprintOption => 'Example: Screen Print',
            self::JerseyLogoOption => 'Example: Embroidered Team Logo',
            self::JerseyPipingOption => 'Example: Contrast Shoulder Piping',
            self::JerseyFabricPatternOption => 'Example: Pinstripe Pattern',
            self::ShortsColor => 'Example: Royal Blue',
            self::ShortsFabric => 'Example: Lightweight Mesh',
            self::ShortsSize => 'Example: Adult Medium',
            self::ShortsPocketOption => 'Example: Side Pockets',
            self::ShortsRopeOption => 'Example: Round Braided Drawstring',
            self::ShortsElasticWaistDrawcordOption => 'Example: Elastic Waist with Internal Drawcord',
            self::ShortsImprintOption => 'Example: Screen Print',
            self::ShortsImprintAreaOption => 'Example: Left Leg',
            self::UniformType => 'Example: Basketball Uniform',
            self::UniformStyle => 'Example: Reversible',
            self::UniformFabric => 'Example: Performance Mesh Polyester',
            self::UniformNeckline => 'Example: V-Neck',
            self::UniformSleeve => 'Example: Sleeveless',
            self::UniformSize => 'Example: Youth Large',
            self::UniformPocket => 'Example: No Pocket',
            self::PantsColor => 'Example: White',
            self::PantsFabric => 'Example: Stretch Polyester',
            self::PantsCalfStyle => 'Example: Full Length',
            self::PantsSize => 'Example: Adult Large',
            self::PantsPocketOption => 'Example: Side Pockets',
            self::PantsRopeOption => 'Example: Round Braided Drawstring',
            self::PantsElasticWaistDrawcordOption => 'Example: Elastic Waist with Internal Drawcord',
            self::PantsImprintOption => 'Example: Screen Print',
            self::PantsImprintAreaOption => 'Example: Left Thigh',
            self::PantsLogoOption => 'Example: Embroidered Team Logo',
            self::PantsPipingOption => 'Example: Contrast Side Piping',
            self::HoodieColor => 'Example: Charcoal Gray',
            self::HoodieFabric => 'Example: Midweight Fleece',
            self::HoodieHoodType => 'Example: Double-Layer Hood',
            self::HoodieClosure => 'Example: Full-Zip',
            self::HoodieSleeve => 'Example: Long Sleeve',
            self::HoodiePocket => 'Example: Kangaroo Pocket',
            self::HoodieSize => 'Example: Adult Large',
            self::HoodieCuff => 'Example: Ribbed Cuff',
            self::HoodieDifferentNameAndNumberOption => 'Example: Different Player Name and Number',
            self::HoodieImprintOption => 'Example: Screen Print',
            self::HoodieImprintAreaOption => 'Example: Front Chest',
            self::HoodieHoodDrawstringOption => 'Example: Round Braided Hood Drawstring',
            self::PoloColor => 'Example: Team Red',
            self::PoloFabric => 'Example: Performance Pique',
            self::PoloCollarStyle => 'Example: Classic Polo Collar',
            self::PoloSleeve => 'Example: Short Sleeve',
            self::PoloPocketOption => 'Example: No Pocket',
            self::PoloImprintMethodOption => 'Example: Screen Print',
            self::PoloImprintAreaOption => 'Example: Left Chest',
            self::PoloBackDetailOption => 'Example: Contrast Back Yoke',
            self::PoloImprintOption => 'Example: Standard Team Imprint',
            self::PoloDifferentNameAndNumberOption => 'Example: Different Player Name and Number',
            self::PoloSizeAdditionalChargesOption => 'Example: 3XL / Additional Charge',
            self::TshirtColor => 'Example: Black',
            self::TshirtFabric => 'Example: Dry-Fit Polyester',
            self::TshirtSleeve => 'Example: Short Sleeve',
            self::TshirtNeck => 'Example: Crew Neck',
            self::TshirtPocketOption => 'Example: Left Chest Pocket',
            self::TshirtImprintOption => 'Example: Standard Team Imprint',
            self::TshirtImprintAreaOption => 'Example: Front Chest',
            self::TshirtBackDetailOption => 'Example: Contrast Back Panel',
            self::TshirtDifferentNameAndNumberOption => 'Example: Different Player Name and Number',
            self::QuarterZipColor => 'Example: Athletic Navy',
            self::QuarterZipFabric => 'Example: Performance Fleece',
            self::QuarterZipZipper => 'Example: Contrast Quarter Zipper',
            self::QuarterZipSleeve => 'Example: Raglan Long Sleeve',
            self::QuarterZipImprintOption => 'Example: Screen Print',
            self::QuarterZipPocketOption => 'Example: Zippered Side Pocket',
            self::QuarterZipNeckOption => 'Example: Mock Neck',
            self::QuarterZipSize => 'Example: Adult Large',
            self::TankTopColor => 'Example: Team Red',
            self::TankTopFabric => 'Example: Lightweight Mesh',
            self::TankTopStyle => 'Example: Racerback Tank',
            self::TankTopImprintOption => 'Example: Screen Print',
            self::TankTopImprintAreaOption => 'Example: Front Chest',
            self::TankTopNeckOption => 'Example: Scoop Neck',
            self::TankTopBackDetailOption => 'Example: Contrast Back Panel',
            self::TankTopPocketOption => 'Example: No Pocket',
            self::TankTopDifferentNameAndNumberChargesOption => 'Example: Different Player Name and Number',
            self::TankTopSize => 'Example: Adult Medium',
            self::CompressionWearColor => 'Example: Black',
            self::CompressionWearMaterials => 'Example: 4-Way Stretch Polyester Spandex',
            self::CompressionWearPattern => 'Example: Hex Pattern',
            self::CompressionWearImprintOption => 'Example: Heat Transfer',
            self::CompressionWearWaistType => 'Example: High Waist',
            self::CompressionWearLegLength => 'Example: Full Length',
            self::CompressionWearPocketDrawstringOption => 'Example: Side Pocket with Drawstring',
            self::SocksColor => 'Example: White / Royal Blue',
            self::SocksPattern => 'Example: Striped Crew Socks',
            self::SocksMaterialConstruction => 'Example: Cushioned Polyester Blend',
            self::SocksThicknessOption => 'Example: Midweight Cushion',
            self::SocksYarnOption => 'Example: Moisture-Wicking Polyester Yarn',
            self::SocksTypesOption => 'Example: Crew Socks',
            self::SocksImprintMethodOption => 'Example: Dye Sublimation',
            self::SweatshirtColor => 'Example: Heather Gray',
            self::SweatshirtFabric => 'Example: Brushed Cotton Fleece',
            self::SweatshirtNeck => 'Example: Crew Neck',
            self::SweatshirtSleeve => 'Example: Raglan Long Sleeve',
            self::SweatshirtCuff => 'Example: Ribbed Stretch Cuff',
            self::SweatshirtPocket => 'Example: Side Seam Pockets',
            self::SweatshirtHem => 'Example: Ribbed Waistband',
            self::SweatshirtStyle => 'Example: Relaxed Fit Pullover',
            self::SweatshirtImprintOption => 'Example: Screen Print',
            self::SweatshirtImprintAreaOption => 'Example: Left Chest',
            self::SweatshirtDifferentNameAndNumberSurchargeOption => 'Example: Different Player Name and Number',
            self::SweatshirtDBackOption => 'Example: D Back Detail',
            self::SweatshirtZipperOption => 'Example: Full-Zip Front',
            self::SweatshirtSize => 'Example: Adult Large',
            self::JacketColor => 'Example: Black / Team Red',
            self::JacketOuterFabric => 'Example: Water-Resistant Polyester Shell',
            self::JacketInnerFabric => 'Example: Quilted Fleece Lining',
            self::JacketType => 'Example: Varsity Jacket',
            self::JacketClosure => 'Example: Full-Zip Front',
            self::JacketCollarHood => 'Example: Stand Collar',
            self::JacketSleeve => 'Example: Set-In Long Sleeve',
            self::JacketPocket => 'Example: Zippered Side Pockets',
            self::JacketCuff => 'Example: Adjustable Hook-and-Loop Cuff',
            self::JacketHem => 'Example: Elastic Drawcord Hem',
            self::JacketImprintOption => 'Example: Screen Print',
            self::JacketImprintAreaOption => 'Example: Left Chest',
            self::JacketDifferentNameAndNumberOption => 'Example: Different Player Name and Number',
            self::JacketSize => 'Example: Adult Large',
            self::BagLogo => 'Example: Team Logo',
            self::BagScreenPrint => 'Example: Front print or screen print',
            self::BagColor => 'Example: Black',
            self::BagPrintSize => 'Example: 8 inch print',
            self::BagColorMode => 'Example: Single Color',
            self::BagSizeOption => 'Example: Medium Duffel',
            self::BagFabricOption => 'Example: 600D Polyester',
            self::HeadwearWovenLogo => 'Example: Front logo',
            self::HeadwearHeatLogo => 'Example: Heat Transfer Logo',
            self::HeadwearMultiPosition => 'Example: Front + Side',
            self::Headwear3dPuff => 'Example: Raised 3D Puff',
            self::HeadwearFlatEmbroidery => 'Example: Flat Embroidered Logo',
            self::HeadwearDyeSublimation => 'Example: Screen print or sublimation',
            self::HeadwearColor => 'Example: Navy Blue',
            self::HeadwearSoftPvcPatch => 'Example: Soft PVC patch',
            self::Headwear2dLogo => 'Example: 2D Logo Patch',
            self::HeadwearTeamNameNumber => 'Example: Team name + number',
            self::HeadwearClosureOption => 'Example: Adjustable Snapback',
            self::HeadwearCrownOption => 'Example: Structured Mid Crown',
            self::HeadwearVisorOption => 'Example: Curved Visor',
            self::HeadwearPanelsOption => 'Example: 6 Panel',
            self::HeadwearFabricOption => 'Example: Performance Polyester',
            self::CapPipingOption => 'Example: Contrast Visor Piping',
            self::BeanieSizeOption => 'Example: Adult One Size',
            self::BeanieKnittingStyleOption => 'Example: Rib Knit',
            self::BeanieImprintMethodOption => 'Example: Embroidery',
            self::BeanieColorOption => 'Example: Navy Blue',
            self::DrinkwareLocation => 'Example: Front center, back, or wrap',
            self::DrinkwareLaserPrint => 'Example: Laser print or screen print',
            self::DrinkwareBrandingLogo => 'Example: Brand logo or team mark',
            self::DrinkwareCustomGraphics => 'Example: Custom wrap graphic',
            self::DrinkwareMaterialOption => 'Example: Stainless Steel',
            self::DrinkwareSampleChargeOption => 'Example: Sample Setup Charge',
            self::LanyardColor => 'Example: Royal Blue',
            self::LanyardPrint => 'Example: One-side print',
            self::LanyardBackgroundColor => 'Example: White background',
            self::LanyardWidth => 'Example: 20 mm',
            self::LanyardAttachment => 'Example: Metal hook',
            self::LanyardLogo => 'Example: Repeating logo',
            self::LanyardMaterialOption => 'Example: Polyester',
            self::LanyardStandardAttachmentOption => 'Example: Metal Lobster Clip',
            self::LanyardAttachmentSurchargeOptions => 'Example: Premium Badge Reel Upgrade',
            self::HeadbandLogo => 'Example: Front logo',
            self::HeadbandPattern => 'Example: Stripe pattern',
            self::HeadbandAngle => 'Example: Full wrap artwork',
            self::HeadbandPackaging => 'Example: Individual poly bag',
            self::HeadbandSizeOption => 'Example: Adult One Size',
            self::HeadbandMaterialOption => 'Example: Performance Polyester',
            self::HeadbandImprintMethodOption => 'Example: Sublimation',
            default => ProductCustomizationOptionRegistry::metadata($this, 'placeholder'),
        };
    }

    public function helpText(): string
    {
        return match ($this) {
            self::Color => 'Add jersey colors with the exact display color. Upload a swatch only when the color needs a visual reference.',
            self::NeckAndCollar => 'Add neck and collar styles. Use a close-up image only when customers need to see the shape.',
            self::Fabric => 'Add fabric choices with short details so customers understand feel, texture, and performance.',
            self::SleevesAndCuffs => 'Add sleeve and cuff styles. Use a close-up image when the finish needs a visual preview.',
            self::JerseyStyle => 'Add jersey style options used across product configuration. Use a full jersey image when helpful.',
            self::JerseyImprintOption => 'Add jersey-only imprint choices such as screen print, heat transfer, embroidery, or sublimation. These values stay separate from print options used by other product groups.',
            self::JerseyLogoOption => 'Add jersey-only logo choices used during jersey configuration. These values stay separate from bag, headwear, drinkware, lanyard, and other logo master data.',
            self::JerseyPipingOption => 'Add jersey-only piping choices such as no piping, contrast piping, shoulder piping, or side piping.',
            self::JerseyFabricPatternOption => 'Add jersey-only fabric pattern choices such as solid, pinstripe, geometric, camo, or custom pattern. These values stay separate from fabric material and from pattern master data used by other categories.',
            self::ShortsColor => 'Add only the common shorts color choices that customers need while configuring shorts.',
            self::ShortsFabric => 'Add simple shorts fabric choices with short details when the material needs explanation.',
            self::ShortsSize => 'Add shorts-specific size values only when they are different from the main Size Options master data.',
            self::ShortsPocketOption => 'Add simple pocket choices such as no pocket, side pockets, or zipper pocket.',
            self::ShortsRopeOption => 'Add shorts-only rope choices such as no rope, round braided rope, or flat drawstring. These values stay separate from other product customization data.',
            self::ShortsElasticWaistDrawcordOption => 'Add shorts-only elastic waist and drawcord choices such as elastic only, internal drawcord, or external drawcord.',
            self::ShortsImprintOption => 'Add shorts-only imprint choices such as screen print, heat transfer, embroidery, or sublimation. These values stay separate from jersey and other imprint master data.',
            self::ShortsImprintAreaOption => 'Add shorts-only imprint area choices such as left leg, right leg, front thigh, back leg, or other supported placements. These values stay separate from shorts imprint methods and all other categories.',
            self::UniformType => 'Add uniform type choices that help admins separate basketball, soccer, baseball, or other uniform setups.',
            self::UniformStyle => 'Add simple uniform style choices such as standard or reversible.',
            self::UniformFabric => 'Add uniform-only fabric choices with short details such as performance mesh, lightweight polyester, reversible fabric, or moisture-wicking material. These are stored separately from jersey, shorts, and other fabric lists.',
            self::UniformNeckline => 'Add uniform neckline choices that customers need to select.',
            self::UniformSleeve => 'Add uniform sleeve choices such as sleeveless, short sleeve, or long sleeve.',
            self::UniformSize => 'Add uniform-specific size values only when they are different from the main Size Options master data.',
            self::UniformPocket => 'Add simple uniform pocket choices only when the uniform design supports pockets.',
            self::PantsColor => 'Add common pants color choices used in product configuration.',
            self::PantsFabric => 'Add pants fabric choices with short details when the material needs explanation.',
            self::PantsCalfStyle => 'Add calf or length choices such as full length or open-bottom style.',
            self::PantsSize => 'Add pants-specific size values only when they are different from the main Size Options master data.',
            self::PantsPocketOption => 'Add pants-only pocket choices such as no pocket, side pockets, zipper pockets, or back pockets. These values stay separate from shorts, hoodie, and other pocket master data.',
            self::PantsRopeOption => 'Add pants-only rope choices such as no rope, round braided rope, or flat drawstring. These values stay separate from shorts and other drawstring master data.',
            self::PantsElasticWaistDrawcordOption => 'Add pants-only elastic waist and drawcord choices such as elastic only, internal drawcord, or external drawcord. These values stay separate from shorts waist and drawcord options.',
            self::PantsImprintOption => 'Add pants-only imprint choices such as screen print, heat transfer, embroidery, or sublimation. These values stay separate from jersey, shorts, and other imprint master data.',
            self::PantsImprintAreaOption => 'Add pants-only imprint area choices such as left thigh, right thigh, lower leg, back leg, or other supported placements. These values stay separate from pants imprint methods and all other categories.',
            self::PantsLogoOption => 'Add pants-only logo choices used during pants configuration. These values stay separate from jersey, bag, headwear, drinkware, lanyard, and other logo master data.',
            self::PantsPipingOption => 'Add pants-only piping choices such as no piping, contrast side piping, double piping, or custom piping. These values stay separate from jersey and other piping master data.',
            self::HoodieColor => 'Add common hoodie color choices with exact color values for the admin product configuration.',
            self::HoodieFabric => 'Add simple hoodie fabric choices with short details such as fleece, interlock, or lightweight performance fabric.',
            self::HoodieHoodType => 'Add only useful hood choices such as single-layer hood, double-layer hood, or no hood when needed.',
            self::HoodieClosure => 'Add closure choices such as pullover, full-zip, half-zip, or quarter-zip.',
            self::HoodieSleeve => 'Add hoodie sleeve choices only when the product needs them.',
            self::HoodiePocket => 'Add simple hoodie pocket choices such as kangaroo pocket, side pockets, or no pocket.',
            self::HoodieSize => 'Add hoodie-specific size values only when they are different from the main Size Options master data.',
            self::HoodieCuff => 'Add cuff choices such as ribbed cuff, elastic cuff, or open cuff.',
            self::HoodieDifferentNameAndNumberOption => 'Add hoodie-only name and number choices for products that allow different player names and numbers. These values stay separate from jersey, headwear, and other personalization master data.',
            self::HoodieImprintOption => 'Add hoodie-only imprint choices such as screen print, heat transfer, embroidery, or sublimation. These values stay separate from jersey, shorts, and other imprint master data.',
            self::HoodieImprintAreaOption => 'Add hoodie-only imprint area choices such as front chest, full front, back, sleeve, or hood. These values stay separate from imprint method choices.',
            self::HoodieHoodDrawstringOption => 'Add hoodie-only hood drawstring choices such as flat drawcord, round braided drawstring, contrast drawstring, or no drawstring. These values stay separate from shorts and pants drawcord master data.',
            self::PoloColor => 'Add common polo color choices with exact color values for product configuration.',
            self::PoloFabric => 'Add simple polo fabric choices with short details when the material needs explanation.',
            self::PoloCollarStyle => 'Add collar choices such as classic polo collar, rib collar, or contrast collar.',
            self::PoloSleeve => 'Add polo sleeve choices such as short sleeve or long sleeve.',
            self::PoloPocketOption => 'Add simple polo pocket choices such as no pocket or chest pocket.',
            self::PoloImprintMethodOption => 'Legacy POLO imprint-method master data retained only so existing rows can be migrated safely.',
            self::PoloImprintAreaOption => 'Add POLO-only imprint area choices such as left chest, right chest, full front, upper back, sleeve, or collar. These values stay separate from imprint methods and other product groups.',
            self::PoloBackDetailOption => 'Add polo-only back detail choices such as plain back, contrast back yoke, mesh back panel, or custom back panel.',
            self::PoloImprintOption => 'Add polo-only imprint choices used during product configuration. These values stay separate from imprint areas and from jersey, shorts, hoodie, T-shirt, and other imprint master data.',
            self::PoloDifferentNameAndNumberOption => 'Add POLO-only personalization choices for products that allow different player names and numbers. These values stay separate from hoodie, T-shirt, and other name/number master data.',
            self::PoloSizeAdditionalChargesOption => 'Add reusable POLO size surcharge labels such as 2XL, 3XL, or 4XL. Configure the actual monetary additional charge per product so master data remains reusable.',
            self::TshirtColor => 'Add common T-shirt color choices with exact color values for product configuration.',
            self::TshirtFabric => 'Add simple T-shirt fabric choices with short details such as cotton, polyester, or dry-fit.',
            self::TshirtSleeve => 'Add T-shirt sleeve choices such as short sleeve, long sleeve, or sleeveless.',
            self::TshirtNeck => 'Add neck choices such as crew neck, V-neck, or round neck.',
            self::TshirtPocketOption => 'Add T-shirt-only pocket choices such as no pocket, left chest pocket, or utility pocket.',
            self::TshirtImprintOption => 'Add T-shirt-only imprint choices used during product configuration. These values stay separate from jersey, shorts, hoodie, polo, and other imprint master data.',
            self::TshirtImprintAreaOption => 'Add T-shirt-only imprint area choices such as front chest, full front, back, sleeve, or pocket area. These values stay separate from imprint choices.',
            self::TshirtBackDetailOption => 'Add T-shirt-only back detail choices such as plain back, contrast panel, mesh panel, or custom back detail.',
            self::TshirtDifferentNameAndNumberOption => 'Add T-shirt-only name and number choices for products that allow different player names and numbers. These values stay separate from hoodie and other personalization master data.',
            self::QuarterZipColor => 'Add common quarter-zip color choices with exact color values for product configuration.',
            self::QuarterZipFabric => 'Add simple quarter-zip fabric choices with short details such as fleece, interlock, or lightweight performance fabric.',
            self::QuarterZipZipper => 'Add zipper choices for quarter-zip products, such as matching zipper, contrast zipper, or hidden zipper.',
            self::QuarterZipSleeve => 'Add quarter-zip sleeve choices such as long sleeve, raglan sleeve, or contrast sleeve.',
            self::QuarterZipImprintOption => 'Add quarter-zip-only imprint choices such as screen print, heat transfer, embroidery, or sublimation. These values stay separate from hoodie, polo, T-shirt, jacket, and other imprint master data.',
            self::QuarterZipPocketOption => 'Add quarter-zip-only pocket choices such as no pocket, side pocket, zip pocket, or chest pocket.',
            self::QuarterZipNeckOption => 'Add quarter-zip-only neck choices such as mock neck, stand neck, funnel neck, or contrast neck. These values stay separate from T-shirt and other neckline master data.',
            self::QuarterZipSize => 'Add quarter-zip-specific size values only when they are different from the main Size Options master data.',
            self::TankTopColor => 'Add common tank top color choices with exact color values for product configuration.',
            self::TankTopFabric => 'Add tank top fabric choices with short details when the material needs explanation.',
            self::TankTopStyle => 'Add tank top style choices such as racerback, classic athletic cut, or reversible tank.',
            self::TankTopImprintOption => 'Add tank-top-only imprint choices such as screen print, heat transfer, embroidery, or sublimation. These values stay separate from T-shirt, polo, hoodie, jacket, and other imprint master data.',
            self::TankTopImprintAreaOption => 'Add tank-top-only imprint area choices such as front chest, full front, back, or other supported areas.',
            self::TankTopNeckOption => 'Add tank-top-only neck choices such as scoop neck, V-neck, high neck, or crew neck.',
            self::TankTopBackDetailOption => 'Add tank-top-only back detail choices such as racerback, open back, contrast panel, mesh panel, or custom back detail.',
            self::TankTopPocketOption => 'Add tank-top-only pocket choices such as no pocket or supported pocket styles.',
            self::TankTopDifferentNameAndNumberChargesOption => 'Add tank-top-only different name and number choices. Configure any monetary surcharge per selected value in Add Product using the shared Additional charge and Charge basis controls.',
            self::TankTopSize => 'Add tank top-specific size values only when they are different from the main Size Options master data.',
            self::CompressionWearColor => 'Add common compression wear color choices with exact color values for product configuration.',
            self::CompressionWearMaterials => 'Add compression wear material choices with short details, such as polyester spandex, nylon spandex, mesh panels, or moisture-wicking blends.',
            self::CompressionWearPattern => 'Add compression wear pattern choices such as solid, camo, hex, stripe, or gradient.',
            self::CompressionWearImprintOption => 'Add compression-wear-only imprint choices such as heat transfer, screen print, sublimation, or other supported methods. These values stay separate from other product imprint master data.',
            self::CompressionWearWaistType => 'Add compression-wear waist types as their own master data. These values stay separate from pants, shorts, and other waist or fit choices.',
            self::CompressionWearLegLength => 'Add compression-wear leg-length choices as their own master data so length selections do not share values with pants or other categories.',
            self::CompressionWearPocketDrawstringOption => 'Add compression-wear pocket and drawstring configurations as one dedicated feature type for this category only.',
            self::SocksColor => 'Add common socks color choices with exact color values for product configuration.',
            self::SocksPattern => 'Add socks pattern choices such as solid, striped, crew stripe, or gradient.',
            self::SocksMaterialConstruction => 'Add socks material and construction choices with short details such as cushioned sole, ribbed cuff, or polyester blend.',
            self::SocksThicknessOption => 'Add socks-only thickness choices such as lightweight, midweight, cushioned, or heavyweight. These values stay separate from material and construction choices.',
            self::SocksYarnOption => 'Add socks-only yarn choices such as polyester, nylon, cotton blend, or moisture-wicking performance yarn.',
            self::SocksTypesOption => 'Add socks-only type choices such as ankle, crew, knee-high, or over-the-calf.',
            self::SocksImprintMethodOption => 'Add socks-only imprint method choices such as dye sublimation, woven design, or other supported decoration methods. These values stay separate from other product imprint master data.',
            self::SweatshirtColor => 'Add sweatshirt colors with exact color values. Sweatshirt colors are stored independently from hoodie, jacket, jersey, and other color lists.',
            self::SweatshirtFabric => 'Add sweatshirt-only fabric choices with useful details such as brushed fleece, French terry, cotton blend, or performance polyester.',
            self::SweatshirtNeck => 'Add sweatshirt neck choices such as crew neck, mock neck, or V-neck. These choices are not shared with jersey or T-shirt neck options.',
            self::SweatshirtSleeve => 'Add sweatshirt sleeve choices such as set-in, raglan, long sleeve, or contrast sleeve.',
            self::SweatshirtCuff => 'Add sweatshirt cuff choices such as ribbed cuff, elastic cuff, thumbhole cuff, or open cuff.',
            self::SweatshirtPocket => 'Add sweatshirt pocket choices such as no pocket, side-seam pockets, kangaroo pocket, or zip pocket.',
            self::SweatshirtHem => 'Add sweatshirt hem choices such as ribbed waistband, straight hem, elastic hem, or split hem.',
            self::SweatshirtStyle => 'Add sweatshirt style and fit choices such as classic fit, relaxed fit, oversized, pullover, or performance cut.',
            self::SweatshirtImprintOption => 'Add sweatshirt-only imprint choices used during product configuration. These values stay separate from hoodie, jacket, polo, T-shirt, and other imprint master data.',
            self::SweatshirtImprintAreaOption => 'Add sweatshirt-only imprint area choices such as left chest, full front, back, or sleeve. These values stay separate from imprint method choices.',
            self::SweatshirtDifferentNameAndNumberSurchargeOption => 'Add sweatshirt-only different name and number choices. Configure any monetary surcharge per selected value in Add Product using the shared Additional charge and Charge basis controls.',
            self::SweatshirtDBackOption => 'Add sweatshirt-only D back choices or details used during product configuration. These values stay separate from other back-detail master data.',
            self::SweatshirtZipperOption => 'Add sweatshirt-only zipper choices. These values stay separate from hoodie, jacket, and quarter-zip closure data.',
            self::SweatshirtSize => 'Manage sweatshirt size values through the separate Sweatshirt Size Options master data.',
            self::JacketColor => 'Add jacket colors with exact color values. Jacket colors are stored independently from sweatshirt, hoodie, jersey, and other color lists.',
            self::JacketOuterFabric => 'Add jacket outer-shell fabrics such as water-resistant polyester, softshell, nylon, fleece, or varsity wool blend.',
            self::JacketInnerFabric => 'Add jacket inner fabric or lining choices such as mesh, fleece, quilted polyester, satin, or insulated lining.',
            self::JacketType => 'Add jacket types such as varsity, bomber, track, rain, softshell, windbreaker, or coach jacket.',
            self::JacketClosure => 'Add jacket closure choices such as full zip, snap buttons, half zip, hook-and-loop, or two-way zip.',
            self::JacketCollarHood => 'Add jacket collar or hood choices such as stand collar, baseball collar, detachable hood, fixed hood, or no hood.',
            self::JacketSleeve => 'Add jacket sleeve choices such as set-in, raglan, detachable, contrast, or articulated sleeve.',
            self::JacketPocket => 'Add jacket pocket choices such as side pockets, zip pockets, chest pocket, inside pocket, or no pocket.',
            self::JacketCuff => 'Add jacket cuff choices such as ribbed cuff, elastic cuff, snap cuff, adjustable cuff, or open cuff.',
            self::JacketHem => 'Add jacket hem choices such as ribbed hem, elastic hem, straight hem, drop-tail hem, or drawcord hem.',
            self::JacketImprintOption => 'Add jacket-only imprint choices such as screen print, heat transfer, embroidery, or sublimation. These values stay separate from quarter-zip, hoodie, polo, T-shirt, and other imprint master data.',
            self::JacketImprintAreaOption => 'Add jacket-only imprint placement choices such as left chest, right chest, back, sleeve, or other supported areas.',
            self::JacketDifferentNameAndNumberOption => 'Add jacket-only personalization choices for products that support different player names and numbers. These values stay separate from hoodie and T-shirt name/number options.',
            self::JacketSize => 'Manage jacket size values through the separate Jacket Size Options master data.',
            self::BagLogo => 'Add bag-only logo choices. These are stored separately from headwear, lanyard, drinkware, and jersey logos.',
            self::BagScreenPrint => 'Add bag-only print choices such as screen print, front print, or side print.',
            self::BagColor => 'Add bag-only color choices with exact color values. These are not shared with any other product color list.',
            self::BagPrintSize => 'Add print-size choices used for bag artwork setup.',
            self::BagColorMode => 'Add choices such as single color, multicolor, or full color print.',
            self::BagSizeOption => 'Add bag-only size choices such as small, medium, large, backpack, tote, or duffel sizes. Keep these separate from apparel size master data.',
            self::BagFabricOption => 'Add bag-only fabric choices such as polyester, nylon, canvas, mesh, or other bag materials. These values stay separate from apparel fabrics.',
            self::HeadwearWovenLogo => 'Add headwear-only logo choices for caps, hats, and related items.',
            self::HeadwearHeatLogo => 'Add heat-transfer logo choices for headwear.',
            self::HeadwearMultiPosition => 'Add position choices such as front, side, back, or multiple positions.',
            self::Headwear3dPuff => 'Add raised 3D puff embroidery choices.',
            self::HeadwearFlatEmbroidery => 'Add flat embroidery choices for headwear.',
            self::HeadwearDyeSublimation => 'Add headwear-only print choices such as sublimation, heat print, or panel print.',
            self::HeadwearColor => 'Add headwear-only color choices with exact color values. These are not shared with any other product color list.',
            self::HeadwearSoftPvcPatch => 'Add headwear-only patch choices such as PVC patch, woven patch, or leather patch.',
            self::Headwear2dLogo => 'Add 2D logo patch or flat logo choices.',
            self::HeadwearTeamNameNumber => 'Add team name and number personalization choices for headwear.',
            self::HeadwearClosureOption => 'Add headwear closure choices such as snapback, hook-and-loop, buckle, fitted, or elastic closures.',
            self::HeadwearCrownOption => 'Add headwear crown choices such as structured, unstructured, low, mid, or high crown.',
            self::HeadwearVisorOption => 'Add headwear visor choices such as flat, curved, pre-curved, sandwich, or contrast visor.',
            self::HeadwearPanelsOption => 'Add headwear panel choices such as 5-panel, 6-panel, or other supported constructions.',
            self::HeadwearFabricOption => 'Add headwear fabric choices such as cotton twill, polyester, performance mesh, wool blend, or nylon. These values stay separate from bag and apparel fabrics.',
            self::CapPipingOption => 'Add CAP-only piping choices. These values stay separate from jersey and pants piping master data.',
            self::BeanieSizeOption => 'Add Beanie-only size choices without sharing values with generic headwear or apparel size data.',
            self::BeanieKnittingStyleOption => 'Add Beanie-only knitting style choices as a dedicated master-data feature.',
            self::BeanieImprintMethodOption => 'Add Beanie-only imprint methods so decoration methods are not shared with generic headwear or other product categories.',
            self::BeanieColorOption => 'Add Beanie-only colors with the exact display color. These values stay separate from generic headwear colors.',
            self::DrinkwareLocation => 'Add drinkware-only print-location choices such as front, back, wrap, or lid area.',
            self::DrinkwareLaserPrint => 'Add drinkware-only print choices such as laser print, screen print, engraving, or wrap print.',
            self::DrinkwareBrandingLogo => 'Add drinkware-only logo and branding choices.',
            self::DrinkwareCustomGraphics => 'Add drinkware-only graphics choices used for custom artwork.',
            self::DrinkwareMaterialOption => 'Add drinkware material choices such as stainless steel, aluminum, plastic, ceramic, or glass. Keep these values separate from apparel and accessory materials.',
            self::DrinkwareSampleChargeOption => 'Add drinkware sample-charge choices that describe when a sample or setup charge can apply. Configure the actual amount per product with the shared Additional charge controls.',
            self::LanyardColor => 'Add lanyard-only color choices with exact color values. These are not shared with any other product color list.',
            self::LanyardPrint => 'Add lanyard-only print style choices such as one-side or two-side print.',
            self::LanyardBackgroundColor => 'Add background color choices for lanyard artwork.',
            self::LanyardWidth => 'Add lanyard width choices such as 15 mm, 20 mm, or 25 mm.',
            self::LanyardAttachment => 'Add attachment choices such as hook, buckle, clip, or badge reel.',
            self::LanyardLogo => 'Add lanyard-only logo choices for lanyard artwork.',
            self::LanyardMaterialOption => 'Add lanyard material choices such as polyester, nylon, recycled PET, satin, or woven material.',
            self::LanyardStandardAttachmentOption => 'Add standard lanyard attachment choices such as lobster clip, swivel hook, split ring, or badge clip.',
            self::LanyardAttachmentSurchargeOptions => 'Add premium attachment choices that may carry a surcharge. Configure the actual amount per product with the shared Additional charge controls.',
            self::HeadbandLogo => 'Add logo choices for headband customization.',
            self::HeadbandPattern => 'Add pattern choices such as solid, stripe, gradient, or custom pattern.',
            self::HeadbandAngle => 'Add headband-only wrap choices for full-wrap or partial-wrap artwork.',
            self::HeadbandPackaging => 'Add packaging choices for headband orders.',
            self::HeadbandSizeOption => 'Add headband-only size choices such as youth, adult, one size, or custom dimensions.',
            self::HeadbandMaterialOption => 'Add headband material choices such as polyester, cotton blend, spandex blend, or performance fabric.',
            self::HeadbandImprintMethodOption => 'Add headband imprint-method choices such as sublimation, screen print, heat transfer, or embroidery.',
            default => ProductCustomizationOptionRegistry::metadata($this, 'help_text'),
        };
    }

    public function imageTitle(): string
    {
        return match ($this) {
            self::Color,
            self::ShortsColor,
            self::PantsColor,
            self::HoodieColor,
            self::PoloColor,
            self::TshirtColor,
            self::QuarterZipColor,
            self::TankTopColor,
            self::CompressionWearColor,
            self::SocksColor,
            self::SweatshirtColor,
            self::JacketColor,
            self::BeanieColorOption => 'Optional color swatch',
            self::NeckAndCollar => 'Optional neck/collar image',
            self::Fabric,
            self::ShortsFabric,
            self::UniformFabric,
            self::PantsFabric,
            self::HoodieFabric,
            self::PoloFabric,
            self::TshirtFabric,
            self::QuarterZipFabric,
            self::TankTopFabric,
            self::CompressionWearMaterials,
            self::SocksMaterialConstruction,
            self::SweatshirtFabric,
            self::JacketOuterFabric,
            self::JacketInnerFabric,
            self::BagFabricOption,
            self::HeadwearFabricOption,
            self::DrinkwareMaterialOption,
            self::LanyardMaterialOption,
            self::HeadbandMaterialOption => 'Optional fabric texture image',
            self::SleevesAndCuffs,
            self::UniformSleeve,
            self::HoodieSleeve,
            self::PoloSleeve,
            self::TshirtSleeve,
            self::QuarterZipSleeve,
            self::SweatshirtSleeve,
            self::JacketSleeve => 'Optional sleeve image',
            self::JerseyStyle => 'Optional jersey style image',
            self::JerseyImprintOption => 'Optional imprint reference image',
            self::JerseyLogoOption,
            self::PantsLogoOption => 'Optional logo reference image',
            self::JerseyPipingOption,
            self::PantsPipingOption,
            self::CapPipingOption => 'Optional piping reference image',
            self::ShortsRopeOption,
            self::PantsRopeOption => 'Optional rope reference image',
            self::ShortsElasticWaistDrawcordOption,
            self::PantsElasticWaistDrawcordOption => 'Optional waist/drawcord reference image',
            self::ShortsImprintOption,
            self::PantsImprintOption => 'Optional imprint reference image',
            self::HoodieDifferentNameAndNumberOption => 'Optional name/number reference image',
            self::HoodieImprintOption => 'Optional imprint reference image',
            self::HoodieImprintAreaOption => 'Optional imprint area reference image',
            self::HoodieHoodDrawstringOption => 'Optional hood drawstring reference image',
            self::PoloImprintMethodOption,
            self::PoloImprintOption,
            self::TshirtImprintOption,
            self::QuarterZipImprintOption,
            self::TankTopImprintOption,
            self::CompressionWearImprintOption,
            self::BeanieImprintMethodOption,
            self::SocksImprintMethodOption,
            self::HeadbandImprintMethodOption,
            self::SweatshirtImprintOption,
            self::JacketImprintOption => 'Optional imprint reference image',
            self::PoloBackDetailOption,
            self::TshirtBackDetailOption,
            self::TankTopBackDetailOption,
            self::SweatshirtDBackOption => 'Optional back detail reference image',
            self::ShortsImprintAreaOption,
            self::PantsImprintAreaOption,
            self::PoloImprintAreaOption,
            self::TshirtImprintAreaOption,
            self::TankTopImprintAreaOption,
            self::SweatshirtImprintAreaOption,
            self::JacketImprintAreaOption => 'Optional imprint area reference image',
            self::PoloDifferentNameAndNumberOption,
            self::TshirtDifferentNameAndNumberOption,
            self::TankTopDifferentNameAndNumberChargesOption,
            self::SweatshirtDifferentNameAndNumberSurchargeOption,
            self::JacketDifferentNameAndNumberOption => 'Optional name/number reference image',
            self::ShortsSize,
            self::UniformSize,
            self::PantsSize,
            self::HoodieSize,
            self::QuarterZipSize,
            self::TankTopSize,
            self::SweatshirtSize,
            self::JacketSize,
            self::CompressionWearWaistType,
            self::CompressionWearLegLength,
            self::BeanieSizeOption,
            self::PoloSizeAdditionalChargesOption => 'Optional size reference image',
            self::ShortsPocketOption,
            self::PantsPocketOption,
            self::UniformPocket,
            self::HoodiePocket,
            self::PoloPocketOption,
            self::TshirtPocketOption,
            self::QuarterZipPocketOption,
            self::TankTopPocketOption,
            self::SweatshirtPocket,
            self::JacketPocket,
            self::CompressionWearPocketDrawstringOption => 'Optional pocket reference image',
            self::UniformType => 'Optional uniform type image',
            self::UniformStyle => 'Optional style reference image',
            self::UniformNeckline,
            self::TshirtNeck,
            self::QuarterZipNeckOption,
            self::TankTopNeckOption,
            self::SweatshirtNeck,
            self::JacketCollarHood => 'Optional neckline image',
            self::PantsCalfStyle => 'Optional calf style image',
            self::HoodieHoodType => 'Optional hood type image',
            self::HoodieClosure => 'Optional zipper/closure image',
            self::HoodieCuff,
            self::SweatshirtCuff,
            self::JacketCuff => 'Optional cuff image',
            self::PoloCollarStyle => 'Optional collar style image',
            self::QuarterZipZipper,
            self::JacketClosure,
            self::SweatshirtZipperOption => 'Optional zipper/closure image',
            self::TankTopStyle,
            self::SweatshirtStyle,
            self::BeanieKnittingStyleOption => 'Optional style reference image',
            self::SweatshirtHem,
            self::JacketHem => 'Optional hem reference image',
            self::JacketType => 'Optional jacket type image',
            self::BagLogo,
            self::HeadwearWovenLogo,
            self::HeadwearHeatLogo,
            self::Headwear2dLogo,
            self::DrinkwareBrandingLogo,
            self::LanyardLogo,
            self::HeadbandLogo => 'Optional logo reference image',
            self::BagScreenPrint,
            self::DrinkwareLaserPrint,
            self::LanyardPrint => 'Optional print method image',
            self::BagColor,
            self::HeadwearColor,
            self::LanyardColor,
            self::LanyardBackgroundColor => 'Optional color swatch',
            self::BagPrintSize,
            self::BagSizeOption,
            self::LanyardWidth,
            self::HeadbandSizeOption => 'Optional size reference image',
            self::BagColorMode,
            self::HeadwearMultiPosition,
            self::Headwear3dPuff,
            self::HeadwearFlatEmbroidery,
            self::HeadwearDyeSublimation,
            self::HeadwearSoftPvcPatch,
            self::HeadwearTeamNameNumber,
            self::HeadwearClosureOption,
            self::HeadwearCrownOption,
            self::HeadwearVisorOption,
            self::HeadwearPanelsOption,
            self::DrinkwareLocation,
            self::DrinkwareCustomGraphics,
            self::DrinkwareSampleChargeOption,
            self::LanyardAttachment,
            self::LanyardStandardAttachmentOption,
            self::LanyardAttachmentSurchargeOptions,
            self::HeadbandAngle,
            self::HeadbandPackaging => 'Optional reference image',
            self::JerseyFabricPatternOption,
            self::CompressionWearPattern,
            self::SocksPattern,
            self::HeadbandPattern => 'Optional pattern image',
            self::SocksThicknessOption => 'Optional thickness reference image',
            self::SocksYarnOption => 'Optional yarn reference image',
            self::SocksTypesOption => 'Optional socks type reference image',
            default => ProductCustomizationOptionRegistry::metadata($this, 'image_title'),
        };
    }

    public function imageDescription(): string
    {
        return match ($this) {
            self::Color,
            self::ShortsColor,
            self::PantsColor,
            self::HoodieColor,
            self::PoloColor,
            self::TshirtColor,
            self::QuarterZipColor,
            self::TankTopColor,
            self::CompressionWearColor,
            self::SocksColor,
            self::SweatshirtColor,
            self::JacketColor,
            self::BeanieColorOption => 'Optional. Add a real swatch only when the HEX color needs a visual reference.',
            self::NeckAndCollar => 'Optional. Add a clear close-up of the neckline or collar shape.',
            self::Fabric,
            self::ShortsFabric,
            self::UniformFabric,
            self::PantsFabric,
            self::HoodieFabric,
            self::PoloFabric,
            self::TshirtFabric,
            self::QuarterZipFabric,
            self::TankTopFabric,
            self::CompressionWearMaterials,
            self::SocksMaterialConstruction,
            self::SweatshirtFabric,
            self::JacketOuterFabric,
            self::JacketInnerFabric,
            self::BagFabricOption,
            self::HeadwearFabricOption,
            self::DrinkwareMaterialOption,
            self::LanyardMaterialOption,
            self::HeadbandMaterialOption => 'Optional. Add a texture or material close-up for this fabric.',
            self::SleevesAndCuffs,
            self::UniformSleeve,
            self::HoodieSleeve,
            self::PoloSleeve,
            self::TshirtSleeve,
            self::QuarterZipSleeve,
            self::SweatshirtSleeve,
            self::JacketSleeve => 'Optional. Add a close-up of the sleeve or cuff finish.',
            self::JerseyStyle => 'Optional. Add a full jersey preview for this style.',
            self::JerseyImprintOption => 'Optional. Add an imprint-method reference image when the finish needs a visual preview.',
            self::JerseyLogoOption,
            self::PantsLogoOption => 'Optional. Add a clear logo reference image when the logo choice needs a visual preview.',
            self::JerseyPipingOption,
            self::PantsPipingOption,
            self::CapPipingOption => 'Optional. Add a close-up showing the piping placement or finish.',
            self::ShortsRopeOption,
            self::PantsRopeOption => 'Optional. Add a close-up when the rope or drawstring style needs a visual preview.',
            self::ShortsElasticWaistDrawcordOption,
            self::PantsElasticWaistDrawcordOption => 'Optional. Add a close-up showing the elastic waist and drawcord construction.',
            self::ShortsImprintOption,
            self::PantsImprintOption => 'Optional. Add an imprint-method reference image when the finished decoration needs a visual preview.',
            self::ShortsImprintAreaOption,
            self::PantsImprintAreaOption => 'Optional. Add a placement reference image showing the imprint area.',
            self::HoodieDifferentNameAndNumberOption => 'Optional. Add a hoodie name/number placement reference image when the personalization choice needs a visual preview.',
            self::HoodieImprintOption => 'Optional. Add an imprint-method reference image when the hoodie finish needs a visual preview.',
            self::HoodieImprintAreaOption => 'Optional. Add a hoodie placement reference image showing the imprint area.',
            self::HoodieHoodDrawstringOption => 'Optional. Add a close-up when the hood drawstring construction, finish, or color needs a visual preview.',
            self::PoloImprintMethodOption => 'Optional. Add a legacy POLO imprint reference image when needed during migration.',
            self::PoloImprintAreaOption => 'Optional. Add a POLO placement reference image showing the imprint area.',
            self::PoloImprintOption => 'Optional. Add a polo imprint reference image when the finished decoration needs a visual preview.',
            self::PoloBackDetailOption => 'Optional. Add a polo back-detail reference image when the panel, yoke, or finish needs a visual preview.',
            self::PoloDifferentNameAndNumberOption => 'Optional. Add a POLO name/number placement reference image when the personalization choice needs a visual preview.',
            self::PoloSizeAdditionalChargesOption => 'Optional. Add a size reference only when the surcharge tier needs a visual explanation.',
            self::TshirtImprintOption => 'Optional. Add a T-shirt imprint reference image when the finished decoration needs a visual preview.',
            self::TshirtImprintAreaOption => 'Optional. Add a T-shirt placement reference image showing the imprint area.',
            self::TshirtBackDetailOption => 'Optional. Add a T-shirt back-detail reference image when the panel or finish needs a visual preview.',
            self::TshirtDifferentNameAndNumberOption => 'Optional. Add a T-shirt name/number placement reference image when the personalization choice needs a visual preview.',
            self::QuarterZipImprintOption => 'Optional. Add a quarter-zip imprint reference image when the finished decoration needs a visual preview.',
            self::TankTopImprintOption => 'Optional. Add a tank top imprint reference image when the finished decoration needs a visual preview.',
            self::TankTopImprintAreaOption => 'Optional. Add a tank top placement reference image showing the imprint area.',
            self::TankTopBackDetailOption => 'Optional. Add a tank top back-detail reference image when the panel or finish needs a visual preview.',
            self::TankTopDifferentNameAndNumberChargesOption => 'Optional. Add a tank top name/number placement reference image when the personalization choice needs a visual preview.',
            self::CompressionWearImprintOption => 'Optional. Add a compression wear imprint reference image when the finished decoration needs a visual preview.',
            self::BeanieImprintMethodOption => 'Optional. Add a Beanie imprint-method reference image when the decoration method needs a visual preview.',
            self::CompressionWearWaistType,
            self::CompressionWearLegLength,
            self::BeanieSizeOption => 'Optional. Add a size or fit reference only when the selection needs a visual explanation.',
            self::CompressionWearPocketDrawstringOption => 'Optional. Add a close-up showing the pocket and drawstring construction.',
            self::SweatshirtZipperOption => 'Optional. Add a close-up showing the sweatshirt zipper or closure.',
            self::BeanieKnittingStyleOption => 'Optional. Add a close-up when the Beanie knitting style needs a visual preview.',
            self::SocksImprintMethodOption => 'Optional. Add a socks imprint-method reference image when the decoration method needs a visual preview.',
            self::HeadbandImprintMethodOption => 'Optional. Add a headband imprint-method reference image when the decoration method needs a visual preview.',
            self::SweatshirtImprintOption => 'Optional. Add a sweatshirt imprint reference image when the finished decoration needs a visual preview.',
            self::SweatshirtImprintAreaOption => 'Optional. Add a sweatshirt placement reference image showing the imprint area.',
            self::SweatshirtDifferentNameAndNumberSurchargeOption => 'Optional. Add a sweatshirt name/number placement reference image when the personalization choice needs a visual preview.',
            self::SweatshirtDBackOption => 'Optional. Add a sweatshirt D back reference image when the back detail needs a visual preview.',
            self::TankTopNeckOption => 'Optional. Add a close-up when the tank top neck shape needs a visual preview.',
            self::TankTopPocketOption => 'Optional. Add a close-up when the tank top pocket style needs a visual preview.',
            self::JacketImprintOption => 'Optional. Add a jacket imprint reference image when the finished decoration needs a visual preview.',
            self::JacketImprintAreaOption => 'Optional. Add a jacket placement reference image showing the imprint area.',
            self::JacketDifferentNameAndNumberOption => 'Optional. Add a jacket name/number placement reference image when the personalization choice needs a visual preview.',
            self::ShortsSize,
            self::UniformSize,
            self::PantsSize,
            self::HoodieSize,
            self::QuarterZipSize,
            self::TankTopSize,
            self::SweatshirtSize,
            self::JacketSize => 'Optional. Add only when a visual size reference helps the admin or customer.',
            self::ShortsPocketOption,
            self::PantsPocketOption,
            self::UniformPocket,
            self::HoodiePocket,
            self::PoloPocketOption,
            self::TshirtPocketOption,
            self::QuarterZipPocketOption,
            self::SweatshirtPocket,
            self::JacketPocket => 'Optional. Add a close-up only when the pocket style needs a visual preview.',
            self::UniformType => 'Optional. Add only when the uniform type needs a visual preview.',
            self::UniformStyle => 'Optional. Add only when standard and reversible styles need a visual reference.',
            self::UniformNeckline,
            self::TshirtNeck,
            self::QuarterZipNeckOption,
            self::SweatshirtNeck,
            self::JacketCollarHood => 'Optional. Add a close-up of the neckline, collar, or hood shape.',
            self::PantsCalfStyle => 'Optional. Add a close-up only when the calf or length style needs a visual preview.',
            self::HoodieHoodType => 'Optional. Add a close-up only when the hood style needs a visual preview.',
            self::HoodieClosure => 'Optional. Add a close-up only when the zipper or closure style needs a visual preview.',
            self::HoodieCuff,
            self::SweatshirtCuff,
            self::JacketCuff => 'Optional. Add a close-up only when the cuff finish needs a visual preview.',
            self::PoloCollarStyle => 'Optional. Add a close-up only when the collar style needs a visual preview.',
            self::QuarterZipZipper,
            self::JacketClosure => 'Optional. Add a close-up only when the zipper or closure style needs a visual preview.',
            self::TankTopStyle => 'Optional. Add only when the tank top style needs a visual preview.',
            self::SweatshirtStyle => 'Optional. Add only when the sweatshirt style or fit needs a visual preview.',
            self::SweatshirtHem,
            self::JacketHem => 'Optional. Add only when the hem finish needs a visual preview.',
            self::JacketType => 'Optional. Add only when the jacket type needs a visual preview.',
            self::BagLogo,
            self::HeadwearWovenLogo,
            self::HeadwearHeatLogo,
            self::Headwear2dLogo,
            self::DrinkwareBrandingLogo,
            self::LanyardLogo,
            self::HeadbandLogo => 'Optional. Add a clear logo or patch reference image when helpful.',
            self::BagScreenPrint,
            self::DrinkwareLaserPrint,
            self::LanyardPrint => 'Optional. Add a print-method reference image when helpful.',
            self::BagColor,
            self::HeadwearColor,
            self::LanyardColor,
            self::LanyardBackgroundColor => 'Optional. Add a real swatch only when the HEX color needs a visual reference.',
            self::BagPrintSize,
            self::BagSizeOption,
            self::LanyardWidth,
            self::HeadbandSizeOption => 'Optional. Add only when a size reference helps the admin or customer.',
            self::BagColorMode,
            self::HeadwearMultiPosition,
            self::Headwear3dPuff,
            self::HeadwearFlatEmbroidery,
            self::HeadwearDyeSublimation,
            self::HeadwearSoftPvcPatch,
            self::HeadwearTeamNameNumber,
            self::HeadwearClosureOption,
            self::HeadwearCrownOption,
            self::HeadwearVisorOption,
            self::HeadwearPanelsOption,
            self::DrinkwareLocation,
            self::DrinkwareCustomGraphics,
            self::DrinkwareSampleChargeOption,
            self::LanyardAttachment,
            self::LanyardStandardAttachmentOption,
            self::LanyardAttachmentSurchargeOptions,
            self::HeadbandAngle,
            self::HeadbandPackaging => 'Optional. Add only when this option needs a visual preview.',
            self::JerseyFabricPatternOption,
            self::CompressionWearPattern,
            self::SocksPattern,
            self::HeadbandPattern => 'Optional. Add only when the pattern needs a visual preview.',
            self::SocksThicknessOption => 'Optional. Add a reference image when the sock thickness or cushioning level needs a visual preview.',
            self::SocksYarnOption => 'Optional. Add a yarn or fiber close-up when the material needs a visual preview.',
            self::SocksTypesOption => 'Optional. Add a full sock reference image when the sock type or height needs a visual preview.',
            default => ProductCustomizationOptionRegistry::metadata($this, 'image_description'),
        };
    }

    public function imageCta(): string
    {
        return match ($this) {
            self::Color,
            self::ShortsColor,
            self::PantsColor,
            self::HoodieColor,
            self::PoloColor,
            self::TshirtColor,
            self::QuarterZipColor,
            self::TankTopColor,
            self::CompressionWearColor,
            self::SocksColor,
            self::SweatshirtColor,
            self::JacketColor,
            self::BeanieColorOption => 'Choose swatch image',
            self::NeckAndCollar => 'Choose neck/collar image',
            self::Fabric,
            self::ShortsFabric,
            self::UniformFabric,
            self::PantsFabric,
            self::HoodieFabric,
            self::PoloFabric,
            self::TshirtFabric,
            self::QuarterZipFabric,
            self::TankTopFabric,
            self::CompressionWearMaterials,
            self::SocksMaterialConstruction,
            self::SweatshirtFabric,
            self::JacketOuterFabric,
            self::JacketInnerFabric,
            self::BagFabricOption,
            self::HeadwearFabricOption,
            self::DrinkwareMaterialOption,
            self::LanyardMaterialOption,
            self::HeadbandMaterialOption => 'Choose fabric image',
            self::SleevesAndCuffs,
            self::UniformSleeve,
            self::HoodieSleeve,
            self::PoloSleeve,
            self::TshirtSleeve,
            self::QuarterZipSleeve,
            self::SweatshirtSleeve,
            self::JacketSleeve => 'Choose sleeve image',
            self::JerseyStyle => 'Choose style image',
            self::JerseyImprintOption => 'Choose imprint image',
            self::JerseyLogoOption,
            self::PantsLogoOption => 'Choose logo image',
            self::JerseyPipingOption,
            self::PantsPipingOption,
            self::CapPipingOption => 'Choose piping image',
            self::ShortsRopeOption,
            self::PantsRopeOption => 'Choose rope image',
            self::ShortsElasticWaistDrawcordOption,
            self::PantsElasticWaistDrawcordOption => 'Choose waist/drawcord image',
            self::ShortsImprintOption,
            self::PantsImprintOption => 'Choose imprint image',
            self::HoodieDifferentNameAndNumberOption => 'Choose name/number image',
            self::HoodieImprintOption => 'Choose imprint image',
            self::HoodieImprintAreaOption => 'Choose imprint area image',
            self::HoodieHoodDrawstringOption => 'Choose drawstring image',
            self::PoloImprintMethodOption,
            self::PoloImprintOption,
            self::TshirtImprintOption,
            self::QuarterZipImprintOption,
            self::TankTopImprintOption,
            self::CompressionWearImprintOption,
            self::BeanieImprintMethodOption,
            self::SocksImprintMethodOption,
            self::HeadbandImprintMethodOption,
            self::SweatshirtImprintOption,
            self::JacketImprintOption => 'Choose imprint image',
            self::PoloBackDetailOption,
            self::TshirtBackDetailOption,
            self::TankTopBackDetailOption,
            self::SweatshirtDBackOption => 'Choose back detail image',
            self::ShortsImprintAreaOption,
            self::PantsImprintAreaOption,
            self::PoloImprintAreaOption,
            self::TshirtImprintAreaOption,
            self::TankTopImprintAreaOption,
            self::SweatshirtImprintAreaOption,
            self::JacketImprintAreaOption => 'Choose imprint area image',
            self::PoloDifferentNameAndNumberOption,
            self::TshirtDifferentNameAndNumberOption,
            self::TankTopDifferentNameAndNumberChargesOption,
            self::SweatshirtDifferentNameAndNumberSurchargeOption,
            self::JacketDifferentNameAndNumberOption => 'Choose name/number image',
            self::ShortsSize,
            self::UniformSize,
            self::PantsSize,
            self::HoodieSize,
            self::QuarterZipSize,
            self::TankTopSize,
            self::SweatshirtSize,
            self::JacketSize,
            self::CompressionWearWaistType,
            self::CompressionWearLegLength,
            self::BeanieSizeOption,
            self::PoloSizeAdditionalChargesOption => 'Choose size image',
            self::ShortsPocketOption,
            self::PantsPocketOption,
            self::UniformPocket,
            self::HoodiePocket,
            self::PoloPocketOption,
            self::TshirtPocketOption,
            self::QuarterZipPocketOption,
            self::TankTopPocketOption,
            self::SweatshirtPocket,
            self::JacketPocket,
            self::CompressionWearPocketDrawstringOption => 'Choose pocket image',
            self::UniformType => 'Choose type image',
            self::UniformStyle => 'Choose style image',
            self::UniformNeckline,
            self::TshirtNeck,
            self::QuarterZipNeckOption,
            self::TankTopNeckOption,
            self::SweatshirtNeck,
            self::JacketCollarHood => 'Choose neckline image',
            self::PantsCalfStyle => 'Choose calf style image',
            self::HoodieHoodType => 'Choose hood type image',
            self::HoodieClosure => 'Choose zipper image',
            self::HoodieCuff,
            self::SweatshirtCuff,
            self::JacketCuff => 'Choose cuff image',
            self::PoloCollarStyle => 'Choose collar image',
            self::QuarterZipZipper,
            self::JacketClosure,
            self::SweatshirtZipperOption => 'Choose closure image',
            self::TankTopStyle,
            self::SweatshirtStyle,
            self::BeanieKnittingStyleOption => 'Choose style image',
            self::SweatshirtHem,
            self::JacketHem => 'Choose hem image',
            self::JacketType => 'Choose jacket type image',
            self::BagLogo,
            self::HeadwearWovenLogo,
            self::HeadwearHeatLogo,
            self::Headwear2dLogo,
            self::DrinkwareBrandingLogo,
            self::LanyardLogo,
            self::HeadbandLogo => 'Choose logo image',
            self::BagScreenPrint,
            self::DrinkwareLaserPrint,
            self::LanyardPrint => 'Choose print image',
            self::BagColor,
            self::HeadwearColor,
            self::LanyardColor,
            self::LanyardBackgroundColor => 'Choose swatch image',
            self::BagPrintSize,
            self::BagSizeOption,
            self::LanyardWidth,
            self::HeadbandSizeOption => 'Choose size image',
            self::BagColorMode,
            self::HeadwearMultiPosition,
            self::Headwear3dPuff,
            self::HeadwearFlatEmbroidery,
            self::HeadwearDyeSublimation,
            self::HeadwearSoftPvcPatch,
            self::HeadwearTeamNameNumber,
            self::HeadwearClosureOption,
            self::HeadwearCrownOption,
            self::HeadwearVisorOption,
            self::HeadwearPanelsOption,
            self::DrinkwareLocation,
            self::DrinkwareCustomGraphics,
            self::DrinkwareSampleChargeOption,
            self::LanyardAttachment,
            self::LanyardStandardAttachmentOption,
            self::LanyardAttachmentSurchargeOptions,
            self::HeadbandAngle,
            self::HeadbandPackaging => 'Choose reference image',
            self::JerseyFabricPatternOption,
            self::CompressionWearPattern,
            self::SocksPattern,
            self::HeadbandPattern => 'Choose pattern image',
            self::SocksThicknessOption => 'Choose thickness image',
            self::SocksYarnOption => 'Choose yarn image',
            self::SocksTypesOption => 'Choose socks type image',
            default => ProductCustomizationOptionRegistry::metadata($this, 'image_cta'),
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(static fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }

    /** @return array<string, string> */
    public static function masterDataOptions(): array
    {
        return collect(self::menuGroups())
            ->flatMap(static fn (array $group): array => $group['types'])
            ->unique(static fn (self $type): string => $type->value)
            ->mapWithKeys(static fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }

    /** @return array<string, string> */
    public static function productConfigurationOptions(): array
    {
        return collect(self::masterDataOptions())
            ->union(collect(ProductCustomizationOptionRegistry::hiddenTypes())
                ->mapWithKeys(static fn (self $type): array => [$type->value => $type->label()]))
            ->all();
    }

    /** @return array<int, self> */
    public static function sizeChartTypes(): array
    {
        return [
            self::ShortsSize,
            self::UniformSize,
            self::PantsSize,
            self::HoodieSize,
            self::QuarterZipSize,
            self::TankTopSize,
            self::SweatshirtSize,
            self::JacketSize,
        ];
    }

    /** @return array<int, self> */
    public static function menuTypesForGroup(string $group): array
    {
        return collect(self::typesForGroup($group))
            ->reject(static fn (self $type): bool => $type->isSizeChartType())
            ->values()
            ->all();
    }

    public static function sizeOptionMenuNumberForGroup(string $group): string
    {
        $firstType = collect(self::typesForGroup($group))->first();
        $groupNumber = $firstType instanceof self ? $firstType->groupNumber() : '1';

        return $groupNumber.'.'.(count(self::menuTypesForGroup($group)) + 1);
    }

    /** @return array<int, self> */
    public static function typesForGroup(string $group): array
    {
        return match ($group) {
            'jersey' => [
                self::Color,
                self::NeckAndCollar,
                self::Fabric,
                self::SleevesAndCuffs,
                self::JerseyStyle,
                self::JerseyImprintOption,
                self::JerseyLogoOption,
                self::JerseyPipingOption,
                self::JerseyFabricPatternOption,
            ],
            'shorts' => [
                self::ShortsColor,
                self::ShortsFabric,
                self::ShortsSize,
                self::ShortsPocketOption,
                self::ShortsRopeOption,
                self::ShortsElasticWaistDrawcordOption,
                self::ShortsImprintOption,
                self::ShortsImprintAreaOption,
            ],
            'uniform' => [
                self::UniformType,
                self::UniformStyle,
                self::UniformFabric,
                self::UniformNeckline,
                self::UniformSleeve,
                self::UniformSize,
                self::UniformPocket,
            ],
            'pants' => [
                self::PantsColor,
                self::PantsFabric,
                self::PantsCalfStyle,
                self::PantsSize,
                self::PantsPocketOption,
                self::PantsRopeOption,
                self::PantsElasticWaistDrawcordOption,
                self::PantsImprintOption,
                self::PantsImprintAreaOption,
                self::PantsLogoOption,
                self::PantsPipingOption,
            ],
            'hoodie' => [
                self::HoodieColor,
                self::HoodieFabric,
                self::HoodieHoodType,
                self::HoodieClosure,
                self::HoodieSleeve,
                self::HoodiePocket,
                self::HoodieSize,
                self::HoodieCuff,
                self::HoodieDifferentNameAndNumberOption,
                self::HoodieImprintOption,
                self::HoodieImprintAreaOption,
                self::HoodieHoodDrawstringOption,
            ],
            'polo' => [
                self::PoloColor,
                self::PoloFabric,
                self::PoloCollarStyle,
                self::PoloSleeve,
                self::PoloPocketOption,
                self::PoloImprintAreaOption,
                self::PoloBackDetailOption,
                self::PoloImprintOption,
                self::PoloDifferentNameAndNumberOption,
                self::PoloSizeAdditionalChargesOption,
            ],
            'tshirt' => [
                self::TshirtColor,
                self::TshirtFabric,
                self::TshirtSleeve,
                self::TshirtNeck,
                self::TshirtPocketOption,
                self::TshirtImprintOption,
                self::TshirtImprintAreaOption,
                self::TshirtBackDetailOption,
                self::TshirtDifferentNameAndNumberOption,
            ],
            'quarter_zip' => [
                self::QuarterZipColor,
                self::QuarterZipFabric,
                self::QuarterZipZipper,
                self::QuarterZipSleeve,
                self::QuarterZipImprintOption,
                self::QuarterZipPocketOption,
                self::QuarterZipNeckOption,
                self::QuarterZipSize,
            ],
            'tank_top' => [
                self::TankTopColor,
                self::TankTopFabric,
                self::TankTopStyle,
                self::TankTopImprintOption,
                self::TankTopImprintAreaOption,
                self::TankTopNeckOption,
                self::TankTopBackDetailOption,
                self::TankTopPocketOption,
                self::TankTopDifferentNameAndNumberChargesOption,
                self::TankTopSize,
            ],
            'compression_wear' => [
                self::CompressionWearColor,
                self::CompressionWearMaterials,
                self::CompressionWearPattern,
                self::CompressionWearImprintOption,
                self::CompressionWearWaistType,
                self::CompressionWearLegLength,
                self::CompressionWearPocketDrawstringOption,
            ],
            'socks' => [
                self::SocksColor,
                self::SocksPattern,
                self::SocksMaterialConstruction,
                self::SocksThicknessOption,
                self::SocksYarnOption,
                self::SocksTypesOption,
                self::SocksImprintMethodOption,
            ],
            'sweatshirt' => [
                self::SweatshirtColor,
                self::SweatshirtFabric,
                self::SweatshirtNeck,
                self::SweatshirtSleeve,
                self::SweatshirtCuff,
                self::SweatshirtPocket,
                self::SweatshirtHem,
                self::SweatshirtStyle,
                self::SweatshirtImprintOption,
                self::SweatshirtImprintAreaOption,
                self::SweatshirtDifferentNameAndNumberSurchargeOption,
                self::SweatshirtDBackOption,
                self::SweatshirtZipperOption,
                self::SweatshirtSize,
            ],
            'jacket' => [
                self::JacketColor,
                self::JacketOuterFabric,
                self::JacketInnerFabric,
                self::JacketType,
                self::JacketClosure,
                self::JacketCollarHood,
                self::JacketSleeve,
                self::JacketPocket,
                self::JacketCuff,
                self::JacketHem,
                self::JacketImprintOption,
                self::JacketImprintAreaOption,
                self::JacketDifferentNameAndNumberOption,
                self::JacketSize,
            ],
            'bag' => [
                self::BagLogo,
                self::BagScreenPrint,
                self::BagColor,
                self::BagPrintSize,
                self::BagColorMode,
                self::BagSizeOption,
                self::BagFabricOption,
            ],
            'headwear' => [
                self::HeadwearWovenLogo,
                self::HeadwearDyeSublimation,
                self::HeadwearSoftPvcPatch,
                self::HeadwearColor,
                self::Headwear3dPuff,
                self::HeadwearFlatEmbroidery,
                self::HeadwearMultiPosition,
                self::HeadwearTeamNameNumber,
                self::HeadwearClosureOption,
                self::HeadwearCrownOption,
                self::HeadwearVisorOption,
                self::HeadwearPanelsOption,
                self::HeadwearFabricOption,
                self::CapPipingOption,
                self::BeanieSizeOption,
                self::BeanieKnittingStyleOption,
                self::BeanieImprintMethodOption,
                self::BeanieColorOption,
            ],
            'drinkware' => [
                self::DrinkwareLaserPrint,
                self::DrinkwareLocation,
                self::DrinkwareBrandingLogo,
                self::DrinkwareCustomGraphics,
                self::DrinkwareMaterialOption,
                self::DrinkwareSampleChargeOption,
            ],
            'lanyard' => [
                self::LanyardColor,
                self::LanyardPrint,
                self::LanyardLogo,
                self::LanyardMaterialOption,
                self::LanyardStandardAttachmentOption,
                self::LanyardAttachmentSurchargeOptions,
            ],
            'headband' => [
                self::HeadbandLogo,
                self::HeadbandPattern,
                self::HeadbandAngle,
                self::HeadbandPackaging,
                self::HeadbandSizeOption,
                self::HeadbandMaterialOption,
                self::HeadbandImprintMethodOption,
            ],
            default => ProductCustomizationOptionRegistry::typesForGroup($group),
        };
    }

    /** @return array<string, array{number: string, label: string, types: array<int, self>}> */
    public static function menuGroups(): array
    {
        $groups = [
            'jersey' => ['number' => '1.1', 'label' => 'Jersey Customization', 'types' => self::menuTypesForGroup('jersey')],
            'shorts' => ['number' => '1.2', 'label' => 'Shorts Customization', 'types' => self::menuTypesForGroup('shorts')],
            'uniform' => ['number' => '1.3', 'label' => 'Uniform Customization', 'types' => self::menuTypesForGroup('uniform')],
            'pants' => ['number' => '1.4', 'label' => 'Pants Customization', 'types' => self::menuTypesForGroup('pants')],
            'hoodie' => ['number' => '1.5', 'label' => 'Hoodie Customization', 'types' => self::menuTypesForGroup('hoodie')],
            'polo' => ['number' => '1.6', 'label' => 'Polo Customization', 'types' => self::menuTypesForGroup('polo')],
            'tshirt' => ['number' => '1.7', 'label' => 'T-Shirt Customization', 'types' => self::menuTypesForGroup('tshirt')],
            'quarter_zip' => ['number' => '1.8', 'label' => 'Quarter-Zip Customization', 'types' => self::menuTypesForGroup('quarter_zip')],
            'tank_top' => ['number' => '1.9', 'label' => 'Tank Top Customization', 'types' => self::menuTypesForGroup('tank_top')],
            'compression_wear' => ['number' => '1.10', 'label' => 'Compression Wear Customization', 'types' => self::menuTypesForGroup('compression_wear')],
            'socks' => ['number' => '1.11', 'label' => 'Socks Customization', 'types' => self::menuTypesForGroup('socks')],
            'sweatshirt' => ['number' => '1.12', 'label' => 'Sweatshirt Customization', 'types' => self::menuTypesForGroup('sweatshirt')],
            'jacket' => ['number' => '1.13', 'label' => 'Jacket Customization', 'types' => self::menuTypesForGroup('jacket')],
            'bag' => ['number' => '1.14', 'label' => 'Bag Customization', 'types' => self::menuTypesForGroup('bag')],
            'headwear' => ['number' => '1.15', 'label' => 'Headwear Customization', 'types' => self::menuTypesForGroup('headwear')],
            'drinkware' => ['number' => '1.16', 'label' => 'Drinkware Customization', 'types' => self::menuTypesForGroup('drinkware')],
            'lanyard' => ['number' => '1.17', 'label' => 'Lanyard Customization', 'types' => self::menuTypesForGroup('lanyard')],
            'headband' => ['number' => '1.18', 'label' => 'Headband Customization', 'types' => self::menuTypesForGroup('headband')],
        ];

        return array_merge($groups, ProductCustomizationOptionRegistry::menuGroups());
    }

    /** @return array<int, self> */
    public static function fabricTypes(): array
    {
        return [
            self::Fabric,
            self::ShortsFabric,
            self::UniformFabric,
            self::PantsFabric,
            self::HoodieFabric,
            self::PoloFabric,
            self::TshirtFabric,
            self::QuarterZipFabric,
            self::TankTopFabric,
            self::CompressionWearMaterials,
            self::SocksMaterialConstruction,
            self::SocksYarnOption,
            self::SweatshirtFabric,
            self::JacketOuterFabric,
            self::JacketInnerFabric,
            self::BagFabricOption,
            self::HeadwearFabricOption,
            self::DrinkwareMaterialOption,
            self::LanyardMaterialOption,
            self::HeadbandMaterialOption,
            ...ProductCustomizationOptionRegistry::descriptionTypes(),
        ];
    }

    /** @return array<int, string> */
    public static function fabricTypeValues(): array
    {
        return collect(self::fabricTypes())
            ->map(static fn (self $type): string => $type->value)
            ->all();
    }
}
