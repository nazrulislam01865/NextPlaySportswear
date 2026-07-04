<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum JerseyCustomizationType: string
{
    case NeckAndCollar = 'neck_and_collar';
    case Fabric = 'fabric';
    case Color = 'color';
    case SleevesAndCuffs = 'sleeves_and_cuffs';
    case JerseyStyle = 'jersey_style';

    case ShortsColor = 'shorts_color';
    case ShortsFabric = 'shorts_fabric';
    case ShortsSize = 'shorts_size';
    case ShortsPocketOption = 'shorts_pocket_option';

    case UniformType = 'uniform_type';
    case UniformStyle = 'uniform_style';
    case UniformNeckline = 'uniform_neckline';
    case UniformSleeve = 'uniform_sleeve';
    case UniformSize = 'uniform_size';
    case UniformPocket = 'uniform_pocket';

    case PantsColor = 'pants_color';
    case PantsFabric = 'pants_fabric';
    case PantsCalfStyle = 'pants_calf_style';
    case PantsSize = 'pants_size';

    case HoodieColor = 'hoodie_color';
    case HoodieFabric = 'hoodie_fabric';
    case HoodieHoodType = 'hoodie_hood_type';
    case HoodieClosure = 'hoodie_closure';
    case HoodieSleeve = 'hoodie_sleeve';
    case HoodiePocket = 'hoodie_pocket';
    case HoodieSize = 'hoodie_size';
    case HoodieCuff = 'hoodie_cuff';

    case PoloColor = 'polo_color';
    case PoloFabric = 'polo_fabric';
    case PoloCollarStyle = 'polo_collar_style';
    case PoloSleeve = 'polo_sleeve';
    case PoloPocketOption = 'polo_pocket_option';

    case TshirtColor = 'tshirt_color';
    case TshirtFabric = 'tshirt_fabric';
    case TshirtSleeve = 'tshirt_sleeve';
    case TshirtNeck = 'tshirt_neck';

    case QuarterZipColor = 'quarter_zip_color';
    case QuarterZipZipper = 'quarter_zip_zipper';
    case QuarterZipSleeve = 'quarter_zip_sleeve';
    case QuarterZipSize = 'quarter_zip_size';

    case TankTopColor = 'tank_top_color';
    case TankTopFabric = 'tank_top_fabric';
    case TankTopStyle = 'tank_top_style';
    case TankTopSize = 'tank_top_size';

    case CompressionWearColor = 'compression_wear_color';
    case CompressionWearPattern = 'compression_wear_pattern';

    case SocksColor = 'socks_color';
    case SocksPattern = 'socks_pattern';
    case SocksMaterialConstruction = 'socks_material_construction';

    public function label(): string
    {
        return match ($this) {
            self::NeckAndCollar => 'Neck and Collar',
            self::Fabric => 'Fabric',
            self::Color => 'Color',
            self::SleevesAndCuffs => 'Sleeves and Cuffs',
            self::JerseyStyle => 'Jersey Style',
            self::ShortsColor => 'Shorts Color',
            self::ShortsFabric => 'Shorts Fabric',
            self::ShortsSize => 'Shorts Size',
            self::ShortsPocketOption => 'Shorts Pocket Option',
            self::UniformType => 'Uniform Type',
            self::UniformStyle => 'Standard / Reversible',
            self::UniformNeckline => 'Uniform Neckline',
            self::UniformSleeve => 'Uniform Sleeve',
            self::UniformSize => 'Uniform Size',
            self::UniformPocket => 'Uniform Pocket',
            self::PantsColor => 'Pants Color',
            self::PantsFabric => 'Pants Fabric',
            self::PantsCalfStyle => 'Pants Calf Style',
            self::PantsSize => 'Pants Size',
            self::HoodieColor => 'Hoodie Color',
            self::HoodieFabric => 'Hoodie Fabric',
            self::HoodieHoodType => 'Hood Type',
            self::HoodieClosure => 'Zipper / Full-Zip / Half-Zip',
            self::HoodieSleeve => 'Hoodie Sleeve',
            self::HoodiePocket => 'Hoodie Pocket',
            self::HoodieSize => 'Hoodie Size',
            self::HoodieCuff => 'Hoodie Cuff',
            self::PoloColor => 'Polo Color',
            self::PoloFabric => 'Polo Fabric',
            self::PoloCollarStyle => 'Polo Collar Style',
            self::PoloSleeve => 'Polo Sleeve',
            self::PoloPocketOption => 'Polo Pocket Option',
            self::TshirtColor => 'T-Shirt Color',
            self::TshirtFabric => 'T-Shirt Fabric',
            self::TshirtSleeve => 'T-Shirt Sleeve',
            self::TshirtNeck => 'T-Shirt Neck',
            self::QuarterZipColor => 'Quarter-Zip Color',
            self::QuarterZipZipper => 'Quarter-Zip Zipper',
            self::QuarterZipSleeve => 'Quarter-Zip Sleeves',
            self::QuarterZipSize => 'Quarter-Zip Size',
            self::TankTopColor => 'Tank Top Color',
            self::TankTopFabric => 'Tank Top Fabric',
            self::TankTopStyle => 'Tank Top Style',
            self::TankTopSize => 'Tank Top Size',
            self::CompressionWearColor => 'Compression Wear Color',
            self::CompressionWearPattern => 'Compression Wear Pattern',
            self::SocksColor => 'Socks Color',
            self::SocksPattern => 'Socks Pattern',
            self::SocksMaterialConstruction => 'Socks Material Construction',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::NeckAndCollar,
            self::Fabric,
            self::Color,
            self::SleevesAndCuffs,
            self::JerseyStyle => 'jersey',
            self::ShortsColor,
            self::ShortsFabric,
            self::ShortsSize,
            self::ShortsPocketOption => 'shorts',
            self::UniformType,
            self::UniformStyle,
            self::UniformNeckline,
            self::UniformSleeve,
            self::UniformSize,
            self::UniformPocket => 'uniform',
            self::PantsColor,
            self::PantsFabric,
            self::PantsCalfStyle,
            self::PantsSize => 'pants',
            self::HoodieColor,
            self::HoodieFabric,
            self::HoodieHoodType,
            self::HoodieClosure,
            self::HoodieSleeve,
            self::HoodiePocket,
            self::HoodieSize,
            self::HoodieCuff => 'hoodie',
            self::PoloColor,
            self::PoloFabric,
            self::PoloCollarStyle,
            self::PoloSleeve,
            self::PoloPocketOption => 'polo',
            self::TshirtColor,
            self::TshirtFabric,
            self::TshirtSleeve,
            self::TshirtNeck => 'tshirt',
            self::QuarterZipColor,
            self::QuarterZipZipper,
            self::QuarterZipSleeve,
            self::QuarterZipSize => 'quarter_zip',
            self::TankTopColor,
            self::TankTopFabric,
            self::TankTopStyle,
            self::TankTopSize => 'tank_top',
            self::CompressionWearColor,
            self::CompressionWearPattern => 'compression_wear',
            self::SocksColor,
            self::SocksPattern,
            self::SocksMaterialConstruction => 'socks',
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
            default => 'Product Customization',
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
            default => '1',
        };
    }

    public function menuNumber(): string
    {
        $index = collect(self::typesForGroup($this->group()))
            ->search(fn (self $type): bool => $type === $this);

        return $this->groupNumber().'.'.(((int) $index) + 1);
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
        ], true);
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
            self::ShortsColor => 'Example: Royal Blue',
            self::ShortsFabric => 'Example: Lightweight Mesh',
            self::ShortsSize => 'Example: Adult Medium',
            self::ShortsPocketOption => 'Example: Side Pockets',
            self::UniformType => 'Example: Basketball Uniform',
            self::UniformStyle => 'Example: Reversible',
            self::UniformNeckline => 'Example: V-Neck',
            self::UniformSleeve => 'Example: Sleeveless',
            self::UniformSize => 'Example: Youth Large',
            self::UniformPocket => 'Example: No Pocket',
            self::PantsColor => 'Example: White',
            self::PantsFabric => 'Example: Stretch Polyester',
            self::PantsCalfStyle => 'Example: Full Length',
            self::PantsSize => 'Example: Adult Large',
            self::HoodieColor => 'Example: Charcoal Gray',
            self::HoodieFabric => 'Example: Midweight Fleece',
            self::HoodieHoodType => 'Example: Double-Layer Hood',
            self::HoodieClosure => 'Example: Full-Zip',
            self::HoodieSleeve => 'Example: Long Sleeve',
            self::HoodiePocket => 'Example: Kangaroo Pocket',
            self::HoodieSize => 'Example: Adult Large',
            self::HoodieCuff => 'Example: Ribbed Cuff',
            self::PoloColor => 'Example: Team Red',
            self::PoloFabric => 'Example: Performance Pique',
            self::PoloCollarStyle => 'Example: Classic Polo Collar',
            self::PoloSleeve => 'Example: Short Sleeve',
            self::PoloPocketOption => 'Example: No Pocket',
            self::TshirtColor => 'Example: Black',
            self::TshirtFabric => 'Example: Dry-Fit Polyester',
            self::TshirtSleeve => 'Example: Short Sleeve',
            self::TshirtNeck => 'Example: Crew Neck',
            self::QuarterZipColor => 'Example: Athletic Navy',
            self::QuarterZipZipper => 'Example: Contrast Quarter Zipper',
            self::QuarterZipSleeve => 'Example: Raglan Long Sleeve',
            self::QuarterZipSize => 'Example: Adult Large',
            self::TankTopColor => 'Example: Team Red',
            self::TankTopFabric => 'Example: Lightweight Mesh',
            self::TankTopStyle => 'Example: Racerback Tank',
            self::TankTopSize => 'Example: Adult Medium',
            self::CompressionWearColor => 'Example: Black',
            self::CompressionWearPattern => 'Example: Hex Pattern',
            self::SocksColor => 'Example: White / Royal Blue',
            self::SocksPattern => 'Example: Striped Crew Socks',
            self::SocksMaterialConstruction => 'Example: Cushioned Polyester Blend',
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
            self::ShortsColor => 'Add only the common shorts color choices that customers need while configuring shorts.',
            self::ShortsFabric => 'Add simple shorts fabric choices with short details when the material needs explanation.',
            self::ShortsSize => 'Add shorts-specific size values only when they are different from the main Size Options master data.',
            self::ShortsPocketOption => 'Add simple pocket choices such as no pocket, side pockets, or zipper pocket.',
            self::UniformType => 'Add uniform type choices that help admins separate basketball, soccer, baseball, or other uniform setups.',
            self::UniformStyle => 'Add simple uniform style choices such as standard or reversible.',
            self::UniformNeckline => 'Add uniform neckline choices that customers need to select.',
            self::UniformSleeve => 'Add uniform sleeve choices such as sleeveless, short sleeve, or long sleeve.',
            self::UniformSize => 'Add uniform-specific size values only when they are different from the main Size Options master data.',
            self::UniformPocket => 'Add simple uniform pocket choices only when the uniform design supports pockets.',
            self::PantsColor => 'Add common pants color choices used in product configuration.',
            self::PantsFabric => 'Add pants fabric choices with short details when the material needs explanation.',
            self::PantsCalfStyle => 'Add calf or length choices such as full length or open-bottom style.',
            self::PantsSize => 'Add pants-specific size values only when they are different from the main Size Options master data.',
            self::HoodieColor => 'Add common hoodie color choices with exact color values for the admin product configuration.',
            self::HoodieFabric => 'Add simple hoodie fabric choices with short details such as fleece, interlock, or lightweight performance fabric.',
            self::HoodieHoodType => 'Add only useful hood choices such as single-layer hood, double-layer hood, or no hood when needed.',
            self::HoodieClosure => 'Add closure choices such as pullover, full-zip, half-zip, or quarter-zip.',
            self::HoodieSleeve => 'Add hoodie sleeve choices only when the product needs them.',
            self::HoodiePocket => 'Add simple hoodie pocket choices such as kangaroo pocket, side pockets, or no pocket.',
            self::HoodieSize => 'Add hoodie-specific size values only when they are different from the main Size Options master data.',
            self::HoodieCuff => 'Add cuff choices such as ribbed cuff, elastic cuff, or open cuff.',
            self::PoloColor => 'Add common polo color choices with exact color values for product configuration.',
            self::PoloFabric => 'Add simple polo fabric choices with short details when the material needs explanation.',
            self::PoloCollarStyle => 'Add collar choices such as classic polo collar, rib collar, or contrast collar.',
            self::PoloSleeve => 'Add polo sleeve choices such as short sleeve or long sleeve.',
            self::PoloPocketOption => 'Add simple polo pocket choices such as no pocket or chest pocket.',
            self::TshirtColor => 'Add common T-shirt color choices with exact color values for product configuration.',
            self::TshirtFabric => 'Add simple T-shirt fabric choices with short details such as cotton, polyester, or dry-fit.',
            self::TshirtSleeve => 'Add T-shirt sleeve choices such as short sleeve, long sleeve, or sleeveless.',
            self::TshirtNeck => 'Add neck choices such as crew neck, V-neck, or round neck.',
            self::QuarterZipColor => 'Add common quarter-zip color choices with exact color values for product configuration.',
            self::QuarterZipZipper => 'Add zipper choices for quarter-zip products, such as matching zipper, contrast zipper, or hidden zipper.',
            self::QuarterZipSleeve => 'Add quarter-zip sleeve choices such as long sleeve, raglan sleeve, or contrast sleeve.',
            self::QuarterZipSize => 'Add quarter-zip-specific size values only when they are different from the main Size Options master data.',
            self::TankTopColor => 'Add common tank top color choices with exact color values for product configuration.',
            self::TankTopFabric => 'Add tank top fabric choices with short details when the material needs explanation.',
            self::TankTopStyle => 'Add tank top style choices such as racerback, classic athletic cut, or reversible tank.',
            self::TankTopSize => 'Add tank top-specific size values only when they are different from the main Size Options master data.',
            self::CompressionWearColor => 'Add common compression wear color choices with exact color values for product configuration.',
            self::CompressionWearPattern => 'Add compression wear pattern choices such as solid, camo, hex, stripe, or gradient.',
            self::SocksColor => 'Add common socks color choices with exact color values for product configuration.',
            self::SocksPattern => 'Add socks pattern choices such as solid, striped, crew stripe, or gradient.',
            self::SocksMaterialConstruction => 'Add socks material and construction choices with short details such as cushioned sole, ribbed cuff, or polyester blend.',
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
            self::SocksColor => 'Optional color swatch',
            self::NeckAndCollar => 'Optional neck/collar image',
            self::Fabric,
            self::ShortsFabric,
            self::PantsFabric,
            self::HoodieFabric,
            self::PoloFabric,
            self::TshirtFabric,
            self::TankTopFabric,
            self::SocksMaterialConstruction => 'Optional fabric texture image',
            self::SleevesAndCuffs,
            self::UniformSleeve,
            self::HoodieSleeve,
            self::PoloSleeve,
            self::TshirtSleeve,
            self::QuarterZipSleeve => 'Optional sleeve image',
            self::JerseyStyle => 'Optional jersey style image',
            self::ShortsSize,
            self::UniformSize,
            self::PantsSize,
            self::HoodieSize,
            self::QuarterZipSize,
            self::TankTopSize => 'Optional size reference image',
            self::ShortsPocketOption,
            self::UniformPocket,
            self::HoodiePocket,
            self::PoloPocketOption => 'Optional pocket reference image',
            self::UniformType => 'Optional uniform type image',
            self::UniformStyle => 'Optional style reference image',
            self::UniformNeckline,
            self::TshirtNeck => 'Optional neckline image',
            self::PantsCalfStyle => 'Optional calf style image',
            self::HoodieHoodType => 'Optional hood type image',
            self::HoodieClosure => 'Optional zipper/closure image',
            self::HoodieCuff => 'Optional cuff image',
            self::PoloCollarStyle => 'Optional collar style image',
            self::QuarterZipZipper => 'Optional zipper image',
            self::TankTopStyle => 'Optional style reference image',
            self::CompressionWearPattern,
            self::SocksPattern => 'Optional pattern image',
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
            self::SocksColor => 'Optional. Add a real swatch only when the HEX color needs a visual reference.',
            self::NeckAndCollar => 'Optional. Add a clear close-up of the neckline or collar shape.',
            self::Fabric,
            self::ShortsFabric,
            self::PantsFabric,
            self::HoodieFabric,
            self::PoloFabric,
            self::TshirtFabric,
            self::TankTopFabric,
            self::SocksMaterialConstruction => 'Optional. Add a texture or material close-up for this fabric.',
            self::SleevesAndCuffs,
            self::UniformSleeve,
            self::HoodieSleeve,
            self::PoloSleeve,
            self::TshirtSleeve,
            self::QuarterZipSleeve => 'Optional. Add a close-up of the sleeve or cuff finish.',
            self::JerseyStyle => 'Optional. Add a full jersey preview for this style.',
            self::ShortsSize,
            self::UniformSize,
            self::PantsSize,
            self::HoodieSize,
            self::QuarterZipSize,
            self::TankTopSize => 'Optional. Add only when a visual size reference helps the admin or customer.',
            self::ShortsPocketOption,
            self::UniformPocket,
            self::HoodiePocket,
            self::PoloPocketOption => 'Optional. Add a close-up only when the pocket style needs a visual preview.',
            self::UniformType => 'Optional. Add only when the uniform type needs a visual preview.',
            self::UniformStyle => 'Optional. Add only when standard and reversible styles need a visual reference.',
            self::UniformNeckline,
            self::TshirtNeck => 'Optional. Add a close-up of the neckline shape.',
            self::PantsCalfStyle => 'Optional. Add a close-up only when the calf or length style needs a visual preview.',
            self::HoodieHoodType => 'Optional. Add a close-up only when the hood style needs a visual preview.',
            self::HoodieClosure => 'Optional. Add a close-up only when the zipper or closure style needs a visual preview.',
            self::HoodieCuff => 'Optional. Add a close-up only when the cuff finish needs a visual preview.',
            self::PoloCollarStyle => 'Optional. Add a close-up only when the collar style needs a visual preview.',
            self::QuarterZipZipper => 'Optional. Add a close-up only when the zipper style needs a visual preview.',
            self::TankTopStyle => 'Optional. Add only when the tank top style needs a visual preview.',
            self::CompressionWearPattern,
            self::SocksPattern => 'Optional. Add only when the pattern needs a visual preview.',
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
            self::SocksColor => 'Choose swatch image',
            self::NeckAndCollar => 'Choose neck/collar image',
            self::Fabric,
            self::ShortsFabric,
            self::PantsFabric,
            self::HoodieFabric,
            self::PoloFabric,
            self::TshirtFabric,
            self::TankTopFabric,
            self::SocksMaterialConstruction => 'Choose fabric image',
            self::SleevesAndCuffs,
            self::UniformSleeve,
            self::HoodieSleeve,
            self::PoloSleeve,
            self::TshirtSleeve,
            self::QuarterZipSleeve => 'Choose sleeve image',
            self::JerseyStyle => 'Choose style image',
            self::ShortsSize,
            self::UniformSize,
            self::PantsSize,
            self::HoodieSize,
            self::QuarterZipSize,
            self::TankTopSize => 'Choose size image',
            self::ShortsPocketOption,
            self::UniformPocket,
            self::HoodiePocket,
            self::PoloPocketOption => 'Choose pocket image',
            self::UniformType => 'Choose type image',
            self::UniformStyle => 'Choose style image',
            self::UniformNeckline,
            self::TshirtNeck => 'Choose neckline image',
            self::PantsCalfStyle => 'Choose calf style image',
            self::HoodieHoodType => 'Choose hood type image',
            self::HoodieClosure => 'Choose zipper image',
            self::HoodieCuff => 'Choose cuff image',
            self::PoloCollarStyle => 'Choose collar image',
            self::QuarterZipZipper => 'Choose zipper image',
            self::TankTopStyle => 'Choose style image',
            self::CompressionWearPattern,
            self::SocksPattern => 'Choose pattern image',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(static fn (self $type): array => [$type->value => $type->label()])
            ->all();
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
            ],
            'shorts' => [
                self::ShortsColor,
                self::ShortsFabric,
                self::ShortsSize,
                self::ShortsPocketOption,
            ],
            'uniform' => [
                self::UniformType,
                self::UniformStyle,
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
            ],
            'polo' => [
                self::PoloColor,
                self::PoloFabric,
                self::PoloCollarStyle,
                self::PoloSleeve,
                self::PoloPocketOption,
            ],
            'tshirt' => [
                self::TshirtColor,
                self::TshirtFabric,
                self::TshirtSleeve,
                self::TshirtNeck,
            ],
            'quarter_zip' => [
                self::QuarterZipColor,
                self::QuarterZipZipper,
                self::QuarterZipSleeve,
                self::QuarterZipSize,
            ],
            'tank_top' => [
                self::TankTopColor,
                self::TankTopFabric,
                self::TankTopStyle,
                self::TankTopSize,
            ],
            'compression_wear' => [
                self::CompressionWearColor,
                self::CompressionWearPattern,
            ],
            'socks' => [
                self::SocksColor,
                self::SocksPattern,
                self::SocksMaterialConstruction,
            ],
            default => [],
        };
    }

    /** @return array<string, array{number: string, label: string, types: array<int, self>}> */
    public static function menuGroups(): array
    {
        return [
            'jersey' => ['number' => '1.1', 'label' => 'Jersey Customization', 'types' => self::typesForGroup('jersey')],
            'shorts' => ['number' => '1.2', 'label' => 'Shorts Customization', 'types' => self::typesForGroup('shorts')],
            'uniform' => ['number' => '1.3', 'label' => 'Uniform Customization', 'types' => self::typesForGroup('uniform')],
            'pants' => ['number' => '1.4', 'label' => 'Pants Customization', 'types' => self::typesForGroup('pants')],
            'hoodie' => ['number' => '1.5', 'label' => 'Hoodie Customization', 'types' => self::typesForGroup('hoodie')],
            'polo' => ['number' => '1.6', 'label' => 'Polo Customization', 'types' => self::typesForGroup('polo')],
            'tshirt' => ['number' => '1.7', 'label' => 'T-Shirt Customization', 'types' => self::typesForGroup('tshirt')],
            'quarter_zip' => ['number' => '1.8', 'label' => 'Quarter-Zip Customization', 'types' => self::typesForGroup('quarter_zip')],
            'tank_top' => ['number' => '1.9', 'label' => 'Tank Top Customization', 'types' => self::typesForGroup('tank_top')],
            'compression_wear' => ['number' => '1.10', 'label' => 'Compression Wear Customization', 'types' => self::typesForGroup('compression_wear')],
            'socks' => ['number' => '1.11', 'label' => 'Socks Customization', 'types' => self::typesForGroup('socks')],
        ];
    }

    /** @return array<int, self> */
    public static function fabricTypes(): array
    {
        return [
            self::Fabric,
            self::ShortsFabric,
            self::PantsFabric,
            self::HoodieFabric,
            self::PoloFabric,
            self::TshirtFabric,
            self::TankTopFabric,
            self::SocksMaterialConstruction,
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
