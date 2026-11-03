<?php

namespace Tests\Feature;

use App\Jobs\Payments\ProcessPaymentWebhook;
use App\Models\PaymentWebhookEvent;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\DTO\GatewayPaymentStatus;
use App\Payments\DTO\GatewayRedirect;
use App\Payments\DTO\GatewayRefundResult;
use App\Payments\DTO\GatewayWebhook;
use App\Payments\Exceptions\PaymentGatewayException;
use App\Services\Payments\PaymentAuditRecorder;
use App\Services\Payments\PaymentHealthSnapshot;
use App\Services\Payments\PaymentOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\Fakes\FakeStripeGateway;
use Tests\TestCase;

class PaymentOperationalAssurancePhase6Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('payments.gateways.stripe.driver', Phase6VerifiedGatewayFake::class);
        config()->set('payments.gateways.stripe.enabled', true);
        config()->set('payments.monitoring.alert_email', '');
    }

    public function test_signed_webhook_is_deduplicated_and_only_safe_fields_are_stored(): void
    {
        Queue::fake();
        $body = [
            'id' => 'evt_Phase6Safe123', 'type' => 'payment_intent.payment_failed',
            'livemode' => false,
            'data' => ['object' => [
                'id' => 'pi_Phase6Safe123',
                'metadata' => ['order_id' => '4', 'payment_id' => '8', 'private_note' => 'SECRET'],
                'billing_details' => ['email' => 'do-not-store@example.com'],
                'last_payment_error' => ['code' => 'card_declined', 'message' => 'SENSITIVE MESSAGE'],
            ]],
        ];
        $this->withHeader('Stripe-Signature', 'test-valid')
            ->postJson(route('webhooks.stripe'), $body)->assertOk();
        $this->withHeader('Stripe-Signature', 'test-valid')
            ->postJson(route('webhooks.stripe'), $body)->assertOk();
        $this->assertDatabaseCount('payment_webhook_events', 1);
        $record = PaymentWebhookEvent::query()->firstOrFail();
        $this->assertSame('pi_Phase6Safe123', data_get($record->payload, 'data.object.id'));
        $this->assertSame('card_declined', data_get($record->payload, 'data.object.last_payment_error.code'));
        $this->assertNull(data_get($record->payload, 'data.object.billing_details'));
        $this->assertNull(data_get($record->payload, 'data.object.metadata.private_note'));
        $this->assertNull(data_get($record->payload, 'data.object.last_payment_error.message'));
        Queue::assertPushed(ProcessPaymentWebhook::class);
    }

    public function test_oversized_webhook_body_is_rejected_before_queueing(): void
    {
        Queue::fake();
        config()->set('payments.webhooks.max_payload_bytes', 1024);
        $body = [
            'id' => 'evt_Phase6Large123', 'type' => 'refund.created',
            'unused_customer_content' => str_repeat('x', 1200),
        ];
        $this->withHeader('Stripe-Signature', 'test-valid')
            ->postJson(route('webhooks.stripe'), $body)->assertStatus(413);
        Queue::assertNothingPushed();
        $this->assertDatabaseCount('payment_webhook_events', 0);
    }

    public function test_repeated_event_id_with_a_different_signed_body_is_rejected(): void
    {
        Queue::fake();
        $first = ['id' => 'evt_Phase6Same123', 'type' => 'charge.refunded',
            'data' => ['object' => ['id' => 'ch_Phase6Same123']]];
        $this->withHeader('Stripe-Signature', 'test-valid')
            ->postJson(route('webhooks.stripe'), $first)->assertOk();
        $first['data']['object']['id'] = 'ch_Different123';
        $this->withHeader('Stripe-Signature', 'test-valid')
            ->postJson(route('webhooks.stripe'), $first)->assertStatus(409);
        $this->assertDatabaseCount('payment_webhook_events', 1);
    }

    public function test_unsigned_events_and_connected_account_events_are_rejected(): void
    {
        Queue::fake();
        $body = ['id' => 'evt_Phase6Guard123', 'type' => 'refund.created',
            'data' => ['object' => ['id' => 're_Phase6Guard123']]];
        $this->postJson(route('webhooks.stripe'), $body)->assertStatus(400);
        $this->withHeader('Stripe-Signature', 'test-valid')
            ->postJson(route('webhooks.stripe'), $body + ['account' => 'acct_other'])
            ->assertStatus(400);
        $this->assertDatabaseCount('payment_webhook_events', 0);
    }

    public function test_recovery_requeues_only_stale_events_without_touching_processed_events(): void
    {
        Queue::fake();
        $eligible = $this->webhook('evt_Phase6Old123', 'received', now()->subHour());
        $processed = $this->webhook('evt_Phase6Done123', 'processed', now()->subHour());
        $fresh = $this->webhook('evt_Phase6Fresh123', 'received', now());
        $this->artisan('payments:recover-webhooks')->assertExitCode(0);
        Queue::assertPushed(ProcessPaymentWebhook::class, 1);
        $this->assertNotNull($eligible->fresh()->last_dispatched_at);
        $this->assertNull($processed->fresh()->last_dispatched_at);
        $this->assertNull($fresh->fresh()->last_dispatched_at);
    }

    public function test_recovery_attempt_cap_prevents_endless_automatic_redelivery(): void
    {
        Queue::fake();
        config()->set('payments.webhooks.max_recovery_attempts', 3);
        $event = $this->webhook('evt_Phase6Exhausted123', 'failed', now()->subHour());
        DB::table('payment_webhook_events')->where('id', $event->id)->update([
            'attempt_count' => 3,
            'updated_at' => now()->subHour(),
        ]);
        $this->artisan('payments:recover-webhooks')->assertExitCode(0);
        Queue::assertNothingPushed();
        $this->assertSame('failed', $event->fresh()->status);
    }

    public function test_audit_entries_are_idempotent_and_do_not_record_unapproved_references(): void
    {
        $audit = app(PaymentAuditRecorder::class);
        $audit->record('stripe:payment:42:paid', 'payment_confirmed', 42,
            providerReference: 'pi_Phase6Ref123', oldStatus: 'processing', newStatus: 'paid',
            amountMinor: 1000, currency: 'USD');
        $audit->record('stripe:payment:42:paid', 'payment_confirmed', 42,
            providerReference: 'pi_Phase6Ref123', oldStatus: 'processing', newStatus: 'paid',
            amountMinor: 1000, currency: 'USD');
        $audit->record('stripe:payment:43:paid', 'payment_confirmed', 43,
            providerReference: 'PAN-4242424242424242');
        $this->assertDatabaseCount('payment_audit_entries', 2);
        $this->assertSame(1000, (int) DB::table('payment_audit_entries')
            ->where('event_key', 'stripe:payment:42:paid')->value('amount_minor'));
        $this->assertNull(DB::table('payment_audit_entries')
            ->where('event_key', 'stripe:payment:43:paid')->value('provider_reference'));
    }

    public function test_failed_processing_does_not_store_provider_exception_message(): void
    {
        $event = $this->webhook('evt_Phase6Unmatched123', 'received', now()->subHour());
        $event->forceFill(['event_type' => 'payment_intent.succeeded',
            'payload' => ['data' => ['object' => ['id' => 'pi_Phase6Missing123']]]])->save();
        try {
            (new ProcessPaymentWebhook($event->id))->handle(app(PaymentOrchestrator::class));
            $this->fail('Unmatched provider event must not be treated as successful.');
        } catch (PaymentGatewayException $exception) {
            $this->assertSame('Verified payment event requires retry or reconciliation.', $exception->getMessage());
        }
        $this->assertSame('failed', $event->fresh()->status);
        $this->assertSame('PaymentGatewayException', $event->fresh()->last_error);
        $this->assertSame(1, $event->fresh()->attempt_count);
    }

    public function test_health_snapshot_reports_stranded_payment_webhooks(): void
    {
        $this->webhook('evt_Phase6Health123', 'received', now()->subHour());
        $metrics = app(PaymentHealthSnapshot::class)->collect();
        $this->assertSame(1, $metrics['stale_webhooks']);
        $this->assertSame(0, $metrics['refund_exceptions']);
    }

    private function webhook(string $id, string $status, \DateTimeInterface $date): PaymentWebhookEvent
    {
        return PaymentWebhookEvent::query()->create([
            'provider' => 'stripe', 'provider_event_id' => $id,
            'event_type' => 'checkout.session.completed',
            'payload_hash' => hash('sha256', $id),
            'payload' => ['data' => ['object' => ['id' => 'cs_Phase6Test123']]],
            'status' => $status, 'received_at' => $date,
        ]);
    }
}

/** Test double simulates signature verification; production uses stripe-php. */
final class Phase6VerifiedGatewayFake implements PaymentGateway
{
    private FakeStripeGateway $gateway;

    public function __construct()
    {
        $this->gateway = new FakeStripeGateway();
    }

    public function code(): string { return 'stripe'; }
    public function configured(): bool { return true; }
    public function createCheckout(\App\Models\Order $order, \App\Models\OrderPayment $payment): GatewayRedirect
    {
        return $this->gateway->createCheckout($order, $payment);
    }
    public function retrievePayment(\App\Models\OrderPayment $payment): GatewayPaymentStatus
    {
        return $this->gateway->retrievePayment($payment);
    }
    public function refund(\App\Models\OrderPayment $payment, float $amount, string $reason = '',
        ?string $idempotencyKey = null, array $metadata = []): GatewayRefundResult
    {
        return $this->gateway->refund($payment, $amount, $reason, $idempotencyKey, $metadata);
    }
    public function verifyWebhook(string $payload, string $signature): GatewayWebhook
    {
        if ($signature !== 'test-valid') {
            throw new PaymentGatewayException('Signature rejected');
        }
        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        return new GatewayWebhook((string) $decoded['id'], (string) $decoded['type'], $decoded);
    }
}
