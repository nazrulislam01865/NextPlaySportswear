<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Http\Middleware\EnforceCustomerSessionVersion;
use App\Jobs\SendTransactionalEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmailVerificationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('transactional_email.enabled', true);
        config()->set('transactional_email.queue.enabled', true);
    }

    public function test_verification_status_and_resend_use_existing_customer_session(): void
    {
        Queue::fake();
        $customer = User::factory()->unverified()->create([
            'role' => 'customer',
            'is_active' => true,
            'auth_session_version' => 0,
        ]);

        $this->actingAs($customer, 'web')
            ->withSession([EnforceCustomerSessionVersion::SESSION_KEY => 0])
            ->getJson('/api/v1/auth/verification/status')
            ->assertOk()
            ->assertJsonPath('data.email_verified', false);

        $this->actingAs($customer, 'web')
            ->withSession([EnforceCustomerSessionVersion::SESSION_KEY => 0])
            ->postJson('/api/v1/auth/verification/resend')
            ->assertOk();

        Queue::assertPushed(SendTransactionalEmail::class, fn (SendTransactionalEmail $job): bool => $job->message->key === 'customer.email-verification');
    }
}
