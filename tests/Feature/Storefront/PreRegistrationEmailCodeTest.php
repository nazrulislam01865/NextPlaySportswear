<?php

namespace Tests\Feature\Storefront;

use App\Http\Controllers\Storefront\Auth\RegistrationEmailCodeController;
use App\Mail\TransactionalEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class PreRegistrationEmailCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config()->set('transactional_email.enabled', true);
        config()->set('transactional_email.mailer', 'array');
        config()->set('security.email_verification.enabled', true);
    }

    private function start(string $email): string
    {
        $this->post(route('register.email.send'), ['email' => $email])
            ->assertRedirect(route('register'));
        $sent = Mail::assertSent(TransactionalEmail::class, 1);
        $message = $sent->first()->emailMessage;
        $this->assertSame('customer.pre-registration-code', $message->key);
        $code = (string) $message->details['Verification Code'];
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        return $code;
    }

    public function test_local_preview_opens_code_screen_without_email_delivery(): void
    {
        config()->set('security.registration_otp_preview', true);
        config()->set('transactional_email.enabled', false);

        $this->post(route('register.email.send'), ['email' => 'preview@example.com'])
            ->assertRedirect(route('register'));
        Mail::assertNothingSent();
        $this->assertDatabaseMissing('users', ['email' => 'preview@example.com']);
        $this->get(route('register'))->assertOk()
            ->assertSee('Verify your email')
            ->assertSee('Your code expires in 10 minutes.')
            ->assertDontSee('123456')
            ->assertDontSee('Local preview');

        // The OTP is still checked. Preview mode skips delivery, not verification.
        $this->post(route('register.email.verify'), ['code' => '654321'])
            ->assertSessionHasErrors('code');
        $this->post(route('register.email.verify'), ['code' => '123456'])
            ->assertRedirect(route('register'));
        $this->get(route('register'))->assertOk()->assertSee('Complete your account');
    }

    public function test_local_preview_setting_is_ignored_in_production(): void
    {
        config()->set('security.registration_otp_preview', true);
        app()->detectEnvironment(fn () => 'production');
        try {
            $this->assertFalse(RegistrationEmailCodeController::localPreviewEnabled());
        } finally {
            app()->detectEnvironment(fn () => 'testing');
        }
    }

    public function test_registration_requires_preverified_email_and_creates_no_user_on_code_request(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Test Customer', 'email' => 'test@example.com',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!',
            'terms' => '1',
        ])->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);

        $this->get(route('register'))->assertOk()->assertSee('Create your account');
        $this->start('test@example.com');
        $this->get(route('register'))->assertOk()->assertSee('Verify your email');
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_correct_code_unlocks_account_and_registration_redirects_to_dashboard(): void
    {
        $code = $this->start('verified@example.com');
        $this->post(route('register.email.verify'), ['code' => $code])->assertRedirect(route('register'));
        $this->get(route('register'))->assertOk()->assertSee('Complete your account');
        $this->post(route('register.store'), [
            'name' => 'Verified Customer', 'email' => 'verified@example.com',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!',
            'terms' => '1',
        ])->assertRedirect(route('account.dashboard'));
        $user = User::query()->where('email', 'verified@example.com')->firstOrFail();
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertNull(session(RegistrationEmailCodeController::SESSION_KEY));
    }

    public function test_wrong_code_does_not_unlock_registration(): void
    {
        $this->start('wrong@example.com');
        $this->post(route('register.email.verify'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->get(route('register'))->assertSee('Verify your email');
        $this->post(route('register.store'), [
            'name' => 'Wrong Customer', 'email' => 'wrong@example.com',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!',
            'terms' => '1',
        ])->assertSessionHasErrors('email');
    }

    public function test_verified_email_cannot_be_swapped_during_account_creation(): void
    {
        $code = $this->start('first@example.com');
        $this->post(route('register.email.verify'), ['code' => $code]);
        $this->post(route('register.store'), [
            'name' => 'Wrong Customer', 'email' => 'second@example.com',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!',
            'terms' => '1',
        ])->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => 'second@example.com']);
    }

    public function test_expired_code_and_cooldown_are_enforced(): void
    {
        $this->start('expiry@example.com');
        $this->post(route('register.email.send'), ['email' => 'expiry@example.com'])
            ->assertSessionHasErrors('email');
        $this->travel(11)->minutes();
        $this->get(route('register'))->assertSee('Create your account');
        $this->post(route('register.email.verify'), ['code' => '123456'])->assertSessionHasErrors('code');
    }
}
