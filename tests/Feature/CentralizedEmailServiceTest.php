<?php

namespace Tests\Feature;

use App\Contracts\EmailService;
use App\Data\EmailMessage;
use App\Jobs\SendTransactionalEmail;
use App\Mail\TransactionalEmail;
use App\Models\User;
use App\Services\Email\CentralEmailService;
use App\Services\Email\TransactionalEmailManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class CentralizedEmailServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_contract_resolves_to_the_central_service(): void
    {
        $this->assertInstanceOf(CentralEmailService::class, app(EmailService::class));
    }

    public function test_central_service_queues_one_provider_agnostic_email_job_when_queue_mode_is_explicit(): void
    {
        Queue::fake();
        config()->set('transactional_email.delivery.mode', 'queue');
        config()->set('transactional_email.queue.enabled', true);

        app(EmailService::class)->queue(new EmailMessage(
            key: 'test.central-email',
            recipients: [['email' => 'customer@example.com', 'name' => 'Customer']],
            subject: 'Central email test',
            heading: 'Central email test',
        ));

        Queue::assertPushed(SendTransactionalEmail::class, function (SendTransactionalEmail $job): bool {
            return $job->message->key === 'test.central-email'
                && data_get($job->message->recipients, '0.email') === 'customer@example.com';
        });
    }


    public function test_critical_email_verification_sends_immediately_when_sync_is_enabled(): void
    {
        Mail::fake();
        config()->set('transactional_email.enabled', true);
        config()->set('transactional_email.mailer', 'array');
        config()->set('transactional_email.critical.email_verification_sync', true);

        $customer = User::factory()->unverified()->create([
            'role' => 'customer',
            'is_active' => true,
        ]);

        app(TransactionalEmailManager::class)->emailVerification($customer);

        Mail::assertSent(TransactionalEmail::class, function (TransactionalEmail $mail) use ($customer): bool {
            return $mail->emailMessage->key === 'customer.email-verification'
                && data_get($mail->emailMessage->recipients, '0.email') === $customer->email;
        });
    }

    public function test_production_rejects_a_non_delivery_log_mailer(): void
    {
        $this->app['env'] = 'production';
        config()->set('transactional_email.enabled', true);
        config()->set('transactional_email.mailer', 'log');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('non-delivery mailer');

        app(EmailService::class)->sendNow(new EmailMessage(
            key: 'test.production-log-mailer',
            recipients: [['email' => 'customer@example.com']],
            subject: 'Must not silently log in production',
            heading: 'Must not silently log in production',
        ));
    }

    public function test_send_now_uses_the_shared_transactional_mailable(): void
    {
        Mail::fake();
        config()->set('transactional_email.enabled', true);
        config()->set('transactional_email.mailer', 'array');

        app(EmailService::class)->sendNow(new EmailMessage(
            key: 'test.send-now',
            recipients: [['email' => 'customer@example.com']],
            subject: 'Central email test',
            heading: 'Central email test',
        ));

        Mail::assertSent(TransactionalEmail::class, function (TransactionalEmail $mail): bool {
            return $mail->emailMessage->key === 'test.send-now';
        });
    }
}
