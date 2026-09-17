<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Http\Middleware\EnforceCustomerSessionVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AuthenticationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register_and_receive_safe_session_identity(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'API Customer',
            'email' => ' API@EXAMPLE.COM ',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'terms' => true,
            'website' => '',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.customer.email', 'api@example.com')
            ->assertJsonPath('data.customer.role', 'customer')
            ->assertJsonPath('data.customer.email_verified', false);

        $customer = User::query()->where('email', 'api@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($customer, 'web');
        $this->assertGuest('admin');
        $this->assertStringNotContainsString('remember_token', $response->getContent());
        $this->assertStringNotContainsString('auth_session_version', $response->getContent());
        $this->assertStringNotContainsString('password', $response->getContent());
    }

    public function test_active_customer_can_login_read_me_and_logout_without_api_token(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
            'email_verified_at' => now(),
            'password' => Hash::make('Password123'),
            'auth_session_version' => 0,
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => strtoupper($customer->email),
            'password' => 'Password123',
            'remember' => true,
        ]);

        $login->assertOk()
            ->assertJsonPath('data.customer.id', $customer->id)
            ->assertJsonPath('data.customer.email_verified', true);

        $this->assertAuthenticatedAs($customer->fresh(), 'web');
        $this->assertGuest('admin');
        $this->assertStringNotContainsString('token', strtolower($login->getContent()));

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.customer.id', $customer->id);

        $this->postJson('/api/v1/auth/logout')->assertOk();
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_admin_session_cannot_access_customer_identity_api(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_invalid_admin_and_suspended_customer_login_are_rejected(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'password' => Hash::make('Password123'),
        ]);
        $suspended = User::factory()->create([
            'role' => 'customer',
            'is_active' => false,
            'password' => Hash::make('Password123'),
        ]);

        $this->postJson('/api/v1/auth/login', ['email' => $admin->email, 'password' => 'Password123'])
            ->assertUnprocessable();
        $this->postJson('/api/v1/auth/login', ['email' => $suspended->email, 'password' => 'Password123'])
            ->assertUnprocessable();
        $this->assertGuest('web');
    }

    public function test_stale_customer_session_uses_api_error_contract(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
            'auth_session_version' => 2,
        ]);

        $this->actingAs($customer, 'web')
            ->withSession([EnforceCustomerSessionVersion::SESSION_KEY => 1])
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonStructure(['message', 'request_id']);
    }
}
