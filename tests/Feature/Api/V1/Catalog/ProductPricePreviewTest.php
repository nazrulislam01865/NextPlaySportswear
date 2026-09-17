<?php

namespace Tests\Feature\Api\V1\Catalog;

use App\Services\Cart\CartService;
use App\Services\Discounts\CouponService;
use App\Services\Storefront\ProductCatalogService;
use Tests\TestCase;

class ProductPricePreviewTest extends TestCase
{
    public function test_preview_and_final_cart_use_the_same_authoritative_calculation_path(): void
    {
        $cart = $this->cartFor($this->fixture());
        $payload = [
            'product_slug' => 'preview-product',
            'quantity' => 2,
            'unit_price' => 0.01,
            'line_total' => 0.01,
            'configuration_json' => json_encode([
                'selections' => ['fabric' => 'mesh'],
                'quantities' => ['adult:m' => 2],
                'shipping_method' => 'air',
            ], JSON_THROW_ON_ERROR),
        ];

        $preview = $cart->previewItem($payload);
        $summary = $cart->store($payload);
        $stored = $summary['items'][0];

        foreach (['unit_price', 'customization_unit_price', 'shipping_unit_price', 'line_subtotal', 'customization_total', 'product_shipping_total', 'line_total'] as $field) {
            $this->assertEqualsWithDelta((float) $stored[$field], (float) $preview[$field], 0.001, $field);
        }

        $this->assertNotEquals(0.01, $preview['unit_price']);
        $this->assertNotEquals(0.01, $preview['line_total']);
    }

    public function test_invalid_and_incompatible_configuration_is_rejected_instead_of_silently_normalized(): void
    {
        $cart = $this->cartFor($this->fixture());

        try {
            $cart->previewItem([
                'product_slug' => 'preview-product',
                'quantity' => 2,
                'configuration_json' => json_encode([
                    'selections' => ['fabric' => 'does-not-exist'],
                ], JSON_THROW_ON_ERROR),
            ]);
            $this->fail('Invalid option was not rejected.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }

        try {
            $cart->previewItem([
                'product_slug' => 'preview-product',
                'quantity' => 2,
                'configuration_json' => json_encode([
                    'multi_selections' => ['extras' => ['logo', 'logo']],
                ], JSON_THROW_ON_ERROR),
            ]);
            $this->fail('Duplicate options were not rejected.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }
    }

    public function test_out_of_stock_option_values_are_rejected_server_side(): void
    {
        $cart = $this->cartFor($this->fixture());

        try {
            $cart->previewItem([
                'product_slug' => 'preview-product',
                'quantity' => 1,
                'configuration_json' => json_encode([
                    'selections' => ['fabric' => 'sold-out'],
                ], JSON_THROW_ON_ERROR),
            ]);
            $this->fail('Out-of-stock option was not rejected.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }
    }

    public function test_dedicated_size_profiles_reject_generic_size_quantities(): void
    {
        $product = $this->fixture();
        $product['product_profile'] = 'training_vest';
        $cart = $this->cartFor($product);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $cart->previewItem([
            'product_slug' => 'preview-product',
            'quantity' => 2,
            'configuration_json' => json_encode([
                'quantities' => ['adult:m' => 2],
            ], JSON_THROW_ON_ERROR),
        ]);
    }

    private function cartFor(array $product): CartService
    {
        $catalog = new class($product) extends ProductCatalogService
        {
            public function __construct(private readonly array $fixture) {}

            public function findBySlug(string $slug): ?array
            {
                return $slug === $this->fixture['slug'] ? $this->fixture : null;
            }
        };

        return new CartService($catalog, new CouponService());
    }

    private function fixture(): array
    {
        return [
            'slug' => 'preview-product',
            'title' => 'Preview Product',
            'sku' => 'PREVIEW-1',
            'category' => 'Jerseys',
            'sport' => 'Team',
            'image' => '/product.jpg',
            'alt' => 'Preview Product',
            'url' => '/products/preview-product',
            'base_price' => 10,
            'price' => 'From $10',
            'minimum_quantity' => 1,
            'maximum_quantity' => 100,
            'is_customizable' => true,
            'track_inventory' => false,
            'allow_backorder' => true,
            'product_profile' => 'quarter_zip',
            'price_tiers' => [['min' => 1, 'max' => null, 'unit' => 10]],
            'option_groups' => [
                [
                    'id' => 'fabric', 'label' => 'Fabric', 'type' => 'select', 'display_mode' => 'customer', 'required' => false,
                    'values' => [
                        ['id' => 'mesh', 'label' => 'Mesh', 'price_delta' => 1.50, 'charge_type' => 'per_unit', 'default' => false, 'stock_quantity' => null],
                        ['id' => 'sold-out', 'label' => 'Sold Out', 'price_delta' => 0, 'charge_type' => 'per_unit', 'default' => false, 'stock_quantity' => 0],
                    ],
                ],
                [
                    'id' => 'extras', 'label' => 'Extras', 'type' => 'checkbox', 'display_mode' => 'customer', 'required' => false,
                    'maximum_selections' => 2,
                    'values' => [
                        ['id' => 'logo', 'label' => 'Logo', 'price_delta' => 0.50, 'charge_type' => 'per_unit', 'default' => false],
                    ],
                ],
            ],
            'size_groups' => [[
                'id' => 'adult', 'label' => 'Adult',
                'sizes' => [['code' => 'm', 'label' => 'M', 'price_delta' => 0]],
            ]],
            'production_speeds' => [],
            'shipping_methods' => [[
                'id' => 'air', 'label' => 'Air', 'price_delta' => 2.00,
                'charge_type' => 'per_unit', 'default' => true,
            ]],
            'roster' => ['enabled' => false, 'optional' => true, 'fields' => []],
            'artwork_upload' => ['enabled' => false, 'required' => false, 'max_files' => 5],
        ];
    }
}
