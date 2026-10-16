<?php

namespace Tests\Feature;

use App\Contracts\EmailService;
use App\Data\EmailMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class E2aMailTransportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('mail.mailers.e2a', [
            'transport' => 'e2a',
            'api_key' => 'test-api-key',
            'sender' => 'nextplayspoartswear@agents.e2a.dev',
            'api_url' => 'https://api.e2a.dev/v1',
            'timeout' => 5,
        ]);
        config()->set('mail.from.address', 'nextplayspoartswear@agents.e2a.dev');
        config()->set('mail.from.name', 'NextPlay Sportswear');
        config()->set('transactional_email.mailer', 'e2a');
        config()->set('transactional_email.enabled', true);
    }

    public function test_e2a_mailer_resolves_as_custom_transport_instead_of_https_dsn(): void
    {
        // Laravel treats mailers.*.url as a transport DSN and would otherwise
        // replace the e2a transport name with the https URL scheme.
        $this->assertArrayNotHasKey('url', config('mail.mailers.e2a'));
        $this->assertSame('https://api.e2a.dev/v1', config('mail.mailers.e2a.api_url'));
        $this->assertInstanceOf(
            \App\Services\Email\E2aTransport::class,
            Mail::mailer('e2a')->getSymfonyTransport(),
        );
    }

    public function test_e2a_uses_registered_agent_and_preserves_subject_html_recipients_and_reply_to(): void
    {
        Http::fake(['api.e2a.dev/*' => Http::response([
            'status' => 'accepted', 'message_id' => 'msg_001',
        ], 202)]);

        app(EmailService::class)->sendNow(new EmailMessage(
            key: 'test.e2a',
            recipients: [['email' => 'customer@example.com', 'name' => 'Customer']],
            subject: 'NextPlay test',
            heading: 'Welcome!',
            introLines: ['This is a NextPlay test.'],
            replyTo: 'support@example.com',
        ));

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.e2a.dev/v1/agents/nextplayspoartswear%40agents.e2a.dev/messages'
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer test-api-key')
                && filled($request->header('Idempotency-Key')[0] ?? '')
                && $request['to'] === ['Customer <customer@example.com>']
                && $request['subject'] === 'NextPlay test'
                && str_contains((string) $request['html'], 'Welcome!')
                && str_contains((string) $request['text'], 'NextPlay test.')
                && $request['reply_to'] === 'support@example.com';
        });
    }

    public function test_retry_of_one_logical_email_uses_the_same_idempotency_key(): void
    {
        Http::fake(['api.e2a.dev/*' => Http::response([
            'status' => 'accepted', 'message_id' => 'msg_002',
        ], 202)]);

        $message = new EmailMessage(
            key: 'test.retry',
            recipients: [['email' => 'customer@example.com']],
            subject: 'Retry safety',
            heading: 'Retry safety',
        );

        app(EmailService::class)->sendNow($message);
        app(EmailService::class)->sendNow(unserialize(serialize($message)));

        $keys = array_map(static fn ($request) => $request->header('Idempotency-Key')[0], Http::recorded()->map(static fn ($pair) => $pair[0])->all());
        $this->assertCount(2, $keys);
        $this->assertSame($keys[0], $keys[1]);
    }

    public function test_provider_rejection_and_held_mail_are_never_reported_as_sent(): void
    {
        Http::fake(['api.e2a.dev/*' => Http::response([
            'error' => ['code' => 'external_sending_not_enabled'],
        ], 403)]);

        try {
            app(EmailService::class)->sendNow($this->message());
            $this->fail('A rejected provider request must throw.');
        } catch (\Symfony\Component\Mailer\Exception\TransportException $exception) {
            $this->assertStringContainsString('403', $exception->getMessage());
            $this->assertStringNotContainsString('test-api-key', $exception->getMessage());
        }

        Http::fake(['api.e2a.dev/*' => Http::response([
            'status' => 'pending_review', 'message_id' => 'msg_003',
        ], 202)]);

        $this->expectException(\Symfony\Component\Mailer\Exception\TransportException::class);
        $this->expectExceptionMessage('pending_review');
        app(EmailService::class)->sendNow($this->message());
    }

    public function test_misconfigured_e2a_fails_fast_but_other_mailers_stay_available(): void
    {
        config()->set('mail.mailers.e2a.api_key', null);
        try {
            app(EmailService::class)->sendNow($this->message());
            $this->fail('Missing E2A credentials must be detected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('E2A_API_KEY', $exception->getMessage());
        }

        config()->set('transactional_email.mailer', 'array');
        app(EmailService::class)->sendNow($this->message());
        $this->assertSame('array', config('transactional_email.mailer'));
    }

    private function message(): EmailMessage
    {
        return new EmailMessage(
            key: 'test.e2a',
            recipients: [['email' => 'customer@example.com']],
            subject: 'Test',
            heading: 'Test',
        );
    }
}
