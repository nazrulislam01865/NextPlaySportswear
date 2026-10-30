<?php

namespace Tests\Feature\Storefront;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class RegistrationOtpRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config()->set('security.registration_otp_preview', true);
        config()->set('transactional_email.enabled', false);
    }

    public function test_four_independent_registration_requests_from_one_ip_are_not_blocked(): void
    {
        // The old throttle:3,1 returned an HTTP 429 on the fourth request
        // even when customers on the same network used different emails.
        foreach (range(1, 4) as $number) {
            $this->post(route('register.email.send'), [
                'email' => "shared-network-{$number}@example.com",
            ])->assertRedirect(route('register'));

            $this->post(route('register.email.change'))->assertRedirect(route('register'));
        }

        $this->get(route('register'))->assertOk()->assertSee('Create your account');
        Mail::assertNothingSent();
    }

    public function test_excessive_code_requests_return_inline_email_error_instead_of_raw_429(): void
    {
        // Simulates successive registration challenges from one IP. The
        // shared-IP cap remains, but must return a usable inline error.
        foreach (range(1, 20) as $number) {
            $this->post(route('register.email.send'), [
                'email' => "ip-cap-{$number}@example.com",
            ])->assertRedirect(route('register'));
            $this->post(route('register.email.change'))->assertRedirect(route('register'));
        }

        $this->post(route('register.email.send'), [
            'email' => 'ip-cap-21@example.com',
        ])->assertRedirect(route('register'))
            ->assertSessionHasErrors([
                'email' => 'Too many verification code requests. Please wait a moment before trying again.',
            ]);

        $this->get(route('register'))->assertOk()
            ->assertSee('Too many verification code requests. Please wait a moment before trying again.');
    }

    public function test_many_wrong_otp_attempts_still_show_form_error_not_429_page(): void
    {
        $this->post(route('register.email.send'), ['email' => 'wrong-otp@example.com'])
            ->assertRedirect(route('register'));

        foreach (range(1, 31) as $number) {
            $this->post(route('register.email.verify'), ['code' => '000000'])
                ->assertRedirect(route('register'))
                ->assertSessionHasErrors('code');
        }

        $this->get(route('register'))->assertOk()
            ->assertSee('Too many verification attempts. Please wait a moment before trying again.');
    }

    public function test_resend_form_prevents_double_submit_and_can_show_email_errors(): void
    {
        $this->post(route('register.email.send'), ['email' => 'preview-otp@example.com'])
            ->assertRedirect(route('register'));

        $this->get(route('register'))->assertOk()
            ->assertSee('data-registration-resend', false)
            ->assertSee('data-single-submit', false);

        $this->post(route('register.email.send'), ['email' => 'preview-otp@example.com'])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('email');

        $this->get(route('register'))->assertOk()
            ->assertSee('Please wait before requesting another verification code.');
    }
}
