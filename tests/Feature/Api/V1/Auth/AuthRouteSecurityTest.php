<?php

namespace Tests\Feature\Api\V1\Auth;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthRouteSecurityTest extends TestCase
{
    public function test_auth_api_uses_first_party_web_session_middleware_and_existing_rate_limits(): void
    {
        $login = Route::getRoutes()->getByName('api.v1.auth.login');
        $me = Route::getRoutes()->getByName('api.v1.auth.me');
        $resend = Route::getRoutes()->getByName('api.v1.auth.verification.resend');

        $this->assertNotNull($login);
        $this->assertContains('web', $login->gatherMiddleware());
        $this->assertContains('guest:web', $login->gatherMiddleware());
        $this->assertContains('throttle:storefront-login', $login->gatherMiddleware());

        $this->assertNotNull($me);
        $this->assertContains('web', $me->gatherMiddleware());
        $this->assertContains('auth:web', $me->gatherMiddleware());
        $this->assertContains('customer', $me->gatherMiddleware());

        $this->assertNotNull($resend);
        $this->assertContains('throttle:email-verification-send', $resend->gatherMiddleware());

        $this->assertTrue((bool) config('session.http_only'));
        $this->assertContains((string) config('session.same_site'), ['lax', 'strict']);
    }
}
