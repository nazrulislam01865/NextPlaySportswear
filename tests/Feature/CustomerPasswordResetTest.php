<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceCustomerSessionVersion;
use App\Jobs\SendTransactionalEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CustomerPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Tests keep password-reset delivery on the queue so no real mail
        // transport is contacted. Production defaults to synchronous delivery
        // for this security-critical message.
        config()->set('transactional_email.enabled', true);
        config()->set('transactional_email.delivery.mode', 'queue');
        config()->set('transactional_email.queue.enabled', true);
        config()->set('transactional_email.critical.password_reset_sync', false);
    }

    public function test_forgot_password_page_is_available_to_guests(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Reset your password')
            ->assertSee('Send Password Reset Link');
    }

    public function test_active_customer_can_request_a_password_reset_link(): void
    {
        Queue::fake();

        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
        ]);

        $this->post(route('password.email'), [
            'email' => strtoupper($customer->email),
        ])->assertSessionHas(
            'status',
            'If an active customer account matches that email, a password reset link will be sent. The link expires in 15 minutes and can only be used once.'
        );

        Queue::assertPushed(SendTransactionalEmail::class, function (SendTransactionalEmail $job) use ($customer): bool {
            return $job->message->key === 'customer.password-reset'
                && data_get($job->message->recipients, '0.email') === strtolower($customer->email)
                && str_contains((string) $job->message->actionUrl, '/reset-password/')
                && str_contains((string) $job->message->actionUrl, 'email=');
        });

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => strtolower($customer->email),
        ]);
    }

    public function test_unknown_admin_and_inactive_accounts_get_generic_response_without_customer_reset_mail(): void
    {
        Queue::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $inactiveCustomer = User::factory()->create([
            'role' => 'customer',
            'is_active' => false,
        ]);

        foreach ([
            'unknown@example.com',
            $admin->email,
            $inactiveCustomer->email,
        ] as $email) {
            $this->post(route('password.email'), [
                'email' => $email,
            ])->assertSessionHas('status');
        }

        Queue::assertNothingPushed();
    }

    public function test_valid_customer_reset_link_opens_the_reset_form(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
        ]);

        $token = Password::broker('users')->createToken($customer);

        $this->get(route('password.reset', [
            'token' => $token,
            'email' => $customer->email,
        ]))
            ->assertOk()
            ->assertSee('Choose a new password')
            ->assertSee($customer->email)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('aria-controls="password"', false)
            ->assertSee('aria-controls="password_confirmation"', false);
    }

    public function test_invalid_or_expired_reset_link_is_rejected_before_showing_the_form(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
        ]);

        $this->get(route('password.reset', [
            'token' => 'invalid-token',
            'email' => $customer->email,
        ]))
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('email');
    }

    public function test_admin_and_inactive_customer_tokens_cannot_use_the_storefront_reset_form(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $inactiveCustomer = User::factory()->create([
            'role' => 'customer',
            'is_active' => false,
        ]);

        foreach ([$admin, $inactiveCustomer] as $user) {
            $token = Password::broker('users')->createToken($user);

            $this->get(route('password.reset', [
                'token' => $token,
                'email' => $user->email,
            ]))
                ->assertRedirect(route('password.request'))
                ->assertSessionHasErrors('email');
        }
    }

    public function test_customer_can_reset_password_with_a_valid_token_and_receives_security_notification(): void
    {
        Queue::fake();

        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
            'password' => Hash::make('OldPassword123'),
        ]);

        $oldRememberToken = $customer->remember_token;
        $token = Password::broker('users')->createToken($customer);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $customer->email,
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $customer->refresh();

        $this->assertTrue(Hash::check('NewPassword123', $customer->password));
        $this->assertNotSame($oldRememberToken, $customer->remember_token);
        $this->assertSame(1, (int) $customer->auth_session_version);
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $customer->email,
        ]);

        Queue::assertPushed(SendTransactionalEmail::class, function (SendTransactionalEmail $job) use ($customer): bool {
            return $job->message->key === 'customer.password-changed'
                && data_get($job->message->recipients, '0.email') === $customer->email;
        });
    }

    public function test_reset_token_cannot_be_reused(): void
    {
        Queue::fake();

        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
            'password' => Hash::make('OldPassword123'),
        ]);

        $token = Password::broker('users')->createToken($customer);

        $payload = [
            'token' => $token,
            'email' => $customer->email,
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ];

        $this->post(route('password.store'), $payload)
            ->assertRedirect(route('login'));

        $this->post(route('password.store'), [
            ...$payload,
            'password' => 'AnotherPassword456',
            'password_confirmation' => 'AnotherPassword456',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('NewPassword123', $customer->fresh()->password));
    }

    public function test_weak_or_mismatched_password_is_not_accepted(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
        ]);

        $token = Password::broker('users')->createToken($customer);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $customer->email,
            'password' => 'password',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors('password');
    }

    public function test_stale_customer_session_is_revoked_after_security_version_changes(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
            'auth_session_version' => 2,
        ]);

        $this->actingAs($customer, 'web')
            ->withSession([
                EnforceCustomerSessionVersion::SESSION_KEY => 1,
            ])
            ->get(route('account.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertGuest('web');
    }

    public function test_current_customer_session_version_remains_authenticated(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
            'auth_session_version' => 3,
        ]);

        $this->actingAs($customer, 'web')
            ->withSession([
                EnforceCustomerSessionVersion::SESSION_KEY => 3,
            ])
            ->get(route('account.dashboard'))
            ->assertOk();

        $this->assertAuthenticatedAs($customer, 'web');
    }

    public function test_password_reset_link_requests_are_limited_by_ip_across_different_emails(): void
    {
        Queue::fake();

        config()->set('security.password_reset.request.ip_per_minute', 2);
        config()->set('security.password_reset.request.ip_per_hour', 100);
        config()->set('security.password_reset.request.email_per_minute', 100);
        config()->set('security.password_reset.request.email_per_hour', 100);

        $this->post(route('password.email'), ['email' => 'first@example.com'])
            ->assertSessionHas('status');

        $this->post(route('password.email'), ['email' => 'second@example.com'])
            ->assertSessionHas('status');

        $this->post(route('password.email'), ['email' => 'third@example.com'])
            ->assertStatus(429);

        Queue::assertNothingPushed();
    }

    public function test_password_reset_link_requests_are_limited_by_email_across_ips(): void
    {
        Queue::fake();

        config()->set('security.password_reset.request.ip_per_minute', 100);
        config()->set('security.password_reset.request.ip_per_hour', 100);
        config()->set('security.password_reset.request.email_per_minute', 2);
        config()->set('security.password_reset.request.email_per_hour', 100);

        $email = 'unknown@example.com';

        $this->withServerVariables(['REMOTE_ADDR' => '10.10.0.1'])
            ->post(route('password.email'), ['email' => $email])
            ->assertSessionHas('status');

        $this->withServerVariables(['REMOTE_ADDR' => '10.10.0.2'])
            ->post(route('password.email'), ['email' => $email])
            ->assertSessionHas('status');

        $this->withServerVariables(['REMOTE_ADDR' => '10.10.0.3'])
            ->post(route('password.email'), ['email' => $email])
            ->assertStatus(429);

        Queue::assertNothingPushed();
    }


    public function test_predeployment_customer_session_without_security_version_is_revoked_once(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
            'auth_session_version' => 0,
        ]);

        $this->actingAs($customer, 'web')
            ->get(route('account.dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest('web');
    }

}
