<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceCustomerSessionVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AuthenticationSeparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_account_cannot_sign_in_through_customer_login(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'password' => Hash::make('Password123'),
        ]);

        $this->from(route('login'))->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'Password123',
        ])->assertRedirect(route('login'))->assertSessionHasErrors('email');

        $this->assertGuest('web');
    }

    public function test_customer_account_cannot_sign_in_through_admin_login(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
            'password' => Hash::make('Password123'),
        ]);

        $this->from(route('admin.login'))->post(route('admin.login.store'), [
            'email' => $customer->email,
            'password' => 'Password123',
        ])->assertRedirect(route('admin.login'))->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_admin_session_stays_separate_from_storefront_customer_access(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'password' => Hash::make('Password123'),
        ]);

        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'Password123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertGuest('web');

        // Storefront customer authentication remains independent from the
        // active admin guard and never redirects back into the admin panel.
        $this->get(route('login'))->assertOk();
        $this->get(route('account.dashboard'))->assertRedirect(route('login'));
    }


    public function test_admin_session_survives_navigation_across_admin_pages(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'password' => Hash::make('Password123'),
        ]);

        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'Password123',
        ])->assertRedirect(route('admin.dashboard'));

        foreach ([
            route('admin.dashboard'),
            route('admin.products.index'),
            route('admin.categories.index'),
        ] as $url) {
            $this->get($url)->assertOk();
            $this->assertAuthenticatedAs($admin, 'admin');
        }
    }


    public function test_admin_session_survives_ajax_style_navigation_and_admin_action(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'password' => Hash::make('Password123'),
        ]);

        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'Password123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->withHeaders([
            'Accept' => 'text/html,application/xhtml+xml',
            'X-Requested-With' => 'XMLHttpRequest',
            'X-NextPlay-Admin-Navigation' => '1',
        ])->get(route('admin.products.index'))->assertOk();

        $this->assertAuthenticatedAs($admin, 'admin');

        $this->from(route('admin.dashboard'))
            ->withHeaders([
                'Accept' => 'text/html,application/xhtml+xml',
                'X-NextPlay-Admin-Navigation' => '1',
            ])
            ->post(route('admin.notifications.read-all'))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_stale_customer_session_cannot_erase_an_active_admin_session(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
            'auth_session_version' => 5,
        ]);

        $this->actingAs($admin, 'admin')
            ->actingAs($customer, 'web')
            ->withSession([
                EnforceCustomerSessionVersion::SESSION_KEY => 4,
            ])
            ->get(route('home'))
            ->assertRedirect(route('login'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertGuest('web');
    }


    public function test_customer_login_ignores_a_stale_admin_intended_url(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
            'password' => Hash::make('Password123'),
        ]);

        $this->withSession(['url.intended' => route('admin.dashboard')])
            ->post(route('login.store'), [
                'email' => $customer->email,
                'password' => 'Password123',
            ])
            ->assertRedirect(route('account.dashboard'));

        $this->assertAuthenticatedAs($customer, 'web');
        $this->assertGuest('admin');
        $this->assertFalse(session()->has('url.intended'));
    }

    public function test_customer_login_returns_to_the_product_the_customer_was_viewing(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
            'password' => Hash::make('Password123'),
        ]);
        $productUrl = route('products.show', ['slug' => 'custom-team-jersey']);

        $this->post(route('login.store'), [
            'email' => $customer->email,
            'password' => 'Password123',
            'redirect' => $productUrl,
        ])->assertRedirect($productUrl);

        $this->assertAuthenticatedAs($customer, 'web');
        $this->assertGuest('admin');
    }

    public function test_customer_session_cannot_open_admin_panel(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
        ]);

        $this->withSession([
            EnforceCustomerSessionVersion::SESSION_KEY => (int) $customer->auth_session_version,
        ])->actingAs($customer, 'web')
            ->get(route('admin.dashboard'))
            ->assertNotFound();
    }

    public function test_customer_session_cannot_see_or_submit_the_admin_login_page(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
            'password' => Hash::make('Password123'),
        ]);

        $this->withSession([
            EnforceCustomerSessionVersion::SESSION_KEY => (int) $customer->auth_session_version,
        ])->actingAs($customer, 'web')
            ->get(route('admin.login'))
            ->assertNotFound();

        $this->withSession([
            EnforceCustomerSessionVersion::SESSION_KEY => (int) $customer->auth_session_version,
        ])->actingAs($customer, 'web')
            ->post(route('admin.login.store'), [
                'email' => $customer->email,
                'password' => 'Password123',
            ])
            ->assertNotFound();

        $this->assertAuthenticatedAs($customer, 'web');
        $this->assertGuest('admin');
    }

    public function test_customer_registration_persists_a_hashed_database_account_used_by_login(): void
    {
        Queue::fake();

        $this->post(route('register.store'), [
            'name' => 'NextPlay Customer',
            'email' => 'CUSTOMER@EXAMPLE.COM',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'terms' => '1',
            'website' => '',
        ])->assertRedirect(route('verification.notice'));

        $customer = User::query()->where('email', 'customer@example.com')->firstOrFail();

        $this->assertSame('customer', $customer->role);
        $this->assertTrue($customer->is_active);
        $this->assertTrue(Hash::check('Password123', $customer->password));
        $this->assertNotSame('Password123', $customer->password);
        $this->assertNull($customer->email_verified_at);

        $customer->markEmailAsVerified();

        $this->post(route('logout'))->assertRedirect(route('home'));

        $this->post(route('login.store'), [
            'email' => ' Customer@Example.Com ',
            'password' => 'Password123',
        ])->assertRedirect(route('account.dashboard'));

        $this->assertAuthenticatedAs($customer->fresh(), 'web');
        $this->assertNotNull($customer->fresh()->last_login_at);
    }

}
