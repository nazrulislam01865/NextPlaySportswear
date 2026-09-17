<?php

namespace Tests\Feature\Api\V1\Catalog;

use App\Services\Cart\CartService;
use App\Services\Catalog\ProductConfigurationService;
use App\Services\Discounts\CouponService;
use App\Services\Storefront\ProductCatalogService;
use Tests\TestCase;

class ProductConfigurationApiTest extends TestCase
{
    public function test_configuration_endpoint_exposes_normalized_frontend_neutral_schema(): void
    {
        $service = $this->serviceFor($this->productFixture('quarter_zip'));
        $this->app->instance(ProductConfigurationService::class, $service);

        $response = $this->getJson('/api/v1/products/configurable-product/configuration');

        $response->assertOk()
            ->assertJsonPath('data.schema_version', 1)
            ->assertJsonPath('data.product.slug', 'configurable-product')
            ->assertJsonPath('data.product.profile', 'quarter_zip')
            ->assertJsonPath('data.option_groups.0.master_type', 'quarter_zip_imprint_option')
            ->assertJsonPath('data.option_groups.0.accepted_file_types', ['pdf', 'png'])
            ->assertJsonPath('data.size_groups.0.id', 'adult')
            ->assertJsonPath('data.roster.enabled', true)
            ->assertJsonPath('data.artwork.enabled', true)
            ->assertJsonPath('data.fulfillment.production_speeds.0.id', 'standard')
            ->assertJsonPath('data.fulfillment.shipping_methods.0.id', 'air')
            ->assertJsonStructure([
                'data' => [
                    'schema_version',
                    'product',
                    'quantity',
                    'option_groups',
                    'size_groups',
                    'roster',
                    'artwork',
                    'sample',
                    'fulfillment',
                    'pricing',
                    'request_schema',
                ],
                'meta',
                'request_id',
            ]);

        $this->assertStringNotContainsString('description_html', $response->getContent());
    }

    public function test_special_profiles_are_represented_without_duplicate_generic_size_groups(): void
    {
        $specialProfiles = [
            'bag' => 'bag_product_feature_option',
            'lanyard' => 'lanyard_product_size_option',
            'training_vest' => 'training_vest_size_option',
        ];

        foreach ($specialProfiles as $profile => $masterType) {
            $product = $this->productFixture($profile);
            $product['size_groups'] = [];
            $product['option_groups'][0]['master_type'] = $masterType;
            $service = $this->serviceFor($product);
            $payload = $service->configuration('configurable-product');

            $this->assertSame($profile, $payload['product']['profile']);
            $this->assertSame([], $payload['size_groups']);
            $this->assertSame($masterType, $payload['option_groups'][0]['master_type']);
        }

        $quarterZip = $this->serviceFor($this->productFixture('quarter_zip'))
            ->configuration('configurable-product');

        $this->assertNotEmpty($quarterZip['size_groups']);
        $this->assertSame('quarter_zip_imprint_option', $quarterZip['option_groups'][0]['master_type']);
    }

    public function test_price_preview_endpoint_returns_authoritative_pricing_contract(): void
    {
        $service = $this->serviceFor($this->productFixture('quarter_zip'));
        $this->app->instance(ProductConfigurationService::class, $service);

        $this->postJson('/api/v1/products/configurable-product/price-preview', [
            'quantity' => 2,
            'configuration' => [
                'selections' => ['imprint' => 'front'],
                'quantities' => ['adult:m' => 2],
                'production_speed' => 'standard',
                'shipping_method' => 'air',
            ],
            'line_total' => 0.01,
        ])->assertOk()
            ->assertJsonPath('data.quantity', 2)
            ->assertJsonPath('meta.authoritative', true)
            ->assertJsonStructure(['data' => ['product', 'configuration', 'customization', 'pricing'], 'meta', 'request_id']);
    }

    private function serviceFor(array $product): ProductConfigurationService
    {
        $catalog = new class($product) extends ProductCatalogService
        {
            public function __construct(private readonly array $fixture) {}

            public function findFullBySlug(string $slug): ?array
            {
                return $slug === $this->fixture['slug'] ? $this->fixture : null;
            }

            public function findBySlug(string $slug): ?array
            {
                return $slug === $this->fixture['slug'] ? $this->fixture : null;
            }
        };

        return new ProductConfigurationService(
            $catalog,
            new CartService($catalog, new CouponService())
        );
    }

    private function productFixture(string $profile): array
    {
        return [
            'id' => 99,
            'slug' => 'configurable-product',
            'title' => 'Configurable Product',
            'sku' => 'CFG-99',
            'currency' => 'USD',
            'base_price' => 10.0,
            'minimum_quantity' => 1,
            'maximum_quantity' => 100,
            'is_customizable' => true,
            'track_inventory' => false,
            'allow_backorder' => true,
            'product_profile' => $profile,
            'option_groups' => [[
                'id' => 'imprint',
                'label' => 'Imprint',
                'master_type' => 'quarter_zip_imprint_option',
                'type' => 'select',
                'display_mode' => 'customer',
                'required' => false,
                'accepted_file_types' => 'pdf, png',
                'values' => [[
                    'id' => 'front',
                    'label' => 'Front',
                    'price_delta' => 1.25,
                    'charge_type' => 'per_unit',
                    'default' => false,
                ]],
            ]],
            'size_groups' => [[
                'id' => 'adult',
                'label' => 'Adult',
                'sizes' => [['code' => 'm', 'label' => 'M', 'price_delta' => 0]],
                'chart' => ['enabled' => false],
            ]],
            'roster' => [
                'enabled' => true,
                'optional' => true,
                'fields' => [['key' => 'name', 'label' => 'Name', 'type' => 'text', 'enabled' => true]],
            ],
            'artwork_upload' => [
                'enabled' => true,
                'required' => false,
                'max_files' => 5,
                'max_file_size_mb' => 15,
                'accepted_types' => ['pdf', 'png'],
            ],
            'sample' => ['available' => true, 'charge' => 12.0, 'charge_type' => 'fixed_order'],
            'production_methods_enabled' => true,
            'production_speeds' => [[
                'id' => 'standard', 'label' => 'Standard', 'price_delta' => 0,
                'minimum_quantity' => 1, 'maximum_quantity' => null,
            ]],
            'shipping_methods_enabled' => true,
            'shipping_methods' => [[
                'id' => 'air', 'label' => 'Air', 'price_delta' => 2.0,
                'charge_type' => 'per_unit', 'default' => true,
            ]],
            'price_tiers' => [['min' => 1, 'max' => null, 'unit' => 10.0]],
            'price_table' => ['headers' => ['Quantity', 'Unit Price'], 'rows' => []],
            'fabric_price_tables' => [],
        ];
    }
}
