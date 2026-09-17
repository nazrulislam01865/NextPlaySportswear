<?php

namespace Tests\Feature\Api\V1\Wishlist;

use App\Http\Middleware\EnforceCustomerSessionVersion;
use App\Models\Product;
use App\Models\ProductWishlist;
use App\Models\User;
use Database\Seeders\CatalogNavigationSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CategorySeeder::class, ProductSeeder::class, CatalogNavigationSeeder::class]);
    }

    public function test_guest_wishlist_is_session_scoped_and_data_first(): void
    {
        $product = Product::query()->published()->firstOrFail();

        $this->postJson('/api/v1/wishlist/items', ['product_id' => $product->id])
            ->assertCreated()
            ->assertJsonPath('data.count', 1)
            ->assertJsonPath('data.product_ids.0', $product->id);

        $response = $this->getJson('/api/v1/wishlist')
            ->assertOk()
            ->assertJsonPath('data.count', 1);

        $this->assertStringNotContainsString('item_html', $response->getContent());

        $this->deleteJson('/api/v1/wishlist/items/'.$product->id)
            ->assertOk()
            ->assertJsonPath('data.count', 0);
    }

    public function test_authenticated_wishlist_uses_customer_owned_database_records(): void
    {
        $product = Product::query()->published()->firstOrFail();
        $a = User::factory()->create(['role' => 'customer', 'is_active' => true, 'auth_session_version' => 0, 'email_verified_at' => now()]);
        $b = User::factory()->create(['role' => 'customer', 'is_active' => true, 'auth_session_version' => 0, 'email_verified_at' => now()]);

        $this->withSession([EnforceCustomerSessionVersion::SESSION_KEY => 0])
            ->actingAs($a, 'web')
            ->postJson('/api/v1/wishlist/items', ['product_id' => $product->id])
            ->assertCreated()
            ->assertJsonPath('data.count', 1);

        $this->assertTrue(ProductWishlist::query()->where('user_id', $a->id)->where('product_id', $product->id)->exists());
        $this->assertFalse(ProductWishlist::query()->where('user_id', $b->id)->where('product_id', $product->id)->exists());

        auth('web')->logout();
        $this->withSession([EnforceCustomerSessionVersion::SESSION_KEY => 0])
            ->actingAs($b, 'web')
            ->getJson('/api/v1/wishlist')
            ->assertOk()
            ->assertJsonPath('data.count', 0);
    }
}
