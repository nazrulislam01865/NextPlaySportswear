<?php

namespace Tests\Feature\Storefront;

use App\Http\Controllers\Storefront\Auth\RegistrationEmailCodeController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class RegistrationFinalPasswordAndThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config()->set('security.registration_otp_preview', true);
        config()->set('transactional_email.enabled', false);
    }

    private function verifiedEmail(string $email): void
    {
        $this->post(route('register.email.send'), ['email' => $email])->assertRedirect(route('register'));
        $this->post(route('register.email.verify'), ['code' => '123456'])->assertRedirect(route('register'));
    }

    public function test_account_step_uses_short_prototype_consent_text_and_updated_hint(): void
    {
        $this->verifiedEmail('consent@example.com');
        $this->get(route('register'))->assertOk()
            ->assertSee('I agree to the', false)
            ->assertSee('Use at least 8 characters with a letter, a number, and a special character.')
            ->assertSee('data-password-rule-feedback', false)
            ->assertDontSee('By continuing, you agree to NextPlay Sportswear');
    }

    public function test_password_requires_letter_number_special_character_and_match(): void
    {
        $this->verifiedEmail('strong@example.com');
        $this->get(route('register'))->assertOk();
        foreach (['password123', 'password!!!', '12345678!', 'abc1!'] as $invalid) {
            $this->post(route('register.store'), [
                'name' => 'Test Customer',
                'email' => 'strong@example.com',
                'password' => $invalid,
                'password_confirmation' => $invalid,
                'terms' => '1',
            ])->assertRedirect(route('register'))->assertSessionHasErrors('password');
        }
        // A valid password must still reject mismatched confirmation.
        $this->post(route('register.store'), [
            'name' => 'Test Customer', 'email' => 'strong@example.com',
            'password' => 'Password123!', 'password_confirmation' => 'Different123!',
            'terms' => '1',
        ])->assertSessionHasErrors('password');
        $this->post(route('register.store'), [
            'name' => 'Test Customer', 'email' => 'strong@example.com',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!',
            'terms' => '1',
        ])->assertRedirect(route('account.dashboard'));
        $this->assertDatabaseHas('users', ['email' => 'strong@example.com']);
    }

    public function test_repeated_validation_retries_do_not_show_raw_429(): void
    {
        $this->verifiedEmail('retries@example.com');
        $this->get(route('register'))->assertOk();
        foreach (range(1, 7) as $n) {
            $this->post(route('register.store'), [
                'name' => 'Test Customer', 'email' => 'retries@example.com',
                'password' => 'password123', 'password_confirmation' => 'password123',
                'terms' => '1',
            ])->assertRedirect(route('register'))->assertSessionHasErrors('password');
        }
        $this->get(route('register'))->assertOk()->assertSee('Complete your account');
        $this->assertSame('throttle:registration-complete', collect(Route::getRoutes()->getByName('register.store')->gatherMiddleware())
            ->first(fn (string $middleware) => str_starts_with($middleware, 'throttle:')));
    }
}
