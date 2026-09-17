<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Jobs\SendTransactionalEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PasswordRecoveryApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('transactional_email.enabled', true);
        config()->set('transactional_email.queue.enabled', true);
        config()->set('transactional_email.critical.password_reset_sync', false);
    }

    public function test_forgot_password_response_is_generic_for_existing_and_unknown_accounts(): void
    {
        Queue::fake();
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);

        $existing = $this->postJson('/api/v1/auth/forgot-password', ['email' => $customer->email])->assertOk();
        $unknown = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'missing@example.com'])->assertOk();

        $this->assertSame($existing->json('data.message'), $unknown->json('data.message'));
        $existing->assertJsonStructure(['data' => ['message'], 'meta', 'request_id']);
        Queue::assertPushed(SendTransactionalEmail::class);
    }

    public function test_customer_can_reset_password_through_api_and_old_password_no_longer_works(): void
    {
        Queue::fake();
        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
            'password' => Hash::make('OldPassword123'),
            'auth_session_version' => 0,
        ]);
        $token = Password::broker('users')->createToken($customer);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => $customer->email,
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertOk();

        $customer->refresh();
        $this->assertTrue(Hash::check('NewPassword123', $customer->password));
        $this->assertSame(1, (int) $customer->auth_session_version);
    }
}
