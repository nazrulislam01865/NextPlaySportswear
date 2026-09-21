<?php

namespace Tests\Feature\Api\V1\Storefront;

use App\Http\Middleware\EnforceCustomerSessionVersion;
use App\Models\User;
use App\Services\Storefront\StorefrontBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontBootstrapApiTest extends TestCase
{
    use RefreshDatabase;
    public function test_guest_bootstrap_returns_only_safe_global_storefront_data(): void
    {
        $payload = $this->bootstrapPayload();

        $this->mock(StorefrontBootstrapService::class)
            ->shouldReceive('get')
            ->once()
            ->with(null)
            ->andReturn($payload);

        $response = $this->getJson('/api/v1/storefront/bootstrap');

        $response->assertOk()
            ->assertHeader('X-Request-ID')
            ->assertJsonPath('data.site.name', 'NextPlay Sportswear')
            ->assertJsonPath('data.customer', null)
            ->assertJsonPath('data.cart.quantity', 0)
            ->assertJsonPath('data.cart.total', 0)
            ->assertJsonPath('data.wishlist.total_items', 0)
            ->assertJsonPath('data.navigation.items.0.label', 'SHOP')
            ->assertJsonPath('data.navigation.items.0.mega_menu.top_choices.eyebrow', 'Top Choices')
            ->assertJsonPath('data.navigation.items.0.mega_menu.promo.label', 'Shop All Products')
            ->assertJsonPath('data.header.announcements.0.text', 'Shop $200 and get free delivery')
            ->assertJsonPath('data.footer.contact.email', 'support@example.com')
            ->assertJsonStructure(['data', 'meta', 'request_id']);

        $body = $response->getContent();
        $this->assertStringNotContainsString('password', $body);
        $this->assertStringNotContainsString('remember_token', $body);
        $this->assertStringNotContainsString('flowtrack', strtolower($body));
        $this->assertStringNotContainsString('<html', strtolower($body));
    }

    public function test_authenticated_customer_bootstrap_returns_safe_customer_summary(): void
    {
        $customer = User::factory()->create([
            'name' => 'NextPlay Customer',
            'email' => 'customer@example.com',
            'role' => 'customer',
            'is_active' => true,
            'auth_session_version' => 0,
            'email_verified_at' => now(),
        ]);

        $payload = $this->bootstrapPayload([
            'id' => (int) $customer->getKey(),
            'name' => 'NextPlay Customer',
            'email' => 'customer@example.com',
            'email_verified' => true,
        ]);

        $this->mock(StorefrontBootstrapService::class)
            ->shouldReceive('get')
            ->once()
            ->withArgs(fn (?User $user): bool => $user?->is($customer))
            ->andReturn($payload);

        $response = $this->withSession([
            EnforceCustomerSessionVersion::SESSION_KEY => 0,
        ])->actingAs($customer, 'web')
            ->getJson('/api/v1/storefront/bootstrap');

        $response->assertOk()
            ->assertJsonPath('data.customer.id', (int) $customer->getKey())
            ->assertJsonPath('data.customer.email', 'customer@example.com')
            ->assertJsonPath('data.customer.email_verified', true);

        $body = $response->getContent();
        $this->assertStringNotContainsString('password', $body);
        $this->assertStringNotContainsString('remember_token', $body);
        $this->assertStringNotContainsString('admin_role', $body);
        $this->assertStringNotContainsString('payment_metadata', $body);
    }

    public function test_stale_customer_session_uses_v1_error_contract(): void
    {
        $customer = User::factory()->create([
            'name' => 'Stale Customer',
            'email' => 'stale@example.com',
            'role' => 'customer',
            'is_active' => true,
            'auth_session_version' => 2,
            'email_verified_at' => now(),
        ]);

        $this->mock(StorefrontBootstrapService::class)
            ->shouldNotReceive('get');

        $response = $this->withSession([
            EnforceCustomerSessionVersion::SESSION_KEY => 1,
        ])->actingAs($customer, 'web')
            ->getJson('/api/v1/storefront/bootstrap');

        $response->assertUnauthorized()
            ->assertHeader('X-Request-ID')
            ->assertJsonPath('message', 'Your customer session is no longer valid. Please sign in again.')
            ->assertJsonStructure(['message', 'request_id']);
    }

    private function bootstrapPayload(?array $customer = null): array
    {
        return [
            'site' => [
                'name' => 'NextPlay Sportswear',
                'tagline' => 'Custom sportswear for teams.',
                'logo' => '/images/logo.png',
            ],
            'customer' => $customer,
            'cart' => [
                'quantity' => 0,
                'total_items' => 0,
                'total' => 0.0,
            ],
            'wishlist' => [
                'total_items' => 0,
            ],
            'navigation' => [
                'items' => [[
                    'enabled' => true,
                    'label' => 'SHOP',
                    'url' => '/products',
                    'target' => '_self',
                    'mega_menu' => [
                        'enabled' => true,
                        'top_choices' => [
                            'enabled' => true,
                            'eyebrow' => 'Top Choices',
                            'links' => [],
                        ],
                        'columns' => [],
                        'promo' => [
                            'enabled' => true,
                            'image' => '/images/storefront-vue/navigation/shop-mega-promo.jpg',
                            'alt' => 'Promo',
                            'label' => 'Shop All Products',
                            'url' => '/products',
                        ],
                    ],
                ]],
            ],
            'header' => [
                'announcements' => [['enabled' => true, 'text' => 'Shop $200 and get free delivery']],
            ],
            'footer' => [
                'contact' => ['email' => 'support@example.com'],
            ],
        ];
    }
}
