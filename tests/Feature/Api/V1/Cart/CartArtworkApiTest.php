<?php

namespace Tests\Feature\Api\V1\Cart;

use App\Services\Storefront\ProductCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CartArtworkApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $product = [
            'slug' => 'artwork-product',
            'title' => 'Artwork Product',
            'short_title' => 'Artwork Product',
            'summary' => '',
            'sku' => 'ART-1',
            'category' => 'Team',
            'sport' => 'Team',
            'image' => '/artwork.jpg',
            'alt' => 'Artwork Product',
            'url' => '/products/artwork-product',
            'base_price' => 20,
            'price' => 'From $20',
            'minimum_quantity' => 1,
            'maximum_quantity' => 100,
            'is_customizable' => true,
            'track_inventory' => false,
            'allow_backorder' => true,
            'product_profile' => 'standard',
            'price_tiers' => [['min' => 1, 'max' => null, 'unit' => 20]],
            'option_groups' => [],
            'size_groups' => [],
            'production_speeds' => [],
            'shipping_methods' => [],
            'roster' => ['enabled' => false, 'optional' => true, 'fields' => []],
            'artwork_upload' => [
                'enabled' => true,
                'required' => true,
                'max_files' => 3,
                'max_file_size_mb' => 5,
                'accepted_types' => ['png', 'pdf'],
            ],
        ];

        $catalog = new class($product) extends ProductCatalogService
        {
            public function __construct(private readonly array $fixture) {}

            public function findBySlug(string $slug): ?array
            {
                return $slug === $this->fixture['slug'] ? $this->fixture : null;
            }

            public function findFullBySlug(string $slug): ?array
            {
                return $this->findBySlug($slug);
            }
        };

        $this->app->instance(ProductCatalogService::class, $catalog);
    }

    public function test_artwork_upload_is_retained_by_opaque_token_without_exposing_local_storage_path(): void
    {
        $created = $this->post('/api/v1/cart/items', [
            'product_slug' => 'artwork-product',
            'quantity' => 1,
            'artwork_files' => [UploadedFile::fake()->create('logo.png', 10, 'image/png')],
        ], ['Accept' => 'application/json']);

        $created->assertCreated()
            ->assertJsonPath('data.items.0.customization.artwork_files.0.name', 'logo.png');

        $key = (string) $created->json('data.items.0.key');
        $token = (string) $created->json('data.items.0.customization.artwork_files.0.retention_token');

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
        $this->assertStringNotContainsString('customer-artwork/', $created->getContent());

        $storedFiles = Storage::disk('local')->allFiles('customer-artwork');
        $this->assertCount(1, $storedFiles);

        $updated = $this->patchJson('/api/v1/cart/items/'.$key.'/options', [
            'product_slug' => 'artwork-product',
            'quantity' => 1,
            'retained_artwork_tokens' => [$token],
        ]);

        $updated->assertOk()
            ->assertJsonPath('data.items.0.customization.artwork_files.0.retention_token', $token);

        $this->assertStringNotContainsString('customer-artwork/', $updated->getContent());
        Storage::disk('local')->assertExists($storedFiles[0]);
    }
}
