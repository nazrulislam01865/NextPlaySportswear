<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceCustomerSessionVersion;
use App\Jobs\SendTransactionalEmail;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CustomerEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('transactional_email.enabled', true);
        config()->set('transactional_email.delivery.mode', 'queue');
        config()->set('transactional_email.queue.enabled', true);
        config()->set('transactional_email.critical.email_verification_sync', false);
        config()->set('security.email_verification.enabled', true);
        config()->set('security.email_verification.expire_minutes', 60);
    }

    public function test_preverified_registration_succeeds_when_legacy_verification_is_disabled(): void
    {
        Queue::fake();
        config()->set('security.email_verification.enabled', false);
        config()->set('transactional_email.enabled', false);

        $this->withSession([
            \App\Http\Controllers\Storefront\Auth\RegistrationEmailCodeController::SESSION_KEY => [
                'email' => 'auto@example.com', 'verified_at' => time(),
            ],
        ])->post(route('register.store'), [
            'name' => 'Automatic Verification Customer',
            'email' => ' AUTO@EXAMPLE.COM ',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
            'website' => '',
        ])->assertRedirect(route('account.dashboard'));

        $customer = User::query()->where('email', 'auto@example.com')->firstOrFail();
        $this->assertNotNull($customer->email_verified_at);
        $this->assertAuthenticatedAs($customer, 'web');
        Queue::assertNothingPushed();
    }

    public function test_disabled_verification_repairs_legacy_unverified_customer_on_login(): void
    {
        config()->set('security.email_verification.enabled', false);

        $customer = User::factory()->unverified()->create([
            'role' => 'customer',
            'is_active' => true,
            'password' => 'Password123',
        ]);

        $this->post(route('login.store'), [
            'email' => $customer->email,
            'password' => 'Password123',
        ])
            ->assertRedirect(route('account.dashboard'))
            ->assertSessionHas('status');

        $this->assertNotNull($customer->fresh()->email_verified_at);
        $this->assertAuthenticatedAs($customer->fresh(), 'web');
    }

    public function test_preverified_registration_does_not_send_a_second_verification_link(): void
    {
        Queue::fake();
        $this->withSession([
            \App\Http\Controllers\Storefront\Auth\RegistrationEmailCodeController::SESSION_KEY => [
                'email' => 'verify@example.com', 'verified_at' => time(),
            ],
        ])->post(route('register.store'), [
            'name' => 'Verification Customer',
            'email' => ' VERIFY@EXAMPLE.COM ',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
            'website' => '',
        ])->assertRedirect(route('account.dashboard'));

        $customer = User::query()->where('email', 'verify@example.com')->firstOrFail();
        $this->assertNotNull($customer->email_verified_at);
        $this->assertAuthenticatedAs($customer, 'web');
        Queue::assertNotPushed(SendTransactionalEmail::class, function (SendTransactionalEmail $job): bool {
            return $job->message->key === 'customer.email-verification';
        });
    }

    public function test_welcome_email_is_queued_only_after_first_successful_verification(): void
    {
        Queue::fake();

        $customer = User::factory()->unverified()->create([
            'role' => 'customer',
            'is_active' => true,
            'auth_session_version' => 0,
            'welcome_email_sent_at' => null,
        ]);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $customer->id,
                'hash' => sha1($customer->getEmailForVerification()),
            ]
        );

        $this->actingAs($customer, 'web')
            ->withSession([EnforceCustomerSessionVersion::SESSION_KEY => 0])
            ->get($url)
            ->assertRedirect(route('verification.success'));

        $customer->refresh();

        $this->assertNotNull($customer->email_verified_at);
        $this->assertNotNull($customer->welcome_email_sent_at);

        Queue::assertPushed(SendTransactionalEmail::class, function (SendTransactionalEmail $job) use ($customer): bool {
            return $job->message->key === 'customer.welcome'
                && data_get($job->message->recipients, '0.email') === $customer->email;
        });
        Queue::assertPushed(SendTransactionalEmail::class, 1);

        // Reopening the same valid link must not queue another welcome email.
        $this->actingAs($customer, 'web')
            ->withSession([EnforceCustomerSessionVersion::SESSION_KEY => 0])
            ->get($url)
            ->assertRedirect(route('verification.success'));

        Queue::assertPushed(SendTransactionalEmail::class, 1);
    }

    public function test_pre_registration_delivery_outage_never_creates_an_account(): void
    {
        Queue::fake();
        config()->set('transactional_email.enabled', false);

        $this->post(route('register.email.send'), ['email' => 'outage@example.com'])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'outage@example.com']);
        $this->assertGuest('web');
        Queue::assertNothingPushed();
    }

    public function test_unverified_customer_login_redirects_to_verification_notice(): void
    {
        $customer = User::factory()->unverified()->create([
            'role' => 'customer',
            'is_active' => true,
            'password' => 'Password123',
        ]);

        $this->post(route('login.store'), [
            'email' => $customer->email,
            'password' => 'Password123',
        ])
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status');

        $this->assertAuthenticatedAs($customer, 'web');
    }

    public function test_unverified_login_preserves_checkout_destination_until_verification_success(): void
    {
        $customer = User::factory()->unverified()->create([
            'role' => 'customer',
            'is_active' => true,
            'auth_session_version' => 0,
            'password' => 'Password123',
        ]);

        $checkoutUrl = route('checkout.index');

        $this->post(route('login.store'), [
            'email' => $customer->email,
            'password' => 'Password123',
            'redirect' => $checkoutUrl,
        ])->assertRedirect(route('verification.notice'));

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $customer->id,
                'hash' => sha1($customer->getEmailForVerification()),
            ]
        );

        $this->withSession([EnforceCustomerSessionVersion::SESSION_KEY => 0])
            ->get($verificationUrl)
            ->assertRedirect(route('verification.success'));

        $this->withSession([EnforceCustomerSessionVersion::SESSION_KEY => 0])
            ->get(route('verification.success'))
            ->assertOk()
            ->assertSee('Continue to Checkout')
            ->assertSee($checkoutUrl, false);
    }

    public function test_unverified_customer_is_blocked_from_account_and_checkout(): void
    {
        $customer = User::factory()->unverified()->create([
            'role' => 'customer',
            'is_active' => true,
            'auth_session_version' => 0,
        ]);

        $session = [EnforceCustomerSessionVersion::SESSION_KEY => 0];

        $this->actingAs($customer, 'web')
            ->withSession($session)
            ->get(route('account.dashboard'))
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($customer, 'web')
            ->withSession($session)
            ->get(route('checkout.index'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_valid_signed_verification_link_marks_email_verified_and_dispatches_verified_event(): void
    {
        Event::fake([Verified::class]);

        $customer = User::factory()->unverified()->create([
            'role' => 'customer',
            'is_active' => true,
            'auth_session_version' => 0,
        ]);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $customer->id,
                'hash' => sha1($customer->getEmailForVerification()),
            ]
        );

        $this->actingAs($customer, 'web')
            ->withSession([EnforceCustomerSessionVersion::SESSION_KEY => 0])
            ->get($url)
            ->assertRedirect(route('verification.success'))
            ->assertSessionHas('status', 'email-verified');

        $this->assertNotNull($customer->fresh()->email_verified_at);
        Event::assertDispatched(Verified::class);

        $this->actingAs($customer->fresh(), 'web')
            ->withSession([EnforceCustomerSessionVersion::SESSION_KEY => 0])
            ->get(route('verification.success'))
            ->assertOk()
            ->assertSee('Email verified successfully')
            ->assertSee($customer->email)
            ->assertSee('Go to My Account');
    }

    public function test_tampered_or_expired_verification_link_does_not_verify_customer(): void
    {
        $customer = User::factory()->unverified()->create([
            'role' => 'customer',
            'is_active' => true,
            'auth_session_version' => 0,
        ]);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinute(),
            [
                'id' => $customer->id,
                'hash' => sha1($customer->getEmailForVerification()),
            ]
        );

        $this->actingAs($customer, 'web')
            ->withSession([EnforceCustomerSessionVersion::SESSION_KEY => 0])
            ->get($url)
            ->assertForbidden();

        $this->assertNull($customer->fresh()->email_verified_at);
    }

    public function test_customer_can_resend_verification_email(): void
    {
        Queue::fake();

        $customer = User::factory()->unverified()->create([
            'role' => 'customer',
            'is_active' => true,
            'auth_session_version' => 0,
        ]);

        $this->actingAs($customer, 'web')
            ->withSession([EnforceCustomerSessionVersion::SESSION_KEY => 0])
            ->post(route('verification.send'))
            ->assertRedirect()
            ->assertSessionHas('status', 'verification-link-sent');

        Queue::assertPushed(SendTransactionalEmail::class, function (SendTransactionalEmail $job) use ($customer): bool {
            return $job->message->key === 'customer.email-verification'
                && data_get($job->message->recipients, '0.email') === $customer->email;
        });
    }

    public function test_changing_profile_email_requires_verification_again_and_sends_a_new_link(): void
    {
        Queue::fake();

        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
            'auth_session_version' => 0,
        ]);

        $this->actingAs($customer, 'web')
            ->withSession([EnforceCustomerSessionVersion::SESSION_KEY => 0])
            ->patch(route('account.profile.update'), [
                'name' => $customer->name,
                'email' => 'new-address@example.com',
                'phone' => '',
                'company_name' => '',
                'preferred_sport' => null,
                'marketing_consent' => '0',
            ])
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', 'verification-link-sent');

        $customer->refresh();

        $this->assertSame('new-address@example.com', $customer->email);
        $this->assertNull($customer->email_verified_at);

        Queue::assertPushed(SendTransactionalEmail::class, function (SendTransactionalEmail $job): bool {
            return $job->message->key === 'customer.email-verification'
                && data_get($job->message->recipients, '0.email') === 'new-address@example.com';
        });
    }

    public function test_changing_profile_email_stays_verified_when_verification_is_disabled(): void
    {
        Queue::fake();
        config()->set('security.email_verification.enabled', false);

        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
            'auth_session_version' => 0,
        ]);

        $this->actingAs($customer, 'web')
            ->withSession([EnforceCustomerSessionVersion::SESSION_KEY => 0])
            ->patch(route('account.profile.update'), [
                'name' => $customer->name,
                'email' => 'muted-verification@example.com',
                'phone' => '',
                'company_name' => '',
                'preferred_sport' => null,
                'marketing_consent' => '0',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $customer->refresh();

        $this->assertSame('muted-verification@example.com', $customer->email);
        $this->assertNotNull($customer->email_verified_at);

        Queue::assertNotPushed(SendTransactionalEmail::class, function (SendTransactionalEmail $job): bool {
            return $job->message->key === 'customer.email-verification';
        });
    }
}
