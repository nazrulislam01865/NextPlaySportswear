<?php

namespace Tests\Unit;

use App\Enums\JerseyCustomizationType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class JerseyCustomizationTypeTest extends TestCase
{
    #[DataProvider('types')]
    public function test_each_type_has_a_valid_product_option_group_code(JerseyCustomizationType $type): void
    {
        $code = $type->productCode();

        $this->assertNotSame('', $code);
        $this->assertMatchesRegularExpression('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $code);
    }


    public function test_jersey_imprint_logo_and_piping_are_separate_reusable_master_data_types(): void
    {
        $groups = JerseyCustomizationType::menuGroups();
        $jerseyTypes = collect($groups['jersey']['types'])
            ->map(static fn (JerseyCustomizationType $type): string => $type->value)
            ->all();

        $this->assertContains(JerseyCustomizationType::JerseyImprintOption->value, $jerseyTypes);
        $this->assertContains(JerseyCustomizationType::JerseyLogoOption->value, $jerseyTypes);
        $this->assertContains(JerseyCustomizationType::JerseyPipingOption->value, $jerseyTypes);
        $this->assertSame('jersey', JerseyCustomizationType::JerseyImprintOption->group());
        $this->assertSame('jersey', JerseyCustomizationType::JerseyLogoOption->group());
        $this->assertSame('jersey', JerseyCustomizationType::JerseyPipingOption->group());
        $this->assertSame('1.1.6', JerseyCustomizationType::JerseyImprintOption->menuNumber());
        $this->assertSame('1.1.7', JerseyCustomizationType::JerseyLogoOption->menuNumber());
        $this->assertSame('1.1.8', JerseyCustomizationType::JerseyPipingOption->menuNumber());
        $this->assertArrayHasKey(JerseyCustomizationType::JerseyImprintOption->value, JerseyCustomizationType::masterDataOptions());
        $this->assertArrayHasKey(JerseyCustomizationType::JerseyLogoOption->value, JerseyCustomizationType::masterDataOptions());
        $this->assertArrayHasKey(JerseyCustomizationType::JerseyPipingOption->value, JerseyCustomizationType::masterDataOptions());
        $this->assertNotSame(JerseyCustomizationType::JerseyLogoOption->value, JerseyCustomizationType::BagLogo->value);
    }

    public function test_jersey_fabric_pattern_is_a_separate_reusable_master_data_type(): void
    {
        $jerseyTypes = collect(JerseyCustomizationType::menuGroups()['jersey']['types'])
            ->map(static fn (JerseyCustomizationType $type): string => $type->value)
            ->all();

        $this->assertContains(JerseyCustomizationType::JerseyFabricPatternOption->value, $jerseyTypes);
        $this->assertSame('jersey', JerseyCustomizationType::JerseyFabricPatternOption->group());
        $this->assertSame('Fabric Pattern Option', JerseyCustomizationType::JerseyFabricPatternOption->label());
        $this->assertSame('1.1.9', JerseyCustomizationType::JerseyFabricPatternOption->menuNumber());
        $this->assertArrayHasKey(
            JerseyCustomizationType::JerseyFabricPatternOption->value,
            JerseyCustomizationType::masterDataOptions()
        );
        $this->assertNotSame(
            JerseyCustomizationType::JerseyFabricPatternOption->value,
            JerseyCustomizationType::CompressionWearPattern->value
        );
    }

    public function test_shorts_rope_elastic_waist_drawcord_imprint_and_imprint_area_are_separate_reusable_master_data_types(): void
    {
        $groups = JerseyCustomizationType::menuGroups();
        $shortsTypes = collect($groups['shorts']['types'])
            ->map(static fn (JerseyCustomizationType $type): string => $type->value)
            ->all();

        $this->assertContains(JerseyCustomizationType::ShortsRopeOption->value, $shortsTypes);
        $this->assertContains(JerseyCustomizationType::ShortsElasticWaistDrawcordOption->value, $shortsTypes);
        $this->assertContains(JerseyCustomizationType::ShortsImprintOption->value, $shortsTypes);
        $this->assertContains(JerseyCustomizationType::ShortsImprintAreaOption->value, $shortsTypes);
        $this->assertSame('shorts', JerseyCustomizationType::ShortsRopeOption->group());
        $this->assertSame('shorts', JerseyCustomizationType::ShortsElasticWaistDrawcordOption->group());
        $this->assertSame('shorts', JerseyCustomizationType::ShortsImprintOption->group());
        $this->assertSame('shorts', JerseyCustomizationType::ShortsImprintAreaOption->group());
        $this->assertSame('1.2.4', JerseyCustomizationType::ShortsRopeOption->menuNumber());
        $this->assertSame('1.2.5', JerseyCustomizationType::ShortsElasticWaistDrawcordOption->menuNumber());
        $this->assertSame('1.2.6', JerseyCustomizationType::ShortsImprintOption->menuNumber());
        $this->assertSame('1.2.7', JerseyCustomizationType::ShortsImprintAreaOption->menuNumber());
        $this->assertSame('1.2.8', JerseyCustomizationType::sizeOptionMenuNumberForGroup('shorts'));
        $this->assertArrayHasKey(JerseyCustomizationType::ShortsRopeOption->value, JerseyCustomizationType::masterDataOptions());
        $this->assertArrayHasKey(JerseyCustomizationType::ShortsElasticWaistDrawcordOption->value, JerseyCustomizationType::masterDataOptions());
        $this->assertArrayHasKey(JerseyCustomizationType::ShortsImprintOption->value, JerseyCustomizationType::masterDataOptions());
        $this->assertArrayHasKey(JerseyCustomizationType::ShortsImprintAreaOption->value, JerseyCustomizationType::masterDataOptions());
        $this->assertNotSame(JerseyCustomizationType::ShortsImprintOption->value, JerseyCustomizationType::JerseyImprintOption->value);
    }

    public function test_pants_requested_customizations_are_separate_reusable_master_data_types(): void
    {
        $pantsTypes = collect(JerseyCustomizationType::menuGroups()['pants']['types'])
            ->map(static fn (JerseyCustomizationType $type): string => $type->value)
            ->all();

        $expected = [
            [JerseyCustomizationType::PantsPocketOption, '1.4.4'],
            [JerseyCustomizationType::PantsRopeOption, '1.4.5'],
            [JerseyCustomizationType::PantsElasticWaistDrawcordOption, '1.4.6'],
            [JerseyCustomizationType::PantsImprintOption, '1.4.7'],
            [JerseyCustomizationType::PantsImprintAreaOption, '1.4.8'],
            [JerseyCustomizationType::PantsLogoOption, '1.4.9'],
            [JerseyCustomizationType::PantsPipingOption, '1.4.10'],
        ];

        foreach ($expected as [$type, $menuNumber]) {
            $this->assertContains($type->value, $pantsTypes);
            $this->assertSame('pants', $type->group());
            $this->assertSame($menuNumber, $type->menuNumber());
            $this->assertArrayHasKey($type->value, JerseyCustomizationType::masterDataOptions());
        }

        $this->assertSame('1.4.11', JerseyCustomizationType::sizeOptionMenuNumberForGroup('pants'));
        $this->assertNotSame(JerseyCustomizationType::PantsPocketOption->value, JerseyCustomizationType::ShortsPocketOption->value);
        $this->assertNotSame(JerseyCustomizationType::PantsImprintOption->value, JerseyCustomizationType::ShortsImprintOption->value);
        $this->assertNotSame(JerseyCustomizationType::PantsLogoOption->value, JerseyCustomizationType::JerseyLogoOption->value);
        $this->assertNotSame(JerseyCustomizationType::PantsPipingOption->value, JerseyCustomizationType::JerseyPipingOption->value);
    }

    public function test_hoodie_requested_customizations_are_separate_reusable_master_data_types(): void
    {
        $groups = JerseyCustomizationType::menuGroups();
        $hoodieTypes = collect($groups['hoodie']['types'])
            ->map(static fn (JerseyCustomizationType $type): string => $type->value)
            ->all();

        $expected = [
            JerseyCustomizationType::HoodieDifferentNameAndNumberOption,
            JerseyCustomizationType::HoodieImprintOption,
            JerseyCustomizationType::HoodieImprintAreaOption,
            JerseyCustomizationType::HoodieHoodDrawstringOption,
        ];

        foreach ($expected as $type) {
            $this->assertContains($type->value, $hoodieTypes);
            $this->assertSame('hoodie', $type->group());
            $this->assertArrayHasKey($type->value, JerseyCustomizationType::masterDataOptions());
        }

        $this->assertSame('1.5.8', JerseyCustomizationType::HoodieDifferentNameAndNumberOption->menuNumber());
        $this->assertSame('1.5.9', JerseyCustomizationType::HoodieImprintOption->menuNumber());
        $this->assertSame('1.5.10', JerseyCustomizationType::HoodieImprintAreaOption->menuNumber());
        $this->assertSame('1.5.11', JerseyCustomizationType::HoodieHoodDrawstringOption->menuNumber());
        $this->assertSame('1.5.12', JerseyCustomizationType::sizeOptionMenuNumberForGroup('hoodie'));
        $this->assertNotSame(JerseyCustomizationType::HoodieHoodDrawstringOption->value, JerseyCustomizationType::ShortsRopeOption->value);
        $this->assertNotSame(JerseyCustomizationType::HoodieHoodDrawstringOption->value, JerseyCustomizationType::PantsRopeOption->value);
    }

    public function test_polo_revised_and_requested_customizations_are_separate_reusable_master_data_types(): void
    {
        $groups = JerseyCustomizationType::menuGroups();
        $poloTypes = collect($groups['polo']['types'])
            ->map(static fn (JerseyCustomizationType $type): string => $type->value)
            ->all();

        $expected = [
            JerseyCustomizationType::PoloImprintAreaOption,
            JerseyCustomizationType::PoloBackDetailOption,
            JerseyCustomizationType::PoloImprintOption,
            JerseyCustomizationType::PoloDifferentNameAndNumberOption,
            JerseyCustomizationType::PoloSizeAdditionalChargesOption,
        ];

        foreach ($expected as $type) {
            $this->assertContains($type->value, $poloTypes);
            $this->assertSame('polo', $type->group());
            $this->assertArrayHasKey($type->value, JerseyCustomizationType::masterDataOptions());
            $this->assertArrayHasKey($type->value, JerseyCustomizationType::productConfigurationOptions());
        }

        $this->assertNotContains(JerseyCustomizationType::PoloImprintMethodOption->value, $poloTypes);
        $this->assertArrayNotHasKey(JerseyCustomizationType::PoloImprintMethodOption->value, JerseyCustomizationType::masterDataOptions());
        $this->assertSame('1.6.6', JerseyCustomizationType::PoloImprintAreaOption->menuNumber());
        $this->assertSame('1.6.7', JerseyCustomizationType::PoloBackDetailOption->menuNumber());
        $this->assertSame('1.6.8', JerseyCustomizationType::PoloImprintOption->menuNumber());
        $this->assertSame('1.6.9', JerseyCustomizationType::PoloDifferentNameAndNumberOption->menuNumber());
        $this->assertSame('1.6.10', JerseyCustomizationType::PoloSizeAdditionalChargesOption->menuNumber());
        $this->assertNotSame(JerseyCustomizationType::PoloDifferentNameAndNumberOption->value, JerseyCustomizationType::HoodieDifferentNameAndNumberOption->value);
    }

    public function test_tshirt_customizations_are_separate_reusable_master_data_types(): void
    {
        $groups = JerseyCustomizationType::menuGroups();
        $tshirtTypes = collect($groups['tshirt']['types'])
            ->map(static fn (JerseyCustomizationType $type): string => $type->value)
            ->all();

        $expected = [
            JerseyCustomizationType::TshirtPocketOption,
            JerseyCustomizationType::TshirtImprintOption,
            JerseyCustomizationType::TshirtImprintAreaOption,
            JerseyCustomizationType::TshirtBackDetailOption,
            JerseyCustomizationType::TshirtDifferentNameAndNumberOption,
        ];

        foreach ($expected as $type) {
            $this->assertContains($type->value, $tshirtTypes);
            $this->assertSame('tshirt', $type->group());
            $this->assertArrayHasKey($type->value, JerseyCustomizationType::masterDataOptions());
        }

        $this->assertSame('1.7.5', JerseyCustomizationType::TshirtPocketOption->menuNumber());
        $this->assertSame('1.7.6', JerseyCustomizationType::TshirtImprintOption->menuNumber());
        $this->assertSame('1.7.7', JerseyCustomizationType::TshirtImprintAreaOption->menuNumber());
        $this->assertSame('1.7.8', JerseyCustomizationType::TshirtBackDetailOption->menuNumber());
        $this->assertSame('1.7.9', JerseyCustomizationType::TshirtDifferentNameAndNumberOption->menuNumber());
        $this->assertNotSame(JerseyCustomizationType::TshirtImprintOption->value, JerseyCustomizationType::PoloImprintOption->value);
        $this->assertNotSame(JerseyCustomizationType::TshirtDifferentNameAndNumberOption->value, JerseyCustomizationType::HoodieDifferentNameAndNumberOption->value);
    }

    public function test_quarter_zip_imprint_pocket_and_neck_are_separate_reusable_master_data_types(): void
    {
        $groups = JerseyCustomizationType::menuGroups();
        $quarterZipTypes = collect($groups['quarter_zip']['types'])
            ->map(static fn (JerseyCustomizationType $type): string => $type->value)
            ->all();

        $expected = [
            JerseyCustomizationType::QuarterZipImprintOption,
            JerseyCustomizationType::QuarterZipPocketOption,
            JerseyCustomizationType::QuarterZipNeckOption,
        ];

        foreach ($expected as $type) {
            $this->assertContains($type->value, $quarterZipTypes);
            $this->assertSame('quarter_zip', $type->group());
            $this->assertArrayHasKey($type->value, JerseyCustomizationType::masterDataOptions());
        }

        $this->assertSame('1.8.5', JerseyCustomizationType::QuarterZipImprintOption->menuNumber());
        $this->assertSame('1.8.6', JerseyCustomizationType::QuarterZipPocketOption->menuNumber());
        $this->assertSame('1.8.7', JerseyCustomizationType::QuarterZipNeckOption->menuNumber());
        $this->assertSame('1.8.8', JerseyCustomizationType::sizeOptionMenuNumberForGroup('quarter_zip'));
        $this->assertNotSame(JerseyCustomizationType::QuarterZipImprintOption->value, JerseyCustomizationType::TshirtImprintOption->value);
        $this->assertNotSame(JerseyCustomizationType::QuarterZipPocketOption->value, JerseyCustomizationType::TshirtPocketOption->value);
    }

    public function test_jacket_imprint_area_and_name_number_are_separate_reusable_master_data_types(): void
    {
        $groups = JerseyCustomizationType::menuGroups();
        $jacketTypes = collect($groups['jacket']['types'])
            ->map(static fn (JerseyCustomizationType $type): string => $type->value)
            ->all();

        $expected = [
            JerseyCustomizationType::JacketImprintOption,
            JerseyCustomizationType::JacketImprintAreaOption,
            JerseyCustomizationType::JacketDifferentNameAndNumberOption,
        ];

        foreach ($expected as $type) {
            $this->assertContains($type->value, $jacketTypes);
            $this->assertSame('jacket', $type->group());
            $this->assertArrayHasKey($type->value, JerseyCustomizationType::masterDataOptions());
        }

        $this->assertSame('1.13.11', JerseyCustomizationType::JacketImprintOption->menuNumber());
        $this->assertSame('1.13.12', JerseyCustomizationType::JacketImprintAreaOption->menuNumber());
        $this->assertSame('1.13.13', JerseyCustomizationType::JacketDifferentNameAndNumberOption->menuNumber());
        $this->assertSame('1.13.14', JerseyCustomizationType::sizeOptionMenuNumberForGroup('jacket'));
        $this->assertNotSame(JerseyCustomizationType::JacketImprintOption->value, JerseyCustomizationType::QuarterZipImprintOption->value);
        $this->assertNotSame(JerseyCustomizationType::JacketDifferentNameAndNumberOption->value, JerseyCustomizationType::TshirtDifferentNameAndNumberOption->value);
    }

    public function test_tank_top_customizations_are_separate_reusable_master_data_types(): void
    {
        $groups = JerseyCustomizationType::menuGroups();
        $tankTopTypes = collect($groups['tank_top']['types'])
            ->map(static fn (JerseyCustomizationType $type): string => $type->value)
            ->all();

        $expected = [
            JerseyCustomizationType::TankTopImprintOption,
            JerseyCustomizationType::TankTopImprintAreaOption,
            JerseyCustomizationType::TankTopNeckOption,
            JerseyCustomizationType::TankTopBackDetailOption,
            JerseyCustomizationType::TankTopPocketOption,
            JerseyCustomizationType::TankTopDifferentNameAndNumberChargesOption,
        ];

        foreach ($expected as $type) {
            $this->assertContains($type->value, $tankTopTypes);
            $this->assertSame('tank_top', $type->group());
            $this->assertArrayHasKey($type->value, JerseyCustomizationType::masterDataOptions());
        }

        $this->assertSame('1.9.4', JerseyCustomizationType::TankTopImprintOption->menuNumber());
        $this->assertSame('1.9.5', JerseyCustomizationType::TankTopImprintAreaOption->menuNumber());
        $this->assertSame('1.9.6', JerseyCustomizationType::TankTopNeckOption->menuNumber());
        $this->assertSame('1.9.7', JerseyCustomizationType::TankTopBackDetailOption->menuNumber());
        $this->assertSame('1.9.8', JerseyCustomizationType::TankTopPocketOption->menuNumber());
        $this->assertSame('1.9.9', JerseyCustomizationType::TankTopDifferentNameAndNumberChargesOption->menuNumber());
        $this->assertSame('1.9.10', JerseyCustomizationType::sizeOptionMenuNumberForGroup('tank_top'));
        $this->assertNotSame(JerseyCustomizationType::TankTopImprintOption->value, JerseyCustomizationType::TshirtImprintOption->value);
        $this->assertNotSame(JerseyCustomizationType::TankTopNeckOption->value, JerseyCustomizationType::QuarterZipNeckOption->value);
        $this->assertNotSame(JerseyCustomizationType::TankTopPocketOption->value, JerseyCustomizationType::TshirtPocketOption->value);
    }

    public function test_compression_wear_imprint_is_a_separate_reusable_master_data_type(): void
    {
        $groups = JerseyCustomizationType::menuGroups();
        $compressionTypes = collect($groups['compression_wear']['types'])
            ->map(static fn (JerseyCustomizationType $type): string => $type->value)
            ->all();

        $this->assertContains(JerseyCustomizationType::CompressionWearImprintOption->value, $compressionTypes);
        $this->assertSame('compression_wear', JerseyCustomizationType::CompressionWearImprintOption->group());
        $this->assertSame('1.10.4', JerseyCustomizationType::CompressionWearImprintOption->menuNumber());
        $this->assertArrayHasKey(JerseyCustomizationType::CompressionWearImprintOption->value, JerseyCustomizationType::masterDataOptions());
        $this->assertNotSame(JerseyCustomizationType::CompressionWearImprintOption->value, JerseyCustomizationType::TankTopImprintOption->value);
    }

    public function test_requested_compression_sweatshirt_cap_and_beanie_customizations_are_separate_master_data_types(): void
    {
        $groups = JerseyCustomizationType::menuGroups();

        $expectedByGroup = [
            'compression_wear' => [
                JerseyCustomizationType::CompressionWearWaistType,
                JerseyCustomizationType::CompressionWearLegLength,
                JerseyCustomizationType::CompressionWearPocketDrawstringOption,
            ],
            'sweatshirt' => [
                JerseyCustomizationType::SweatshirtZipperOption,
            ],
            'headwear' => [
                JerseyCustomizationType::CapPipingOption,
                JerseyCustomizationType::BeanieSizeOption,
                JerseyCustomizationType::BeanieKnittingStyleOption,
                JerseyCustomizationType::BeanieImprintMethodOption,
                JerseyCustomizationType::BeanieColorOption,
            ],
        ];

        foreach ($expectedByGroup as $group => $types) {
            foreach ($types as $type) {
                $this->assertContains($type, $groups[$group]['types']);
                $this->assertSame($group, $type->group());
                $this->assertArrayHasKey($type->value, JerseyCustomizationType::masterDataOptions());
                $this->assertArrayHasKey($type->value, JerseyCustomizationType::productConfigurationOptions());
                $this->assertNotSame('', $type->placeholder());
                $this->assertNotSame('', $type->helpText());
                $this->assertNotSame('', $type->imageTitle());
                $this->assertNotSame('', $type->imageDescription());
                $this->assertNotSame('', $type->imageCta());
                $this->assertLessThanOrEqual(60, strlen($type->value));
            }
        }

        $this->assertSame('1.10.5', JerseyCustomizationType::CompressionWearWaistType->menuNumber());
        $this->assertSame('1.10.6', JerseyCustomizationType::CompressionWearLegLength->menuNumber());
        $this->assertSame('1.10.7', JerseyCustomizationType::CompressionWearPocketDrawstringOption->menuNumber());
        $this->assertSame('1.12.13', JerseyCustomizationType::SweatshirtZipperOption->menuNumber());
        $this->assertSame('1.15.14', JerseyCustomizationType::CapPipingOption->menuNumber());
        $this->assertSame('1.15.15', JerseyCustomizationType::BeanieSizeOption->menuNumber());
        $this->assertSame('1.15.16', JerseyCustomizationType::BeanieKnittingStyleOption->menuNumber());
        $this->assertSame('1.15.17', JerseyCustomizationType::BeanieImprintMethodOption->menuNumber());
        $this->assertSame('1.15.18', JerseyCustomizationType::BeanieColorOption->menuNumber());
        $this->assertTrue(JerseyCustomizationType::BeanieColorOption->usesColorValue());

        $this->assertNotSame(JerseyCustomizationType::CapPipingOption->value, JerseyCustomizationType::JerseyPipingOption->value);
        $this->assertNotSame(JerseyCustomizationType::CapPipingOption->value, JerseyCustomizationType::PantsPipingOption->value);
        $this->assertNotSame(JerseyCustomizationType::BeanieColorOption->value, JerseyCustomizationType::HeadwearColor->value);
        $this->assertNotSame(JerseyCustomizationType::BeanieSizeOption->value, JerseyCustomizationType::HeadwearFabricOption->value);
        $this->assertNotSame(JerseyCustomizationType::SweatshirtZipperOption->value, JerseyCustomizationType::QuarterZipZipper->value);
    }

    public function test_socks_customizations_are_separate_reusable_master_data_types(): void
    {
        $groups = JerseyCustomizationType::menuGroups();
        $socksTypes = collect($groups['socks']['types'])
            ->map(static fn (JerseyCustomizationType $type): string => $type->value)
            ->all();

        $expected = [
            JerseyCustomizationType::SocksThicknessOption,
            JerseyCustomizationType::SocksYarnOption,
            JerseyCustomizationType::SocksTypesOption,
            JerseyCustomizationType::SocksImprintMethodOption,
        ];

        foreach ($expected as $type) {
            $this->assertContains($type->value, $socksTypes);
            $this->assertSame('socks', $type->group());
            $this->assertArrayHasKey($type->value, JerseyCustomizationType::masterDataOptions());
        }

        $this->assertSame('1.11.4', JerseyCustomizationType::SocksThicknessOption->menuNumber());
        $this->assertSame('1.11.5', JerseyCustomizationType::SocksYarnOption->menuNumber());
        $this->assertSame('1.11.6', JerseyCustomizationType::SocksTypesOption->menuNumber());
        $this->assertSame('1.11.7', JerseyCustomizationType::SocksImprintMethodOption->menuNumber());
        $this->assertNotSame(JerseyCustomizationType::SocksImprintMethodOption->value, JerseyCustomizationType::PoloImprintAreaOption->value);
    }

    public function test_sweatshirt_customizations_are_separate_reusable_master_data_types(): void
    {
        $groups = JerseyCustomizationType::menuGroups();
        $sweatshirtTypes = collect($groups['sweatshirt']['types'])
            ->map(static fn (JerseyCustomizationType $type): string => $type->value)
            ->all();

        $expected = [
            JerseyCustomizationType::SweatshirtImprintOption,
            JerseyCustomizationType::SweatshirtImprintAreaOption,
            JerseyCustomizationType::SweatshirtDifferentNameAndNumberSurchargeOption,
            JerseyCustomizationType::SweatshirtDBackOption,
        ];

        foreach ($expected as $type) {
            $this->assertContains($type->value, $sweatshirtTypes);
            $this->assertSame('sweatshirt', $type->group());
            $this->assertArrayHasKey($type->value, JerseyCustomizationType::masterDataOptions());
        }

        $this->assertSame('1.12.9', JerseyCustomizationType::SweatshirtImprintOption->menuNumber());
        $this->assertSame('1.12.10', JerseyCustomizationType::SweatshirtImprintAreaOption->menuNumber());
        $this->assertSame('1.12.11', JerseyCustomizationType::SweatshirtDifferentNameAndNumberSurchargeOption->menuNumber());
        $this->assertSame('1.12.12', JerseyCustomizationType::SweatshirtDBackOption->menuNumber());
        $this->assertSame('1.12.14', JerseyCustomizationType::sizeOptionMenuNumberForGroup('sweatshirt'));
        $this->assertNotSame(JerseyCustomizationType::SweatshirtImprintOption->value, JerseyCustomizationType::HoodieImprintOption->value);
        $this->assertNotSame(JerseyCustomizationType::SweatshirtImprintAreaOption->value, JerseyCustomizationType::JacketImprintAreaOption->value);
    }

    public function test_bag_and_headwear_options_use_reusable_product_scoped_master_data_types(): void
    {
        $groups = JerseyCustomizationType::menuGroups();

        $bagTypes = [
            JerseyCustomizationType::BagSizeOption,
            JerseyCustomizationType::BagFabricOption,
        ];
        foreach ($bagTypes as $type) {
            $this->assertContains($type, $groups['bag']['types']);
            $this->assertSame('bag', $type->group());
            $this->assertArrayHasKey($type->value, JerseyCustomizationType::masterDataOptions());
        }
        $this->assertSame('1.14.6', JerseyCustomizationType::BagSizeOption->menuNumber());
        $this->assertSame('1.14.7', JerseyCustomizationType::BagFabricOption->menuNumber());

        $headwearTypes = [
            JerseyCustomizationType::HeadwearClosureOption,
            JerseyCustomizationType::HeadwearCrownOption,
            JerseyCustomizationType::HeadwearVisorOption,
            JerseyCustomizationType::HeadwearPanelsOption,
            JerseyCustomizationType::HeadwearFabricOption,
        ];
        foreach ($headwearTypes as $type) {
            $this->assertContains($type, $groups['headwear']['types']);
            $this->assertSame('headwear', $type->group());
            $this->assertArrayHasKey($type->value, JerseyCustomizationType::masterDataOptions());
        }
        $this->assertSame('1.15.9', JerseyCustomizationType::HeadwearClosureOption->menuNumber());
        $this->assertSame('1.15.10', JerseyCustomizationType::HeadwearCrownOption->menuNumber());
        $this->assertSame('1.15.11', JerseyCustomizationType::HeadwearVisorOption->menuNumber());
        $this->assertSame('1.15.12', JerseyCustomizationType::HeadwearPanelsOption->menuNumber());
        $this->assertSame('1.15.13', JerseyCustomizationType::HeadwearFabricOption->menuNumber());

        $this->assertContains(JerseyCustomizationType::BagFabricOption, JerseyCustomizationType::fabricTypes());
        $this->assertContains(JerseyCustomizationType::HeadwearFabricOption, JerseyCustomizationType::fabricTypes());
        $this->assertNotSame(JerseyCustomizationType::BagFabricOption->value, JerseyCustomizationType::HeadwearFabricOption->value);
    }

    public function test_drinkware_lanyard_and_headband_options_use_reusable_product_scoped_master_data_types(): void
    {
        $groups = JerseyCustomizationType::menuGroups();

        $expectedByGroup = [
            'drinkware' => [
                [JerseyCustomizationType::DrinkwareMaterialOption, '1.16.5'],
                [JerseyCustomizationType::DrinkwareSampleChargeOption, '1.16.6'],
            ],
            'lanyard' => [
                [JerseyCustomizationType::LanyardMaterialOption, '1.17.4'],
                [JerseyCustomizationType::LanyardStandardAttachmentOption, '1.17.5'],
                [JerseyCustomizationType::LanyardAttachmentSurchargeOptions, '1.17.6'],
            ],
            'headband' => [
                [JerseyCustomizationType::HeadbandSizeOption, '1.18.5'],
                [JerseyCustomizationType::HeadbandMaterialOption, '1.18.6'],
                [JerseyCustomizationType::HeadbandImprintMethodOption, '1.18.7'],
            ],
        ];

        foreach ($expectedByGroup as $group => $types) {
            foreach ($types as [$type, $menuNumber]) {
                $this->assertContains($type, $groups[$group]['types']);
                $this->assertSame($group, $type->group());
                $this->assertSame($menuNumber, $type->menuNumber());
                $this->assertArrayHasKey($type->value, JerseyCustomizationType::masterDataOptions());
            }
        }

        $this->assertContains(JerseyCustomizationType::DrinkwareMaterialOption, JerseyCustomizationType::fabricTypes());
        $this->assertContains(JerseyCustomizationType::LanyardMaterialOption, JerseyCustomizationType::fabricTypes());
        $this->assertContains(JerseyCustomizationType::HeadbandMaterialOption, JerseyCustomizationType::fabricTypes());
    }

    public function test_extension_product_options_use_modular_registry_metadata(): void
    {
        $groups = JerseyCustomizationType::menuGroups();

        $expectedByGroup = [
            'towel' => [
                [JerseyCustomizationType::TowelSizeOption, '1.20.1'],
                [JerseyCustomizationType::TowelMaterialOption, '1.20.2'],
                [JerseyCustomizationType::TowelFrontFabricOption, '1.20.3'],
                [JerseyCustomizationType::TowelBackFabricOption, '1.20.4'],
                [JerseyCustomizationType::TowelImprintSizeOption, '1.20.5'],
                [JerseyCustomizationType::TowelAvailableBackingColorOption, '1.20.6'],
            ],
            'silicone_wristband' => [
                [JerseyCustomizationType::SiliconeWristbandProductSizeOption, '1.21.1'],
                [JerseyCustomizationType::SiliconeWristbandMaterialOption, '1.21.2'],
                [JerseyCustomizationType::SiliconeWristbandImprintMethodOption, '1.21.3'],
                [JerseyCustomizationType::SiliconeWristbandCustomizedOptions, '1.21.4'],
            ],
            'armsleeve' => [
                [JerseyCustomizationType::ArmsleeveSizeOption, '1.22.1'],
                [JerseyCustomizationType::ArmsleeveFabricOption, '1.22.2'],
                [JerseyCustomizationType::ArmsleeveImprintMethodOption, '1.22.3'],
            ],
            'baseball_belt' => [
                [JerseyCustomizationType::BaseballBeltSizeOption, '1.23.1'],
                [JerseyCustomizationType::BaseballBeltMaterialOption, '1.23.2'],
                [JerseyCustomizationType::BaseballBeltImprintOption, '1.23.3'],
                [JerseyCustomizationType::BaseballBeltImprintAreaOption, '1.23.4'],
                [JerseyCustomizationType::BaseballBeltImprintSizeOption, '1.23.5'],
                [JerseyCustomizationType::BaseballBeltColorOption, '1.23.6'],
            ],
            'fabric_wristband' => [
                [JerseyCustomizationType::FabricWristbandSizeOption, '1.25.1'],
                [JerseyCustomizationType::FabricWristbandMaterialOption, '1.25.2'],
                [JerseyCustomizationType::FabricWristbandStandardAttachmentOption, '1.25.3'],
                [JerseyCustomizationType::FabricWristbandImprintMethodOption, '1.25.4'],
                [JerseyCustomizationType::FabricWristbandLockingClosuresOption, '1.25.5'],
            ],
            'knitted_gloves' => [
                [JerseyCustomizationType::KnittedGlovesSizeOption, '1.26.1'],
                [JerseyCustomizationType::KnittedGlovesLogoOption, '1.26.2'],
                [JerseyCustomizationType::KnittedGlovesMaterialOption, '1.26.3'],
                [JerseyCustomizationType::KnittedGlovesColorOption, '1.26.4'],
                [JerseyCustomizationType::KnittedGlovesTouchScreenFunctionOption, '1.26.5'],
                [JerseyCustomizationType::KnittedGlovesInnerLiningOption, '1.26.6'],
                [JerseyCustomizationType::KnittedGlovesCuffTypeOption, '1.26.7'],
                [JerseyCustomizationType::KnittedGlovesFabricFeatureOption, '1.26.8'],
            ],
            'bandana' => [
                [JerseyCustomizationType::BandanaSizeOption, '1.27.1'],
                [JerseyCustomizationType::BandanaFabricOption, '1.27.2'],
                [JerseyCustomizationType::BandanaMaskLayersOption, '1.27.3'],
                [JerseyCustomizationType::BandanaImprintMethodOption, '1.27.4'],
            ],
            'training_vest' => [
                [JerseyCustomizationType::TrainingVestImprintOption, '1.19.5'],
                [JerseyCustomizationType::TrainingVestLogoOption, '1.19.6'],
            ],
            'premium_scarf' => [
                [JerseyCustomizationType::PremiumScarfSizeOption, '1.28.1'],
                [JerseyCustomizationType::PremiumScarfMaterialOption, '1.28.2'],
                [JerseyCustomizationType::PremiumScarfCraftOption, '1.28.3'],
                [JerseyCustomizationType::PremiumScarfLayerOption, '1.28.4'],
                [JerseyCustomizationType::PremiumScarfImprintSizeOption, '1.28.5'],
                [JerseyCustomizationType::PremiumScarfYarnColorOption, '1.28.6'],
            ],
            'wristbands' => [
                [JerseyCustomizationType::WristbandsSizeOption, '1.29.1'],
                [JerseyCustomizationType::WristbandsMaterialOption, '1.29.2'],
                [JerseyCustomizationType::WristbandsImprintMethodOption, '1.29.3'],
            ],
        ];

        foreach ($expectedByGroup as $group => $types) {
            $this->assertArrayHasKey($group, $groups);

            foreach ($types as [$type, $menuNumber]) {
                $this->assertContains($type, $groups[$group]['types']);
                $this->assertSame($group, $type->group());
                $this->assertSame($menuNumber, $type->menuNumber());
                $this->assertArrayHasKey($type->value, JerseyCustomizationType::masterDataOptions());
                $this->assertNotSame('', $type->placeholder());
                $this->assertNotSame('', $type->helpText());
                $this->assertNotSame('', $type->imageTitle());
                $this->assertNotSame('', $type->imageDescription());
                $this->assertNotSame('', $type->imageCta());
            }
        }


        foreach ([
            [JerseyCustomizationType::TrainingVestColorOption, '1.19.1'],
            [JerseyCustomizationType::TrainingVestFabricOption, '1.19.2'],
            [JerseyCustomizationType::TrainingVestSizeOption, '1.19.3'],
            [JerseyCustomizationType::TrainingVestVestTypeOption, '1.19.4'],
        ] as [$type, $menuNumber]) {
            $this->assertSame($menuNumber, $type->menuNumber());
            $this->assertArrayNotHasKey($type->value, JerseyCustomizationType::masterDataOptions());
            $this->assertArrayHasKey($type->value, JerseyCustomizationType::productConfigurationOptions());
        }

        $this->assertTrue(JerseyCustomizationType::BaseballBeltColorOption->usesColorValue());
        $this->assertTrue(JerseyCustomizationType::TowelAvailableBackingColorOption->usesColorValue());
        $this->assertContains(JerseyCustomizationType::SiliconeWristbandMaterialOption, JerseyCustomizationType::fabricTypes());
        $this->assertContains(JerseyCustomizationType::BaseballBeltMaterialOption, JerseyCustomizationType::fabricTypes());
        $this->assertContains(JerseyCustomizationType::TowelMaterialOption, JerseyCustomizationType::fabricTypes());
        $this->assertContains(JerseyCustomizationType::TowelFrontFabricOption, JerseyCustomizationType::fabricTypes());
        $this->assertContains(JerseyCustomizationType::TowelBackFabricOption, JerseyCustomizationType::fabricTypes());
        $this->assertContains(JerseyCustomizationType::ArmsleeveFabricOption, JerseyCustomizationType::fabricTypes());
        $this->assertContains(JerseyCustomizationType::FabricWristbandMaterialOption, JerseyCustomizationType::fabricTypes());
        $this->assertContains(JerseyCustomizationType::WristbandsMaterialOption, JerseyCustomizationType::fabricTypes());
        $this->assertTrue(JerseyCustomizationType::KnittedGlovesColorOption->usesColorValue());
        $this->assertContains(JerseyCustomizationType::KnittedGlovesMaterialOption, JerseyCustomizationType::fabricTypes());
        $this->assertContains(JerseyCustomizationType::KnittedGlovesInnerLiningOption, JerseyCustomizationType::fabricTypes());
        $this->assertContains(JerseyCustomizationType::KnittedGlovesFabricFeatureOption, JerseyCustomizationType::fabricTypes());
        $this->assertContains(JerseyCustomizationType::BandanaFabricOption, JerseyCustomizationType::fabricTypes());
        $this->assertContains(JerseyCustomizationType::PremiumScarfMaterialOption, JerseyCustomizationType::fabricTypes());
        $this->assertTrue(JerseyCustomizationType::PremiumScarfYarnColorOption->usesColorValue());
    }

    public function test_sweatshirt_and_jacket_have_independent_master_data_groups(): void
    {
        $groups = JerseyCustomizationType::menuGroups();

        $this->assertArrayHasKey('sweatshirt', $groups);
        $this->assertArrayHasKey('jacket', $groups);
        $this->assertSame('1.12', $groups['sweatshirt']['number']);
        $this->assertSame('1.13', $groups['jacket']['number']);
        $this->assertSame('sweatshirt', JerseyCustomizationType::SweatshirtFabric->group());
        $this->assertSame('jacket', JerseyCustomizationType::JacketOuterFabric->group());
        $this->assertNotSame(
            JerseyCustomizationType::SweatshirtColor->value,
            JerseyCustomizationType::JacketColor->value
        );
    }

    public function test_sweatshirt_and_jacket_sizes_use_separate_size_option_contexts(): void
    {
        $this->assertTrue(JerseyCustomizationType::SweatshirtSize->isSizeChartType());
        $this->assertTrue(JerseyCustomizationType::JacketSize->isSizeChartType());
        $this->assertArrayNotHasKey(
            JerseyCustomizationType::SweatshirtSize->value,
            JerseyCustomizationType::masterDataOptions()
        );
        $this->assertArrayNotHasKey(
            JerseyCustomizationType::JacketSize->value,
            JerseyCustomizationType::masterDataOptions()
        );
        $this->assertSame('1.12.14', JerseyCustomizationType::sizeOptionMenuNumberForGroup('sweatshirt'));
        $this->assertSame('1.13.14', JerseyCustomizationType::sizeOptionMenuNumberForGroup('jacket'));
    }

    /** @return array<string, array{JerseyCustomizationType}> */
    public static function types(): array
    {
        return collect(JerseyCustomizationType::cases())
            ->mapWithKeys(static fn (JerseyCustomizationType $type): array => [$type->value => [$type]])
            ->all();
    }
}
