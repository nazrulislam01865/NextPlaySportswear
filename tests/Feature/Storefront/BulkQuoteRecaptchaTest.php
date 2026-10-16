<?php

namespace Tests\Feature\Storefront;

use App\Rules\VerifiedRecaptcha;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class BulkQuoteRecaptchaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.recaptcha.site_key', 'test-site-key');
        config()->set('services.recaptcha.secret_key', 'test-secret-key');
    }

    public function test_valid_captcha_is_accepted_after_google_verification(): void
    {
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'hostname' => request()->getHost(),
            ]),
        ]);

        $this->assertTrue($this->isValid('valid-token'));
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === 'https://www.google.com/recaptcha/api/siteverify'
            && $request['secret'] === 'test-secret-key'
            && $request['response'] === 'valid-token');
    }

    public function test_missing_captcha_is_rejected_without_calling_google(): void
    {
        Http::fake();

        $this->assertFalse($this->isValid(null));
        Http::assertNothingSent();
    }

    public function test_invalid_captcha_is_rejected(): void
    {
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => false]),
        ]);

        $this->assertFalse($this->isValid('invalid-token'));
    }

    public function test_token_from_another_hostname_is_rejected(): void
    {
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'hostname' => 'unrelated-site.example',
            ]),
        ]);

        $this->assertFalse($this->isValid('other-site-token'));
    }

    public function test_captcha_verification_service_failure_rejects_submission(): void
    {
        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response([], 503),
        ]);

        $this->assertFalse($this->isValid('token'));
    }

    public function test_missing_configuration_rejects_submission_without_contacting_google(): void
    {
        config()->set('services.recaptcha.secret_key', null);
        Http::fake();

        $this->assertFalse($this->isValid('token'));
        Http::assertNothingSent();
    }

    private function isValid(?string $token): bool
    {
        return Validator::make(['g-recaptcha-response' => $token], [
            'g-recaptcha-response' => ['bail', 'required', 'string', new VerifiedRecaptcha()],
        ])->passes();
    }
}
