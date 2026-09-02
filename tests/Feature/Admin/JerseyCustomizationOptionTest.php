<?php

namespace Tests\Feature\Admin;

use App\Enums\JerseyCustomizationType;
use App\Models\JerseyCustomizationOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JerseyCustomizationOptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_color_option_with_description_and_multiple_linked_images(): void
    {
        $admin = User::factory()->create([
            'role' => 'catalog_manager',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->post(
            route('admin.jersey-customization-options.store'),
            [
                'name' => 'Royal Blue',
                'type' => JerseyCustomizationType::Color->value,
                'color_hex' => '#2563EB',
                'description' => 'A bright team blue suitable for home and away jersey accents.',
                'images' => [
                    [
                        'name' => 'Front view',
                        'image_url' => 'https://example.com/royal-blue-front.jpg',
                        'is_primary' => '1',
                        'sort_order' => 0,
                    ],
                    [
                        'name' => 'Back view',
                        'image_url' => 'https://example.com/royal-blue-back.jpg',
                        'is_primary' => '0',
                        'sort_order' => 1,
                    ],
                ],
            ]
        );

        $option = JerseyCustomizationOption::query()->firstOrFail();

        $response->assertRedirect(route('admin.jersey-customization-options.edit', $option));
        $this->assertSame(JerseyCustomizationType::Color, $option->type);
        $this->assertSame('#2563EB', $option->color_hex);
        $this->assertSame(
            'A bright team blue suitable for home and away jersey accents.',
            $option->description
        );
        $this->assertTrue($option->is_active);
        $this->assertSame(0, $option->sort_order);
        $this->assertCount(2, $option->images);
        $this->assertSame('Front view', $option->primaryImage()->firstOrFail()->name);
    }


    public function test_jersey_imprint_logo_and_piping_values_are_stored_separately(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        foreach ([
            JerseyCustomizationType::JerseyImprintOption,
            JerseyCustomizationType::JerseyLogoOption,
            JerseyCustomizationType::JerseyPipingOption,
        ] as $type) {
            $this->actingAs($admin, 'admin')->post(
                route('admin.jersey-customization-options.store'),
                [
                    'name' => 'Standard',
                    'type' => $type->value,
                ]
            )->assertSessionDoesntHaveErrors();

            $this->assertDatabaseHas('jersey_customization_options', [
                'type' => $type->value,
                'slug' => 'standard',
            ]);
        }

        $this->assertSame(3, JerseyCustomizationOption::query()->where('slug', 'standard')->count());
    }

    public function test_shorts_rope_elastic_waist_drawcord_and_imprint_values_are_stored_separately(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        foreach ([
            JerseyCustomizationType::ShortsRopeOption,
            JerseyCustomizationType::ShortsElasticWaistDrawcordOption,
            JerseyCustomizationType::ShortsImprintOption,
        ] as $type) {
            $this->actingAs($admin, 'admin')->post(
                route('admin.jersey-customization-options.store'),
                [
                    'name' => 'Standard',
                    'type' => $type->value,
                ]
            )->assertSessionDoesntHaveErrors();

            $this->assertDatabaseHas('jersey_customization_options', [
                'type' => $type->value,
                'slug' => 'standard',
            ]);
        }

        $this->assertSame(3, JerseyCustomizationOption::query()
            ->whereIn('type', [
                JerseyCustomizationType::ShortsRopeOption->value,
                JerseyCustomizationType::ShortsElasticWaistDrawcordOption->value,
                JerseyCustomizationType::ShortsImprintOption->value,
            ])
            ->where('slug', 'standard')
            ->count());
    }

    public function test_requested_jersey_shorts_and_pants_values_are_stored_by_their_own_master_data_type(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $types = [
            JerseyCustomizationType::JerseyFabricPatternOption,
            JerseyCustomizationType::ShortsImprintAreaOption,
            JerseyCustomizationType::PantsPocketOption,
            JerseyCustomizationType::PantsRopeOption,
            JerseyCustomizationType::PantsElasticWaistDrawcordOption,
            JerseyCustomizationType::PantsImprintOption,
            JerseyCustomizationType::PantsImprintAreaOption,
            JerseyCustomizationType::PantsLogoOption,
            JerseyCustomizationType::PantsPipingOption,
        ];

        foreach ($types as $type) {
            $this->actingAs($admin, 'admin')->post(
                route('admin.jersey-customization-options.store'),
                [
                    'name' => 'Standard',
                    'type' => $type->value,
                ]
            )->assertSessionDoesntHaveErrors();

            $this->assertDatabaseHas('jersey_customization_options', [
                'type' => $type->value,
                'slug' => 'standard',
            ]);
        }

        $this->assertSame(count($types), JerseyCustomizationOption::query()
            ->whereIn('type', array_map(static fn (JerseyCustomizationType $type): string => $type->value, $types))
            ->where('slug', 'standard')
            ->count());
    }

    public function test_hoodie_requested_customization_values_are_stored_separately(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        foreach ([
            JerseyCustomizationType::HoodieDifferentNameAndNumberOption,
            JerseyCustomizationType::HoodieImprintOption,
            JerseyCustomizationType::HoodieImprintAreaOption,
            JerseyCustomizationType::HoodieHoodDrawstringOption,
        ] as $type) {
            $this->actingAs($admin, 'admin')->post(
                route('admin.jersey-customization-options.store'),
                [
                    'name' => 'Standard',
                    'type' => $type->value,
                ]
            )->assertSessionDoesntHaveErrors();

            $this->assertDatabaseHas('jersey_customization_options', [
                'type' => $type->value,
                'slug' => 'standard',
            ]);
        }

        $this->assertSame(4, JerseyCustomizationOption::query()
            ->whereIn('type', [
                JerseyCustomizationType::HoodieDifferentNameAndNumberOption->value,
                JerseyCustomizationType::HoodieImprintOption->value,
                JerseyCustomizationType::HoodieImprintAreaOption->value,
                JerseyCustomizationType::HoodieHoodDrawstringOption->value,
            ])
            ->where('slug', 'standard')
            ->count());
    }

    public function test_polo_customization_values_are_stored_separately(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $types = [
            JerseyCustomizationType::PoloImprintAreaOption,
            JerseyCustomizationType::PoloBackDetailOption,
            JerseyCustomizationType::PoloImprintOption,
            JerseyCustomizationType::PoloDifferentNameAndNumberOption,
            JerseyCustomizationType::PoloSizeAdditionalChargesOption,
        ];

        foreach ($types as $type) {
            $this->actingAs($admin, 'admin')->post(
                route('admin.jersey-customization-options.store'),
                ['name' => 'Standard', 'type' => $type->value]
            )->assertSessionDoesntHaveErrors();

            $this->assertDatabaseHas('jersey_customization_options', [
                'type' => $type->value,
                'slug' => 'standard',
            ]);
        }

        $this->assertSame(5, JerseyCustomizationOption::query()
            ->whereIn('type', array_map(static fn (JerseyCustomizationType $type): string => $type->value, $types))
            ->where('slug', 'standard')
            ->count());
    }

    public function test_tshirt_customization_values_are_stored_separately(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $types = [
            JerseyCustomizationType::TshirtPocketOption,
            JerseyCustomizationType::TshirtImprintOption,
            JerseyCustomizationType::TshirtImprintAreaOption,
            JerseyCustomizationType::TshirtBackDetailOption,
            JerseyCustomizationType::TshirtDifferentNameAndNumberOption,
        ];

        foreach ($types as $type) {
            $this->actingAs($admin, 'admin')->post(
                route('admin.jersey-customization-options.store'),
                ['name' => 'Standard', 'type' => $type->value]
            )->assertSessionDoesntHaveErrors();

            $this->assertDatabaseHas('jersey_customization_options', [
                'type' => $type->value,
                'slug' => 'standard',
            ]);
        }

        $this->assertSame(5, JerseyCustomizationOption::query()
            ->whereIn('type', array_map(static fn (JerseyCustomizationType $type): string => $type->value, $types))
            ->where('slug', 'standard')
            ->count());
    }

    public function test_quarter_zip_customization_values_are_stored_separately(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $types = [
            JerseyCustomizationType::QuarterZipImprintOption,
            JerseyCustomizationType::QuarterZipPocketOption,
            JerseyCustomizationType::QuarterZipNeckOption,
        ];

        foreach ($types as $type) {
            $this->actingAs($admin, 'admin')->post(
                route('admin.jersey-customization-options.store'),
                ['name' => 'Standard', 'type' => $type->value]
            )->assertSessionDoesntHaveErrors();

            $this->assertDatabaseHas('jersey_customization_options', [
                'type' => $type->value,
                'slug' => 'standard',
            ]);
        }

        $this->assertSame(3, JerseyCustomizationOption::query()
            ->whereIn('type', array_map(static fn (JerseyCustomizationType $type): string => $type->value, $types))
            ->where('slug', 'standard')
            ->count());
    }

    public function test_jacket_customization_values_are_stored_separately(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $types = [
            JerseyCustomizationType::JacketImprintOption,
            JerseyCustomizationType::JacketImprintAreaOption,
            JerseyCustomizationType::JacketDifferentNameAndNumberOption,
        ];

        foreach ($types as $type) {
            $this->actingAs($admin, 'admin')->post(
                route('admin.jersey-customization-options.store'),
                ['name' => 'Standard', 'type' => $type->value]
            )->assertSessionDoesntHaveErrors();

            $this->assertDatabaseHas('jersey_customization_options', [
                'type' => $type->value,
                'slug' => 'standard',
            ]);
        }

        $this->assertSame(3, JerseyCustomizationOption::query()
            ->whereIn('type', array_map(static fn (JerseyCustomizationType $type): string => $type->value, $types))
            ->where('slug', 'standard')
            ->count());
    }

    public function test_tank_top_and_compression_wear_customization_values_are_stored_separately(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $types = [
            JerseyCustomizationType::TankTopImprintOption,
            JerseyCustomizationType::TankTopImprintAreaOption,
            JerseyCustomizationType::TankTopNeckOption,
            JerseyCustomizationType::TankTopBackDetailOption,
            JerseyCustomizationType::TankTopPocketOption,
            JerseyCustomizationType::TankTopDifferentNameAndNumberChargesOption,
            JerseyCustomizationType::CompressionWearImprintOption,
        ];

        foreach ($types as $type) {
            $this->actingAs($admin, 'admin')->post(
                route('admin.jersey-customization-options.store'),
                ['name' => 'Standard', 'type' => $type->value]
            )->assertSessionDoesntHaveErrors();

            $this->assertDatabaseHas('jersey_customization_options', [
                'type' => $type->value,
                'slug' => 'standard',
            ]);
        }

        $this->assertSame(7, JerseyCustomizationOption::query()
            ->whereIn('type', array_map(static fn (JerseyCustomizationType $type): string => $type->value, $types))
            ->where('slug', 'standard')
            ->count());
    }

    public function test_socks_and_sweatshirt_customization_values_are_stored_separately(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $types = [
            JerseyCustomizationType::SocksThicknessOption,
            JerseyCustomizationType::SocksYarnOption,
            JerseyCustomizationType::SocksTypesOption,
            JerseyCustomizationType::SocksImprintMethodOption,
            JerseyCustomizationType::SweatshirtImprintOption,
            JerseyCustomizationType::SweatshirtImprintAreaOption,
            JerseyCustomizationType::SweatshirtDifferentNameAndNumberSurchargeOption,
            JerseyCustomizationType::SweatshirtDBackOption,
        ];

        foreach ($types as $type) {
            $this->actingAs($admin, 'admin')->post(
                route('admin.jersey-customization-options.store'),
                ['name' => 'Standard', 'type' => $type->value]
            )->assertSessionDoesntHaveErrors();

            $this->assertDatabaseHas('jersey_customization_options', [
                'type' => $type->value,
                'slug' => 'standard',
            ]);
        }

        $this->assertSame(8, JerseyCustomizationOption::query()
            ->whereIn('type', array_map(static fn (JerseyCustomizationType $type): string => $type->value, $types))
            ->where('slug', 'standard')
            ->count());
    }

    public function test_bag_and_headwear_customization_values_are_stored_separately(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $types = [
            JerseyCustomizationType::BagSizeOption,
            JerseyCustomizationType::BagFabricOption,
            JerseyCustomizationType::HeadwearClosureOption,
            JerseyCustomizationType::HeadwearCrownOption,
            JerseyCustomizationType::HeadwearVisorOption,
            JerseyCustomizationType::HeadwearPanelsOption,
            JerseyCustomizationType::HeadwearFabricOption,
        ];

        foreach ($types as $type) {
            $payload = ['name' => 'Standard', 'type' => $type->value];
            if ($type->usesDescription()) {
                $payload['description'] = 'Reusable material description.';
            }

            $this->actingAs($admin, 'admin')->post(
                route('admin.jersey-customization-options.store'),
                $payload
            )->assertSessionDoesntHaveErrors();

            $this->assertDatabaseHas('jersey_customization_options', [
                'type' => $type->value,
                'slug' => 'standard',
            ]);
        }

        $this->assertSame(7, JerseyCustomizationOption::query()
            ->whereIn('type', array_map(static fn (JerseyCustomizationType $type): string => $type->value, $types))
            ->where('slug', 'standard')
            ->count());
    }

    public function test_drinkware_lanyard_and_headband_customization_values_are_stored_separately(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $types = [
            JerseyCustomizationType::DrinkwareMaterialOption,
            JerseyCustomizationType::DrinkwareSampleChargeOption,
            JerseyCustomizationType::LanyardMaterialOption,
            JerseyCustomizationType::LanyardStandardAttachmentOption,
            JerseyCustomizationType::LanyardAttachmentSurchargeOptions,
            JerseyCustomizationType::HeadbandSizeOption,
            JerseyCustomizationType::HeadbandMaterialOption,
            JerseyCustomizationType::HeadbandImprintMethodOption,
        ];

        foreach ($types as $type) {
            $payload = ['name' => 'Standard', 'type' => $type->value];
            if ($type->usesDescription()) {
                $payload['description'] = 'Reusable material description.';
            }

            $this->actingAs($admin, 'admin')->post(
                route('admin.jersey-customization-options.store'),
                $payload
            )->assertSessionDoesntHaveErrors();
        }

        $this->assertSame(8, JerseyCustomizationOption::query()
            ->whereIn('type', array_map(static fn (JerseyCustomizationType $type): string => $type->value, $types))
            ->where('slug', 'standard')
            ->count());
    }

    public function test_silicone_wristband_and_baseball_belt_customization_values_are_stored_separately(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $types = [
            JerseyCustomizationType::SiliconeWristbandProductSizeOption,
            JerseyCustomizationType::SiliconeWristbandMaterialOption,
            JerseyCustomizationType::SiliconeWristbandImprintMethodOption,
            JerseyCustomizationType::SiliconeWristbandCustomizedOptions,
            JerseyCustomizationType::BaseballBeltSizeOption,
            JerseyCustomizationType::BaseballBeltMaterialOption,
            JerseyCustomizationType::BaseballBeltImprintOption,
            JerseyCustomizationType::BaseballBeltImprintAreaOption,
            JerseyCustomizationType::BaseballBeltImprintSizeOption,
            JerseyCustomizationType::BaseballBeltColorOption,
        ];

        foreach ($types as $type) {
            $payload = ['name' => 'Standard', 'type' => $type->value];
            if ($type->usesDescription()) {
                $payload['description'] = 'Reusable material description.';
            }
            if ($type->usesColorValue()) {
                $payload['color_hex'] = '#112233';
            }

            $this->actingAs($admin, 'admin')->post(
                route('admin.jersey-customization-options.store'),
                $payload
            )->assertSessionDoesntHaveErrors();
        }

        $this->assertSame(10, JerseyCustomizationOption::query()
            ->whereIn('type', array_map(static fn (JerseyCustomizationType $type): string => $type->value, $types))
            ->where('slug', 'standard')
            ->count());
    }

    public function test_towel_armsleeve_and_fabric_wristband_values_are_stored_separately(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $types = [
            JerseyCustomizationType::TowelSizeOption,
            JerseyCustomizationType::TowelMaterialOption,
            JerseyCustomizationType::TowelFrontFabricOption,
            JerseyCustomizationType::TowelBackFabricOption,
            JerseyCustomizationType::TowelImprintSizeOption,
            JerseyCustomizationType::TowelAvailableBackingColorOption,
            JerseyCustomizationType::ArmsleeveSizeOption,
            JerseyCustomizationType::ArmsleeveFabricOption,
            JerseyCustomizationType::ArmsleeveImprintMethodOption,
            JerseyCustomizationType::FabricWristbandSizeOption,
            JerseyCustomizationType::FabricWristbandMaterialOption,
            JerseyCustomizationType::FabricWristbandStandardAttachmentOption,
            JerseyCustomizationType::FabricWristbandImprintMethodOption,
            JerseyCustomizationType::FabricWristbandLockingClosuresOption,
        ];

        foreach ($types as $type) {
            $payload = ['name' => 'Standard', 'type' => $type->value];
            if ($type->usesDescription()) {
                $payload['description'] = 'Reusable material description.';
            }
            if ($type->usesColorValue()) {
                $payload['color_hex'] = '#112233';
            }

            $this->actingAs($admin, 'admin')->post(
                route('admin.jersey-customization-options.store'),
                $payload
            )->assertSessionDoesntHaveErrors();
        }

        $this->assertSame(14, JerseyCustomizationOption::query()
            ->whereIn('type', array_map(static fn (JerseyCustomizationType $type): string => $type->value, $types))
            ->where('slug', 'standard')
            ->count());
    }

    public function test_standalone_wristbands_values_do_not_share_silicone_or_fabric_wristband_types(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $types = [
            JerseyCustomizationType::WristbandsSizeOption,
            JerseyCustomizationType::WristbandsMaterialOption,
            JerseyCustomizationType::WristbandsImprintMethodOption,
            JerseyCustomizationType::SiliconeWristbandProductSizeOption,
            JerseyCustomizationType::SiliconeWristbandMaterialOption,
            JerseyCustomizationType::SiliconeWristbandImprintMethodOption,
            JerseyCustomizationType::FabricWristbandSizeOption,
            JerseyCustomizationType::FabricWristbandMaterialOption,
            JerseyCustomizationType::FabricWristbandImprintMethodOption,
        ];

        foreach ($types as $type) {
            $payload = ['name' => 'Standard', 'type' => $type->value];
            if ($type->usesDescription()) {
                $payload['description'] = 'Category-specific material description.';
            }

            $this->actingAs($admin, 'admin')->post(
                route('admin.jersey-customization-options.store'),
                $payload
            )->assertSessionDoesntHaveErrors();
        }

        $this->assertSame(9, JerseyCustomizationOption::query()
            ->whereIn('type', array_map(static fn (JerseyCustomizationType $type): string => $type->value, $types))
            ->where('slug', 'standard')
            ->count());
    }

    public function test_knitted_gloves_and_bandana_values_are_stored_separately(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $types = [
            JerseyCustomizationType::KnittedGlovesSizeOption,
            JerseyCustomizationType::KnittedGlovesLogoOption,
            JerseyCustomizationType::KnittedGlovesMaterialOption,
            JerseyCustomizationType::KnittedGlovesColorOption,
            JerseyCustomizationType::KnittedGlovesTouchScreenFunctionOption,
            JerseyCustomizationType::KnittedGlovesInnerLiningOption,
            JerseyCustomizationType::KnittedGlovesCuffTypeOption,
            JerseyCustomizationType::KnittedGlovesFabricFeatureOption,
            JerseyCustomizationType::BandanaSizeOption,
            JerseyCustomizationType::BandanaFabricOption,
            JerseyCustomizationType::BandanaMaskLayersOption,
            JerseyCustomizationType::BandanaImprintMethodOption,
        ];

        foreach ($types as $type) {
            $payload = ['name' => 'Standard', 'type' => $type->value];
            if ($type->usesDescription()) {
                $payload['description'] = 'Reusable material description.';
            }
            if ($type->usesColorValue()) {
                $payload['color_hex'] = '#112233';
            }

            $this->actingAs($admin, 'admin')->post(
                route('admin.jersey-customization-options.store'),
                $payload
            )->assertSessionDoesntHaveErrors();
        }

        $this->assertSame(12, JerseyCustomizationOption::query()
            ->whereIn('type', array_map(static fn (JerseyCustomizationType $type): string => $type->value, $types))
            ->where('slug', 'standard')
            ->count());
    }

    public function test_premium_scarf_and_training_vest_extension_values_are_stored_separately(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $types = [
            JerseyCustomizationType::TrainingVestImprintOption,
            JerseyCustomizationType::TrainingVestLogoOption,
            JerseyCustomizationType::PremiumScarfSizeOption,
            JerseyCustomizationType::PremiumScarfMaterialOption,
            JerseyCustomizationType::PremiumScarfCraftOption,
            JerseyCustomizationType::PremiumScarfLayerOption,
            JerseyCustomizationType::PremiumScarfImprintSizeOption,
            JerseyCustomizationType::PremiumScarfYarnColorOption,
        ];

        foreach ($types as $type) {
            $payload = ['name' => 'Standard', 'type' => $type->value];
            if ($type->usesDescription()) {
                $payload['description'] = 'Reusable material description.';
            }
            if ($type->usesColorValue()) {
                $payload['color_hex'] = '#112233';
            }

            $this->actingAs($admin, 'admin')->post(
                route('admin.jersey-customization-options.store'),
                $payload
            )->assertSessionDoesntHaveErrors();
        }

        $this->assertSame(8, JerseyCustomizationOption::query()
            ->whereIn('type', array_map(static fn (JerseyCustomizationType $type): string => $type->value, $types))
            ->where('slug', 'standard')
            ->count());
    }

    public function test_same_option_slug_is_stored_separately_for_sweatshirt_and_jacket(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        foreach ([
            JerseyCustomizationType::SweatshirtColor,
            JerseyCustomizationType::JacketColor,
        ] as $type) {
            $this->actingAs($admin, 'admin')->post(
                route('admin.jersey-customization-options.store'),
                [
                    'name' => 'Black',
                    'type' => $type->value,
                    'color_hex' => '#000000',
                ]
            )->assertSessionDoesntHaveErrors();
        }

        $this->assertDatabaseHas('jersey_customization_options', [
            'type' => JerseyCustomizationType::SweatshirtColor->value,
            'slug' => 'black',
        ]);
        $this->assertDatabaseHas('jersey_customization_options', [
            'type' => JerseyCustomizationType::JacketColor->value,
            'slug' => 'black',
        ]);
        $this->assertSame(2, JerseyCustomizationOption::query()->where('slug', 'black')->count());
    }


    public function test_requested_compression_sweatshirt_cap_and_beanie_values_remain_isolated_by_master_data_type(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $types = [
            JerseyCustomizationType::CompressionWearWaistType,
            JerseyCustomizationType::CompressionWearLegLength,
            JerseyCustomizationType::CompressionWearPocketDrawstringOption,
            JerseyCustomizationType::SweatshirtZipperOption,
            JerseyCustomizationType::CapPipingOption,
            JerseyCustomizationType::BeanieSizeOption,
            JerseyCustomizationType::BeanieKnittingStyleOption,
            JerseyCustomizationType::BeanieImprintMethodOption,
            JerseyCustomizationType::BeanieColorOption,
        ];

        foreach ($types as $type) {
            $payload = [
                'name' => 'Standard',
                'type' => $type->value,
            ];

            if ($type->usesColorValue()) {
                $payload['color_hex'] = '#112233';
            }

            $this->actingAs($admin, 'admin')->post(
                route('admin.jersey-customization-options.store'),
                $payload
            )->assertSessionDoesntHaveErrors();

            $this->assertDatabaseHas('jersey_customization_options', [
                'type' => $type->value,
                'slug' => 'standard',
            ]);
        }

        $this->assertSame(count($types), JerseyCustomizationOption::query()
            ->whereIn('type', array_map(static fn (JerseyCustomizationType $type): string => $type->value, $types))
            ->where('slug', 'standard')
            ->count());
    }

    public function test_beanie_color_does_not_share_generic_headwear_color_values(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        foreach ([JerseyCustomizationType::HeadwearColor, JerseyCustomizationType::BeanieColorOption] as $type) {
            $this->actingAs($admin, 'admin')->post(
                route('admin.jersey-customization-options.store'),
                [
                    'name' => 'Black',
                    'type' => $type->value,
                    'color_hex' => '#000000',
                ]
            )->assertSessionDoesntHaveErrors();
        }

        $this->assertSame(2, JerseyCustomizationOption::query()
            ->whereIn('type', [
                JerseyCustomizationType::HeadwearColor->value,
                JerseyCustomizationType::BeanieColorOption->value,
            ])
            ->where('slug', 'black')
            ->count());
    }

    public function test_color_value_is_required_only_for_color_options(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')->post(
            route('admin.jersey-customization-options.store'),
            [
                'name' => 'Black',
                'type' => JerseyCustomizationType::Color->value,
            ]
        )->assertSessionHasErrors('color_hex');

        $this->actingAs($admin, 'admin')->post(
            route('admin.jersey-customization-options.store'),
            [
                'name' => 'Dri-Fit Mesh',
                'type' => JerseyCustomizationType::Fabric->value,
                'description' => 'Lightweight breathable jersey fabric.',
            ]
        )->assertSessionDoesntHaveErrors('color_hex');
    }
}
