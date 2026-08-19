<?php

namespace Tests\Feature;

use App\Services\Cart\CartService;
use App\Services\Discounts\CouponService;
use App\Services\Storefront\ProductCatalogService;
use Tests\TestCase;

class ProductConfigurationBackendTest extends TestCase
{
    public function test_fixed_options_shipping_and_jersey_roster_are_enforced_server_side(): void
    {
        $product = $this->jerseyFixture();
        $catalog = new class($product) extends ProductCatalogService
        {
            public function __construct(private readonly array $fixture)
            {
            }

            public function findBySlug(string $slug): ?array
            {
                return $slug === $this->fixture['slug'] ? $this->fixture : null;
            }
        };

        $summary = (new CartService($catalog, new CouponService))->store([
            'product_slug' => 'test-jersey',
            'quantity' => 2,
            'configuration_json' => json_encode([
                // A customer cannot replace the admin-fixed fabric through a crafted request.
                'selections' => ['fabric' => 'mesh'],
                'multi_selections' => ['imprint' => ['front']],
                'quantities' => ['adult:s' => 2],
                'shipping_method' => 'urgent',
                'roster_enabled' => true,
                'roster' => [
                    ['values' => ['name' => 'Alice']],
                    ['values' => ['name' => 'Beth']],
                ],
            ], JSON_THROW_ON_ERROR),
        ]);

        $item = $summary['items'][0];

        $this->assertSame(2, $item['quantity']);
        $this->assertSame('nba', $item['customization']['configuration']['selections']['fabric']);
        $this->assertCount(2, $item['customization']['configuration']['roster']);
        $this->assertSame('S', $item['customization']['configuration']['roster'][0]['size_label']);
        $this->assertSame('Adult: S × 2', $item['customization']['size_summary']);
        $this->assertSame([
            'group_id' => 'adult',
            'group_label' => 'Adult',
            'size_code' => 's',
            'size_label' => 'S',
            'quantity' => 2,
        ], $item['customization']['size_breakdown'][0]);
        $this->assertSame('Name', $item['customization']['roster_fields'][0]['label']);
        $this->assertEqualsWithDelta(5.50, $item['customization_unit_price'], 0.001);
        $this->assertEqualsWithDelta(23.00, $item['line_total'], 0.001);
    }


    public function test_customer_can_select_one_of_multiple_production_options_for_the_active_quantity_range(): void
    {
        $product = $this->jerseyFixture();
        $product['production_speeds'] = [
            [
                'id' => 'small-standard',
                'label' => 'Small Batch Standard',
                'price_delta' => 2.00,
                'minimum_quantity' => 1,
                'maximum_quantity' => 10,
                'minimum_days' => 7,
                'maximum_days' => 9,
            ],
            [
                'id' => 'small-rush',
                'label' => 'Small Batch Rush',
                'price_delta' => 4.00,
                'minimum_quantity' => 1,
                'maximum_quantity' => 10,
                'minimum_days' => 3,
                'maximum_days' => 5,
            ],
            [
                'id' => 'bulk-standard',
                'label' => 'Bulk Standard',
                'price_delta' => 0.75,
                'minimum_quantity' => 11,
                'maximum_quantity' => null,
                'minimum_days' => 12,
                'maximum_days' => 16,
            ],
            [
                'id' => 'bulk-priority',
                'label' => 'Bulk Priority',
                'price_delta' => 1.25,
                'minimum_quantity' => 11,
                'maximum_quantity' => null,
                'minimum_days' => 8,
                'maximum_days' => 10,
            ],
        ];
        $product['shipping_methods'] = [];
        $product['jersey_roster']['enabled'] = false;
        $product['option_groups'][1]['required'] = false;

        $catalog = new class($product) extends ProductCatalogService
        {
            public function __construct(private readonly array $fixture)
            {
            }

            public function findBySlug(string $slug): ?array
            {
                return $slug === $this->fixture['slug'] ? $this->fixture : null;
            }
        };

        $summary = (new CartService($catalog, new CouponService))->store([
            'product_slug' => 'test-jersey',
            'quantity' => 12,
            'configuration_json' => json_encode([
                'quantities' => ['adult:s' => 12],
                'production_speed' => 'bulk-priority',
                'multi_selections' => ['imprint' => ['front']],
            ], JSON_THROW_ON_ERROR),
        ]);

        $item = $summary['items'][0];

        $this->assertSame('bulk-priority', $item['customization']['configuration']['production_speed']);
        $this->assertEqualsWithDelta(1.75, $item['customization_unit_price'], 0.001);
    }

    public function test_recent_master_data_customizations_all_support_reusable_additional_charges(): void
    {
        $product = $this->jerseyFixture();
        $types = [
            'jersey_imprint_option',
            'jersey_logo_option',
            'jersey_piping_option',
            'shorts_rope_option',
            'shorts_elastic_waist_drawcord_option',
            'shorts_imprint_option',
        ];

        $product['option_groups'] = collect($types)->map(function (string $type, int $index): array {
            $isFixedOrder = $index >= 3;

            return [
                'id' => str_replace('_', '-', $type),
                'label' => ucwords(str_replace('_', ' ', $type)),
                'jersey_customization_type' => $type,
                'type' => 'buttons',
                'display_mode' => 'customer',
                'required' => false,
                'values' => [[
                    'id' => 'standard',
                    'label' => 'Standard',
                    'price_delta' => $isFixedOrder ? 2.50 : 1.25,
                    'charge_type' => $isFixedOrder ? 'fixed_order' : 'per_unit',
                    'default' => false,
                ]],
            ];
        })->values()->all();
        $product['size_groups'] = [];
        $product['production_speeds'] = [];
        $product['shipping_methods'] = [];
        $product['jersey_roster']['enabled'] = false;

        $catalog = new class($product) extends ProductCatalogService
        {
            public function __construct(private readonly array $fixture)
            {
            }

            public function findBySlug(string $slug): ?array
            {
                return $slug === $this->fixture['slug'] ? $this->fixture : null;
            }
        };

        $selections = collect($types)->mapWithKeys(
            fn (string $type): array => [str_replace('_', '-', $type) => 'standard']
        )->all();

        $summary = (new CartService($catalog, new CouponService))->store([
            'product_slug' => 'test-jersey',
            'quantity' => 4,
            'configuration_json' => json_encode([
                'selections' => $selections,
            ], JSON_THROW_ON_ERROR),
        ]);

        $item = $summary['items'][0];

        // Three $1.25 per-piece charges plus three $2.50 fixed-order charges.
        $this->assertEqualsWithDelta(5.625, $item['customization_unit_price'], 0.001);
        $this->assertEqualsWithDelta(46.50, $item['line_total'], 0.001);
    }

    public function test_hoodie_master_data_customizations_use_reusable_additional_charges(): void
    {
        $product = $this->jerseyFixture();
        $types = [
            'hoodie_different_name_and_number_option',
            'hoodie_imprint_option',
            'hoodie_imprint_area_option',
        ];

        $product['option_groups'] = collect($types)->map(function (string $type, int $index): array {
            return [
                'id' => str_replace('_', '-', $type),
                'label' => ucwords(str_replace('_', ' ', $type)),
                'jersey_customization_type' => $type,
                'type' => 'buttons',
                'display_mode' => 'customer',
                'required' => false,
                'values' => [[
                    'id' => 'standard',
                    'label' => 'Standard',
                    'price_delta' => $index === 0 ? 1.25 : 2.50,
                    'charge_type' => $index === 0 ? 'per_unit' : 'fixed_order',
                    'default' => false,
                ]],
            ];
        })->values()->all();
        $product['size_groups'] = [];
        $product['production_speeds'] = [];
        $product['shipping_methods'] = [];
        $product['jersey_roster']['enabled'] = false;

        $catalog = new class($product) extends ProductCatalogService
        {
            public function __construct(private readonly array $fixture)
            {
            }

            public function findBySlug(string $slug): ?array
            {
                return $slug === $this->fixture['slug'] ? $this->fixture : null;
            }
        };

        $selections = collect($types)->mapWithKeys(
            fn (string $type): array => [str_replace('_', '-', $type) => 'standard']
        )->all();

        $summary = (new CartService($catalog, new CouponService))->store([
            'product_slug' => 'test-jersey',
            'quantity' => 4,
            'configuration_json' => json_encode([
                'selections' => $selections,
            ], JSON_THROW_ON_ERROR),
        ]);

        $item = $summary['items'][0];

        // $1.25 per piece + two $2.50 fixed-order charges = $10 total customization.
        $this->assertEqualsWithDelta(2.50, $item['customization_unit_price'], 0.001);
        $this->assertEqualsWithDelta(34.00, $item['line_total'], 0.001);
    }

    public function test_polo_and_tshirt_master_data_customizations_use_reusable_additional_charges(): void
    {
        $product = $this->jerseyFixture();
        $types = [
            'polo_imprint_method_option',
            'polo_back_detail_option',
            'polo_imprint_option',
            'tshirt_pocket_option',
            'tshirt_imprint_option',
            'tshirt_imprint_area_option',
            'tshirt_back_detail_option',
            'tshirt_different_name_and_number_option',
        ];

        $product['option_groups'] = collect($types)->map(function (string $type, int $index): array {
            $isFixedOrder = $index >= 4;

            return [
                'id' => str_replace('_', '-', $type),
                'label' => ucwords(str_replace('_', ' ', $type)),
                'jersey_customization_type' => $type,
                'type' => 'buttons',
                'display_mode' => 'customer',
                'required' => false,
                'values' => [[
                    'id' => 'standard',
                    'label' => 'Standard',
                    'price_delta' => $isFixedOrder ? 2.50 : 1.25,
                    'charge_type' => $isFixedOrder ? 'fixed_order' : 'per_unit',
                    'default' => false,
                ]],
            ];
        })->values()->all();
        $product['size_groups'] = [];
        $product['production_speeds'] = [];
        $product['shipping_methods'] = [];
        $product['jersey_roster']['enabled'] = false;

        $catalog = new class($product) extends ProductCatalogService
        {
            public function __construct(private readonly array $fixture)
            {
            }

            public function findBySlug(string $slug): ?array
            {
                return $slug === $this->fixture['slug'] ? $this->fixture : null;
            }
        };

        $selections = collect($types)->mapWithKeys(
            fn (string $type): array => [str_replace('_', '-', $type) => 'standard']
        )->all();

        $summary = (new CartService($catalog, new CouponService))->store([
            'product_slug' => 'test-jersey',
            'quantity' => 4,
            'configuration_json' => json_encode([
                'selections' => $selections,
            ], JSON_THROW_ON_ERROR),
        ]);

        $item = $summary['items'][0];

        // Four $1.25 per-piece charges + four $2.50 fixed-order charges = $30 customization total.
        $this->assertEqualsWithDelta(7.50, $item['customization_unit_price'], 0.001);
        $this->assertEqualsWithDelta(54.00, $item['line_total'], 0.001);
    }

    public function test_quarter_zip_and_jacket_master_data_customizations_use_reusable_additional_charges(): void
    {
        $product = $this->jerseyFixture();
        $types = [
            'quarter_zip_imprint_option',
            'quarter_zip_pocket_option',
            'quarter_zip_neck_option',
            'jacket_imprint_option',
            'jacket_imprint_area_option',
            'jacket_different_name_and_number_option',
        ];

        $product['option_groups'] = collect($types)->map(function (string $type, int $index): array {
            $isFixedOrder = $index >= 3;

            return [
                'id' => str_replace('_', '-', $type),
                'label' => ucwords(str_replace('_', ' ', $type)),
                'jersey_customization_type' => $type,
                'type' => 'buttons',
                'display_mode' => 'customer',
                'required' => false,
                'values' => [[
                    'id' => 'standard',
                    'label' => 'Standard',
                    'price_delta' => $isFixedOrder ? 2.50 : 1.25,
                    'charge_type' => $isFixedOrder ? 'fixed_order' : 'per_unit',
                    'default' => false,
                ]],
            ];
        })->values()->all();
        $product['size_groups'] = [];
        $product['production_speeds'] = [];
        $product['shipping_methods'] = [];
        $product['jersey_roster']['enabled'] = false;

        $catalog = new class($product) extends ProductCatalogService
        {
            public function __construct(private readonly array $fixture)
            {
            }

            public function findBySlug(string $slug): ?array
            {
                return $slug === $this->fixture['slug'] ? $this->fixture : null;
            }
        };

        $selections = collect($types)->mapWithKeys(
            fn (string $type): array => [str_replace('_', '-', $type) => 'standard']
        )->all();

        $summary = (new CartService($catalog, new CouponService))->store([
            'product_slug' => 'test-jersey',
            'quantity' => 4,
            'configuration_json' => json_encode([
                'selections' => $selections,
            ], JSON_THROW_ON_ERROR),
        ]);

        $item = $summary['items'][0];

        // Three $1.25 per-piece charges + three $2.50 fixed-order charges = $22.50 customization total.
        $this->assertEqualsWithDelta(5.625, $item['customization_unit_price'], 0.001);
        $this->assertEqualsWithDelta(46.50, $item['line_total'], 0.001);
    }

    public function test_tank_top_and_compression_wear_customizations_use_reusable_additional_charges(): void
    {
        $product = $this->jerseyFixture();
        $types = [
            'tank_top_imprint_option',
            'tank_top_imprint_area_option',
            'tank_top_neck_option',
            'tank_top_back_detail_option',
            'tank_top_pocket_option',
            'tank_top_different_name_and_number_charges_option',
            'compression_wear_imprint_option',
        ];

        $product['option_groups'] = collect($types)->map(function (string $type, int $index): array {
            $isFixedOrder = $index >= 4;

            return [
                'id' => str_replace('_', '-', $type),
                'label' => ucwords(str_replace('_', ' ', $type)),
                'jersey_customization_type' => $type,
                'type' => 'buttons',
                'display_mode' => 'customer',
                'required' => false,
                'values' => [[
                    'id' => 'standard',
                    'label' => 'Standard',
                    'price_delta' => $isFixedOrder ? 2.50 : 1.25,
                    'charge_type' => $isFixedOrder ? 'fixed_order' : 'per_unit',
                    'default' => false,
                ]],
            ];
        })->values()->all();
        $product['size_groups'] = [];
        $product['production_speeds'] = [];
        $product['shipping_methods'] = [];
        $product['jersey_roster']['enabled'] = false;

        $catalog = new class($product) extends ProductCatalogService
        {
            public function __construct(private readonly array $fixture)
            {
            }

            public function findBySlug(string $slug): ?array
            {
                return $slug === $this->fixture['slug'] ? $this->fixture : null;
            }
        };

        $selections = collect($types)->mapWithKeys(
            fn (string $type): array => [str_replace('_', '-', $type) => 'standard']
        )->all();

        $summary = (new CartService($catalog, new CouponService))->store([
            'product_slug' => 'test-jersey',
            'quantity' => 4,
            'configuration_json' => json_encode([
                'selections' => $selections,
            ], JSON_THROW_ON_ERROR),
        ]);

        $item = $summary['items'][0];

        // Four $1.25 per-piece charges plus three $2.50 fixed-order charges = $27.50 customization total.
        $this->assertEqualsWithDelta(6.875, $item['customization_unit_price'], 0.001);
        $this->assertEqualsWithDelta(51.50, $item['line_total'], 0.001);
    }

    public function test_socks_and_sweatshirt_customizations_use_reusable_additional_charges(): void
    {
        $product = $this->jerseyFixture();
        $types = [
            'socks_thickness_option',
            'socks_yarn_option',
            'socks_types_option',
            'socks_imprint_method_option',
            'sweatshirt_imprint_option',
            'sweatshirt_imprint_area_option',
            'sweatshirt_different_name_and_number_surcharge_option',
            'sweatshirt_d_back_option',
        ];

        $product['option_groups'] = collect($types)->map(function (string $type, int $index): array {
            $isFixedOrder = $index >= 4;

            return [
                'id' => str_replace('_', '-', $type),
                'label' => ucwords(str_replace('_', ' ', $type)),
                'jersey_customization_type' => $type,
                'type' => 'buttons',
                'display_mode' => 'customer',
                'required' => false,
                'values' => [[
                    'id' => 'standard',
                    'label' => 'Standard',
                    'price_delta' => $isFixedOrder ? 2.50 : 1.25,
                    'charge_type' => $isFixedOrder ? 'fixed_order' : 'per_unit',
                    'default' => false,
                ]],
            ];
        })->values()->all();
        $product['size_groups'] = [];
        $product['production_speeds'] = [];
        $product['shipping_methods'] = [];
        $product['jersey_roster']['enabled'] = false;

        $catalog = new class($product) extends ProductCatalogService
        {
            public function __construct(private readonly array $fixture)
            {
            }

            public function findBySlug(string $slug): ?array
            {
                return $slug === $this->fixture['slug'] ? $this->fixture : null;
            }
        };

        $selections = collect($types)->mapWithKeys(
            fn (string $type): array => [str_replace('_', '-', $type) => 'standard']
        )->all();

        $summary = (new CartService($catalog, new CouponService))->store([
            'product_slug' => 'test-jersey',
            'quantity' => 4,
            'configuration_json' => json_encode([
                'selections' => $selections,
            ], JSON_THROW_ON_ERROR),
        ]);

        $item = $summary['items'][0];

        // Four $1.25 per-piece charges plus four $2.50 fixed-order charges = $30.00 customization total.
        $this->assertEqualsWithDelta(7.50, $item['customization_unit_price'], 0.001);
        $this->assertEqualsWithDelta(54.00, $item['line_total'], 0.001);
    }

    public function test_bag_and_headwear_customizations_use_reusable_additional_charges(): void
    {
        $product = $this->jerseyFixture();
        $types = [
            'bag_size_option',
            'bag_fabric_option',
            'headwear_closure_option',
            'headwear_crown_option',
            'headwear_visor_option',
            'headwear_panels_option',
            'headwear_fabric_option',
        ];

        $product['option_groups'] = collect($types)->map(function (string $type, int $index): array {
            return [
                'id' => str_replace('_', '-', $type),
                'label' => ucwords(str_replace('_', ' ', $type)),
                'jersey_customization_type' => $type,
                'type' => 'buttons',
                'display_mode' => 'customer',
                'required' => false,
                'values' => [[
                    'id' => 'standard',
                    'label' => 'Standard',
                    'price_delta' => $index < 4 ? 1.25 : 2.50,
                    'charge_type' => $index < 4 ? 'per_unit' : 'fixed_order',
                    'default' => false,
                ]],
            ];
        })->values()->all();
        $product['size_groups'] = [];
        $product['production_speeds'] = [];
        $product['shipping_methods'] = [];
        $product['jersey_roster']['enabled'] = false;

        $catalog = new class($product) extends ProductCatalogService
        {
            public function __construct(private readonly array $fixture)
            {
            }

            public function findBySlug(string $slug): ?array
            {
                return $slug === $this->fixture['slug'] ? $this->fixture : null;
            }
        };

        $selections = collect($types)->mapWithKeys(
            fn (string $type): array => [str_replace('_', '-', $type) => 'standard']
        )->all();

        $summary = (new CartService($catalog, new CouponService))->store([
            'product_slug' => 'test-jersey',
            'quantity' => 4,
            'configuration_json' => json_encode(['selections' => $selections], JSON_THROW_ON_ERROR),
        ]);

        $item = $summary['items'][0];

        // Four $1.25 per-piece charges plus three $2.50 fixed-order charges.
        $this->assertEqualsWithDelta(6.875, $item['customization_unit_price'], 0.001);
        $this->assertEqualsWithDelta(51.50, $item['line_total'], 0.001);
    }

    public function test_drinkware_lanyard_and_headband_customizations_use_reusable_additional_charges(): void
    {
        $product = $this->jerseyFixture();
        $types = [
            'drinkware_material_option',
            'drinkware_sample_charge_option',
            'lanyard_material_option',
            'lanyard_standard_attachment_option',
            'lanyard_attachment_surcharge_options',
            'headband_size_option',
            'headband_material_option',
            'headband_imprint_method_option',
        ];

        $product['option_groups'] = collect($types)->map(function (string $type, int $index): array {
            return [
                'id' => str_replace('_', '-', $type),
                'label' => ucwords(str_replace('_', ' ', $type)),
                'jersey_customization_type' => $type,
                'type' => 'buttons',
                'display_mode' => 'customer',
                'required' => false,
                'values' => [[
                    'id' => 'standard',
                    'label' => 'Standard',
                    'price_delta' => $index < 4 ? 1.25 : 2.50,
                    'charge_type' => $index < 4 ? 'per_unit' : 'fixed_order',
                    'default' => false,
                ]],
            ];
        })->values()->all();
        $product['size_groups'] = [];
        $product['production_speeds'] = [];
        $product['shipping_methods'] = [];
        $product['jersey_roster']['enabled'] = false;

        $catalog = new class($product) extends ProductCatalogService
        {
            public function __construct(private readonly array $fixture) {}
            public function findBySlug(string $slug): ?array
            {
                return $slug === $this->fixture['slug'] ? $this->fixture : null;
            }
        };

        $selections = collect($types)->mapWithKeys(
            fn (string $type): array => [str_replace('_', '-', $type) => 'standard']
        )->all();

        $summary = (new CartService($catalog, new CouponService))->store([
            'product_slug' => 'test-jersey',
            'quantity' => 4,
            'configuration_json' => json_encode(['selections' => $selections], JSON_THROW_ON_ERROR),
        ]);

        $item = $summary['items'][0];

        // Four $1.25 per-piece charges plus four $2.50 fixed-order charges.
        $this->assertEqualsWithDelta(7.50, $item['customization_unit_price'], 0.001);
        $this->assertEqualsWithDelta(54.00, $item['line_total'], 0.001);
    }

    public function test_silicone_wristband_and_baseball_belt_customizations_use_reusable_additional_charges(): void
    {
        $product = $this->jerseyFixture();
        $types = [
            'silicone_wristband_product_size_option',
            'silicone_wristband_material_option',
            'silicone_wristband_imprint_method_option',
            'silicone_wristband_customized_options',
            'baseball_belt_size_option',
            'baseball_belt_material_option',
            'baseball_belt_imprint_option',
            'baseball_belt_imprint_area_option',
            'baseball_belt_imprint_size_option',
            'baseball_belt_color_option',
        ];

        $product['option_groups'] = collect($types)->map(function (string $type, int $index): array {
            return [
                'id' => str_replace('_', '-', $type),
                'label' => ucwords(str_replace('_', ' ', $type)),
                'jersey_customization_type' => $type,
                'type' => 'buttons',
                'display_mode' => 'customer',
                'required' => false,
                'values' => [[
                    'id' => 'standard',
                    'label' => 'Standard',
                    'price_delta' => $index < 5 ? 1.25 : 2.50,
                    'charge_type' => $index < 5 ? 'per_unit' : 'fixed_order',
                    'default' => false,
                ]],
            ];
        })->values()->all();
        $product['size_groups'] = [];
        $product['production_speeds'] = [];
        $product['shipping_methods'] = [];
        $product['jersey_roster']['enabled'] = false;

        $catalog = new class($product) extends ProductCatalogService
        {
            public function __construct(private readonly array $fixture) {}
            public function findBySlug(string $slug): ?array
            {
                return $slug === $this->fixture['slug'] ? $this->fixture : null;
            }
        };

        $selections = collect($types)->mapWithKeys(
            fn (string $type): array => [str_replace('_', '-', $type) => 'standard']
        )->all();

        $summary = (new CartService($catalog, new CouponService))->store([
            'product_slug' => 'test-jersey',
            'quantity' => 4,
            'configuration_json' => json_encode(['selections' => $selections], JSON_THROW_ON_ERROR),
        ]);

        $item = $summary['items'][0];

        // Five $1.25 per-piece charges plus five $2.50 fixed-order charges.
        $this->assertEqualsWithDelta(9.375, $item['customization_unit_price'], 0.001);
        $this->assertEqualsWithDelta(61.50, $item['line_total'], 0.001);
    }

    public function test_towel_armsleeve_and_fabric_wristband_customizations_use_reusable_additional_charges(): void
    {
        $product = $this->jerseyFixture();
        $types = [
            'towel_size_option',
            'towel_material_option',
            'towel_front_fabric_option',
            'towel_back_fabric_option',
            'towel_imprint_size_option',
            'towel_available_backing_color_option',
            'armsleeve_size_option',
            'armsleeve_fabric_option',
            'armsleeve_imprint_method_option',
            'fabric_wristband_size_option',
            'fabric_wristband_material_option',
            'fabric_wristband_standard_attachment_option',
            'fabric_wristband_imprint_method_option',
            'fabric_wristband_locking_closures_option',
        ];

        $product['option_groups'] = collect($types)->map(function (string $type, int $index): array {
            return [
                'id' => str_replace('_', '-', $type),
                'label' => ucwords(str_replace('_', ' ', $type)),
                'jersey_customization_type' => $type,
                'type' => 'buttons',
                'display_mode' => 'customer',
                'required' => false,
                'values' => [[
                    'id' => 'standard',
                    'label' => 'Standard',
                    'price_delta' => $index < 7 ? 1.00 : 2.00,
                    'charge_type' => $index < 7 ? 'per_unit' : 'fixed_order',
                    'default' => false,
                ]],
            ];
        })->values()->all();
        $product['size_groups'] = [];
        $product['production_speeds'] = [];
        $product['shipping_methods'] = [];
        $product['jersey_roster']['enabled'] = false;

        $catalog = new class($product) extends ProductCatalogService
        {
            public function __construct(private readonly array $fixture) {}
            public function findBySlug(string $slug): ?array
            {
                return $slug === $this->fixture['slug'] ? $this->fixture : null;
            }
        };

        $selections = collect($types)->mapWithKeys(
            fn (string $type): array => [str_replace('_', '-', $type) => 'standard']
        )->all();

        $summary = (new CartService($catalog, new CouponService))->store([
            'product_slug' => 'test-jersey',
            'quantity' => 4,
            'configuration_json' => json_encode(['selections' => $selections], JSON_THROW_ON_ERROR),
        ]);

        $item = $summary['items'][0];

        // Seven $1 per-piece charges plus seven $2 fixed-order charges.
        $this->assertEqualsWithDelta(10.50, $item['customization_unit_price'], 0.001);
        $this->assertEqualsWithDelta(66.00, $item['line_total'], 0.001);
    }

    public function test_knitted_gloves_and_bandana_customizations_use_reusable_additional_charges(): void
    {
        $product = $this->jerseyFixture();
        $types = [
            'knitted_gloves_size_option',
            'knitted_gloves_logo_option',
            'knitted_gloves_material_option',
            'knitted_gloves_color_option',
            'knitted_gloves_touch_screen_function_option',
            'knitted_gloves_inner_lining_option',
            'knitted_gloves_cuff_type_option',
            'knitted_gloves_fabric_feature_option',
            'bandana_size_option',
            'bandana_fabric_option',
            'bandana_mask_layers_option',
            'bandana_imprint_method_option',
        ];

        $product['option_groups'] = collect($types)->map(function (string $type, int $index): array {
            return [
                'id' => str_replace('_', '-', $type),
                'label' => ucwords(str_replace('_', ' ', $type)),
                'jersey_customization_type' => $type,
                'type' => 'buttons',
                'display_mode' => 'customer',
                'required' => false,
                'values' => [[
                    'id' => 'standard',
                    'label' => 'Standard',
                    'price_delta' => $index < 6 ? 1.00 : 2.00,
                    'charge_type' => $index < 6 ? 'per_unit' : 'fixed_order',
                    'default' => false,
                ]],
            ];
        })->values()->all();
        $product['size_groups'] = [];
        $product['production_speeds'] = [];
        $product['shipping_methods'] = [];
        $product['jersey_roster']['enabled'] = false;

        $catalog = new class($product) extends ProductCatalogService
        {
            public function __construct(private readonly array $fixture) {}
            public function findBySlug(string $slug): ?array
            {
                return $slug === $this->fixture['slug'] ? $this->fixture : null;
            }
        };

        $selections = collect($types)->mapWithKeys(
            fn (string $type): array => [str_replace('_', '-', $type) => 'standard']
        )->all();

        $summary = (new CartService($catalog, new CouponService))->store([
            'product_slug' => 'test-jersey',
            'quantity' => 4,
            'configuration_json' => json_encode(['selections' => $selections], JSON_THROW_ON_ERROR),
        ]);

        $item = $summary['items'][0];

        // Six $1 per-piece charges plus six $2 fixed-order charges.
        $this->assertEqualsWithDelta(9.00, $item['customization_unit_price'], 0.001);
        $this->assertEqualsWithDelta(60.00, $item['line_total'], 0.001);
    }

    public function test_premium_scarf_and_training_vest_customizations_use_reusable_additional_charges(): void
    {
        $types = [
            'training_vest_color_option',
            'training_vest_fabric_option',
            'training_vest_size_option',
            'training_vest_vest_type_option',
            'training_vest_imprint_option',
            'training_vest_logo_option',
            'premium_scarf_size_option',
            'premium_scarf_material_option',
            'premium_scarf_craft_option',
            'premium_scarf_layer_option',
            'premium_scarf_imprint_size_option',
            'premium_scarf_yarn_color_option',
        ];

        $product = $this->jerseyFixture();
        $product['option_groups'] = collect($types)->map(function (string $type, int $index): array {
            return [
                'id' => str_replace('_', '-', $type),
                'label' => ucwords(str_replace('_', ' ', $type)),
                'type' => 'select',
                'display_mode' => 'customer',
                'required' => false,
                'values' => [[
                    'id' => 'standard',
                    'label' => 'Standard',
                    'price_delta' => $index < 6 ? 1.00 : 2.00,
                    'charge_type' => $index < 6 ? 'per_unit' : 'fixed_order',
                    'default' => false,
                ]],
            ];
        })->values()->all();
        $product['size_groups'] = [];
        $product['production_speeds'] = [];
        $product['shipping_methods'] = [];
        $product['jersey_roster']['enabled'] = false;

        $catalog = new class($product) extends ProductCatalogService
        {
            public function __construct(private readonly array $fixture) {}
            public function findBySlug(string $slug): ?array
            {
                return $slug === $this->fixture['slug'] ? $this->fixture : null;
            }
        };

        $selections = collect($types)->mapWithKeys(
            fn (string $type): array => [str_replace('_', '-', $type) => 'standard']
        )->all();

        $summary = (new CartService($catalog, new CouponService))->store([
            'product_slug' => 'test-jersey',
            'quantity' => 4,
            'configuration_json' => json_encode(['selections' => $selections], JSON_THROW_ON_ERROR),
        ]);

        $item = $summary['items'][0];

        // Six $1 per-piece charges plus six $2 fixed-order charges.
        $this->assertEqualsWithDelta(9.00, $item['customization_unit_price'], 0.001);
        $this->assertEqualsWithDelta(60.00, $item['line_total'], 0.001);
    }

    public function test_size_specific_extra_charges_are_applied_per_selected_piece(): void
    {
        $product = $this->jerseyFixture();
        $product['jersey_roster']['enabled'] = false;
        $product['shipping_methods'] = [];
        $product['option_groups'] = [];
        $product['size_groups'][0]['sizes'] = [
            ['code' => 's', 'label' => 'S', 'price_delta' => 0],
            ['code' => 'm', 'label' => 'M', 'price_delta' => 2.50],
        ];

        $catalog = new class($product) extends ProductCatalogService
        {
            public function __construct(private readonly array $fixture) {}
            public function findBySlug(string $slug): ?array
            {
                return $slug === $this->fixture['slug'] ? $this->fixture : null;
            }
        };

        $summary = (new CartService($catalog, new CouponService))->store([
            'product_slug' => 'test-jersey',
            'quantity' => 4,
            'configuration_json' => json_encode([
                'quantities' => ['adult:s' => 1, 'adult:m' => 3],
            ], JSON_THROW_ON_ERROR),
        ]);

        $item = $summary['items'][0];
        $this->assertEqualsWithDelta(1.875, $item['customization_unit_price'], 0.001);
        $this->assertEqualsWithDelta(31.50, $item['line_total'], 0.001);
    }

    public function test_size_extra_charge_editor_uses_a_shared_component(): void
    {
        $form = file_get_contents(resource_path('views/admin/products/_form.blade.php'));
        $card = file_get_contents(resource_path('views/components/admin/product-size-group-card.blade.php'));
        $component = file_get_contents(resource_path('views/components/admin/size-extra-charge-fields.blade.php'));

        $this->assertStringContainsString('<x-admin.product-size-group-card />', $form);
        $this->assertStringContainsString('<x-admin.size-extra-charge-fields />', $card);
        $this->assertStringContainsString('has_size_extra_charges', $component);
        $this->assertStringContainsString('size_charges', $component);
        $this->assertStringContainsString('[code]', $component);
        $this->assertStringContainsString('[amount]', $component);
        $this->assertStringContainsString('(size, sizeIndex) in (group.sizes || [])', $component);
        $this->assertStringContainsString('setSizeChargeValue(group, size, $event.target.value)', $component);
        $this->assertStringNotContainsString('size_price_adjustments][${sizeCode(group, size)}', $component);

        $builder = file_get_contents(resource_path('views/components/storefront/product/builder.blade.php'));
        $label = file_get_contents(resource_path('views/components/storefront/product/size-charge-label.blade.php'));
        $this->assertStringContainsString('<x-storefront.product.size-charge-label', $builder);
        $this->assertStringContainsString('Included', $label);
        $this->assertStringContainsString('/ piece', $label);

        $request = file_get_contents(app_path('Http/Requests/Admin/ProductFormRequest.php'));
        $controller = file_get_contents(app_path('Http/Controllers/Admin/ProductController.php'));
        $normalizer = file_get_contents(app_path('Support/ProductSizeExtraCharges.php'));
        $this->assertStringContainsString("size_groups.*.size_charges.*.amount", $request);
        $this->assertStringContainsString('ProductSizeExtraCharges::adjustments($groupData)', $controller);
        $this->assertStringContainsString("\$groupData['size_charges']", $normalizer);

        $this->assertStringContainsString("'size_price_adjustments' => \$group->sizes->mapWithKeys", $form);
        $this->assertStringContainsString("'has_size_extra_charges' => \$group->sizes->contains", $form);
    }

    private function jerseyFixture(): array
    {
        return [
            'slug' => 'test-jersey',
            'title' => 'Test Jersey',
            'short_title' => 'Test Jersey',
            'summary' => '',
            'sku' => 'TEST-1',
            'category' => 'Jerseys',
            'sport' => 'Baseball',
            'image' => '/x.jpg',
            'alt' => '',
            'url' => '/product/test-jersey',
            'base_price' => 6,
            'price' => 'From $6',
            'minimum_quantity' => 1,
            'maximum_quantity' => 100,
            'is_customizable' => true,
            'track_inventory' => false,
            'allow_backorder' => true,
            'product_profile' => 'jersey',
            'price_tiers' => [['min' => 1, 'max' => null, 'unit' => 6]],
            'option_groups' => [
                [
                    'id' => 'fabric',
                    'label' => 'Fabric',
                    'type' => 'select',
                    'display_mode' => 'fixed',
                    'fixed_value_code' => 'nba',
                    'required' => false,
                    'values' => [
                        ['id' => 'nba', 'label' => 'NBA', 'price_delta' => 0, 'charge_type' => 'included', 'default' => true],
                        ['id' => 'mesh', 'label' => 'Mesh', 'price_delta' => 1, 'charge_type' => 'per_unit', 'default' => false],
                    ],
                ],
                [
                    'id' => 'imprint',
                    'label' => 'Imprint',
                    'type' => 'checkbox',
                    'display_mode' => 'customer',
                    'required' => true,
                    'minimum_selections' => 1,
                    'maximum_selections' => 2,
                    'values' => [
                        ['id' => 'front', 'label' => 'Front', 'price_delta' => .5, 'charge_type' => 'per_unit', 'default' => false],
                    ],
                ],
            ],
            'size_groups' => [[
                'id' => 'adult',
                'label' => 'Adult',
                'sizes' => [
                    ['code' => 's', 'label' => 'S', 'price_delta' => 0],
                    ['code' => 'm', 'label' => 'M', 'price_delta' => 0],
                ],
            ]],
            'artwork_methods' => [],
            'production_speeds' => [],
            'shipping_methods' => [[
                'id' => 'urgent',
                'label' => 'Urgent',
                'price_delta' => 10,
                'charge_type' => 'fixed_order',
                'default' => true,
            ]],
            'jersey_roster' => [
                'enabled' => true,
                'optional' => false,
                'fields' => [[
                    'key' => 'name',
                    'label' => 'Name',
                    'type' => 'text',
                    'max_length' => 20,
                    'required' => true,
                    'enabled' => true,
                ]],
            ],
        ];
    }
    public function test_size_charge_edit_hydration_and_sidebar_state_are_stable_across_navigation(): void
    {
        $form = file_get_contents(resource_path('views/admin/products/_form.blade.php'));
        $adminJs = file_get_contents(resource_path('js/admin.js'));
        $layout = file_get_contents(resource_path('views/components/layouts/admin.blade.php'));

        $this->assertStringContainsString('editorAdjustmentAliases', $form);
        $this->assertStringContainsString('sizeChargeKeys(group, size)', $adminJs);
        $this->assertStringContainsString('sizeChargeAmount(group, size)', $adminJs);
        $this->assertStringContainsString('<x-admin.sidebar-prepaint-state />', $layout);
        $this->assertStringContainsString('<x-admin.sidebar-scroll-restore />', $layout);
    }

}
