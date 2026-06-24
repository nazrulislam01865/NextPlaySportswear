<?php

namespace Tests\Feature;

use App\Services\Cart\CartService;
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

        $summary = (new CartService($catalog))->store([
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
        $this->assertEqualsWithDelta(5.50, $item['customization_unit_price'], 0.001);
        $this->assertEqualsWithDelta(23.00, $item['line_total'], 0.001);
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
}
