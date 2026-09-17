<?php

namespace Tests\Feature\Api\V1\Cart;

use App\Http\Middleware\EnforceCustomerSessionVersion;
use App\Models\User;
use App\Services\Storefront\ProductCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bindCatalog();
    }

    public function test_guest_cart_mutations_preserve_session_and_return_authoritative_data_without_blade_html(): void
    {
        $add = $this->postJson('/api/v1/cart/items', [
            'product_slug' => 'api-cart-product',
            'quantity' => 2,
            'line_total' => 0.01,
            'configuration' => [
                'selections' => ['color' => 'red'],
            ],
        ]);

        $add->assertCreated()
            ->assertJsonPath('data.quantity', 2)
            ->assertJsonPath('data.items.0.product.slug', 'api-cart-product')
            ->assertJsonPath('data.items.0.customization.configuration.selections.color', 'red');

        $this->assertNotEquals(0.01, $add->json('data.items.0.line_total'));
        $this->assertStringNotContainsString('item_html', $add->getContent());
        $this->assertStringNotContainsString('<x-', $add->getContent());

        $this->getJson('/api/v1/cart')
            ->assertOk()
            ->assertJsonPath('data.quantity', 2)
            ->assertJsonPath('data.items.0.customization.configuration.selections.color', 'red');
    }

    public function test_same_idempotency_key_does_not_double_add_cart_quantity(): void
    {
        $payload = ['product_slug' => 'api-cart-product', 'quantity' => 2];
        $headers = ['Idempotency-Key' => 'cart-add-00000001'];

        $first = $this->withHeaders($headers)->postJson('/api/v1/cart/items', $payload);
        $second = $this->withHeaders($headers)->postJson('/api/v1/cart/items', $payload);

        $first->assertCreated()->assertJsonPath('data.quantity', 2);
        $second->assertOk()->assertJsonPath('data.quantity', 2);
        $second->assertHeader('Idempotency-Replayed', 'true');
    }

    public function test_idempotency_key_cannot_be_reused_with_a_different_payload(): void
    {
        $headers = ['Idempotency-Key' => 'cart-add-conflict-01'];

        $this->withHeaders($headers)->postJson('/api/v1/cart/items', [
            'product_slug' => 'api-cart-product',
            'quantity' => 1,
        ])->assertCreated();

        $this->withHeaders($headers)->postJson('/api/v1/cart/items', [
            'product_slug' => 'api-cart-product',
            'quantity' => 2,
        ])->assertStatus(409);

        $this->getJson('/api/v1/cart')
            ->assertOk()
            ->assertJsonPath('data.quantity', 1);
    }

    public function test_quantity_options_and_remove_mutations_return_full_recalculated_cart(): void
    {
        $created = $this->postJson('/api/v1/cart/items', [
            'product_slug' => 'api-cart-product',
            'quantity' => 1,
        ])->assertCreated();

        $key = (string) $created->json('data.items.0.key');

        $this->patchJson('/api/v1/cart/items/'.$key, ['quantity' => 3])
            ->assertOk()
            ->assertJsonPath('data.quantity', 3);

        $this->patchJson('/api/v1/cart/items/'.$key.'/options', [
            'product_slug' => 'api-cart-product',
            'quantity' => 3,
            'configuration' => ['selections' => ['color' => 'blue']],
        ])->assertOk()
            ->assertJsonPath('data.items.0.customization.configuration.selections.color', 'blue');

        $newKey = (string) $this->getJson('/api/v1/cart')->json('data.items.0.key');
        $this->deleteJson('/api/v1/cart/items/'.$newKey)
            ->assertOk()
            ->assertJsonPath('data.is_empty', true)
            ->assertJsonPath('data.total', 0);
    }

    public function test_authenticated_carts_remain_owned_by_the_authenticated_customer(): void
    {
        $a = User::factory()->create(['role' => 'customer', 'is_active' => true, 'auth_session_version' => 0, 'email_verified_at' => now()]);
        $b = User::factory()->create(['role' => 'customer', 'is_active' => true, 'auth_session_version' => 0, 'email_verified_at' => now()]);

        $this->withSession([EnforceCustomerSessionVersion::SESSION_KEY => 0])
            ->actingAs($a, 'web')
            ->postJson('/api/v1/cart/items', ['product_slug' => 'api-cart-product', 'quantity' => 2])
            ->assertCreated()
            ->assertJsonPath('data.quantity', 2);

        auth('web')->logout();
        $this->withSession([EnforceCustomerSessionVersion::SESSION_KEY => 0])
            ->actingAs($b, 'web')
            ->getJson('/api/v1/cart')
            ->assertOk()
            ->assertJsonPath('data.is_empty', true);
    }

    private function bindCatalog(): void
    {
        $product = [
            'slug' => 'api-cart-product', 'title' => 'API Cart Product', 'short_title' => 'API Cart Product',
            'summary' => '', 'sku' => 'API-CART-1', 'category' => 'Team', 'sport' => 'Team', 'image' => '/x.jpg',
            'alt' => '', 'url' => '/products/api-cart-product', 'base_price' => 12, 'price' => 'From $12',
            'minimum_quantity' => 1, 'maximum_quantity' => 100, 'is_customizable' => true,
            'track_inventory' => false, 'allow_backorder' => true, 'product_profile' => 'standard',
            'price_tiers' => [['min' => 1, 'max' => null, 'unit' => 12]],
            'option_groups' => [[
                'id' => 'color', 'label' => 'Color', 'type' => 'select', 'display_mode' => 'customer', 'required' => false,
                'values' => [
                    ['id' => 'red', 'label' => 'Red', 'price_delta' => 1, 'charge_type' => 'per_unit', 'default' => false],
                    ['id' => 'blue', 'label' => 'Blue', 'price_delta' => 2, 'charge_type' => 'per_unit', 'default' => false],
                ],
            ]],
            'size_groups' => [], 'production_speeds' => [], 'shipping_methods' => [],
            'roster' => ['enabled' => false, 'optional' => true, 'fields' => []],
            'artwork_upload' => ['enabled' => false, 'required' => false, 'max_files' => 5],
        ];

        $catalog = new class($product) extends ProductCatalogService
        {
            public function __construct(private readonly array $fixture) {}
            public function findBySlug(string $slug): ?array { return $slug === $this->fixture['slug'] ? $this->fixture : null; }
            public function findFullBySlug(string $slug): ?array { return $this->findBySlug($slug); }
        };

        $this->app->instance(ProductCatalogService::class, $catalog);
    }
}
