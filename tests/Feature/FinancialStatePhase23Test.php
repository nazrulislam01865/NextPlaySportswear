<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\OrderRefund;
use App\Models\PaymentMethod;
use App\Models\PaymentWebhookEvent;
use App\Models\User;
use App\Payments\Exceptions\PaymentGatewayException;
use App\Services\Payments\FinancialStateService;
use App\Services\Payments\PaymentOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Fakes\FakeStripeGateway;
use Tests\TestCase;

class FinancialStatePhase23Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        FakeStripeGateway::reset();
        config()->set('payments.gateways.stripe.driver', FakeStripeGateway::class);
        config()->set('payments.gateways.stripe.enabled', true);
    }

    public function test_financial_projection_keeps_refund_state_separate_from_outstanding_contract(): void
    {
        $order = $this->order();
        $paid = $this->payment($order, 'paid', 40, 'partial');
        OrderRefund::query()->create([
            'order_id' => $order->id,
            'order_payment_id' => $paid->id,
            'refund_number' => 'RF-'.Str::upper(Str::random(8)),
            'status' => 'issued',
            'amount' => 10,
            'currency' => 'USD',
        ]);
        $state = app(FinancialStateService::class)->forOrder($order);
        $this->assertSame('partially_paid', $state['collection_status']);
        $this->assertSame('partially_refunded', $state['refund_status']);
        $this->assertSame(4000, $state['gross_captured_minor']);
        $this->assertSame(1000, $state['confirmed_refunded_minor']);
        $this->assertSame(3000, $state['net_received_minor']);
        $this->assertSame(7500, $state['outstanding_minor']);
        $this->assertSame(75.0, $order->outstandingAmount());
    }

    public function test_provider_confirmed_partial_receipt_never_advances_order_into_design_review(): void
    {
        $order = $this->order();
        $payment = $this->payment($order, 'processing', 40);
        app(PaymentOrchestrator::class)->reconcile($payment);
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('partially_paid', $order->fresh()->payment_status);
        $this->assertSame('pending_payment', $order->fresh()->status);
        $this->assertNull($order->fresh()->paid_at);
        $this->assertFalse($order->fresh()->canApproveAfterPayment());
    }

    public function test_card_decline_is_recorded_without_closing_active_checkout(): void
    {
        $order = $this->order();
        $payment = $this->payment($order);
        $event = $this->event($payment, 'payment_intent.payment_failed', [
            'id' => $payment->provider_payment_id,
            'metadata' => ['payment_id' => $payment->id, 'order_id' => $order->id],
            'last_payment_error' => ['decline_code' => 'insufficient_funds'],
        ]);
        app(PaymentOrchestrator::class)->processWebhook($event);
        $this->assertSame('processing', $payment->fresh()->status);
        $this->assertSame('processing', $order->fresh()->payment_status);
        $this->assertSame('attempt_declined', $payment->fresh()->failure_code);
        $this->assertSame('insufficient_funds', data_get($payment->fresh()->metadata, 'last_decline_code'));
        $this->assertSame(1, data_get($payment->fresh()->metadata, 'decline_count'));
        app(PaymentOrchestrator::class)->processWebhook($event);
        $this->assertSame(1, data_get($payment->fresh()->metadata, 'decline_count'));
    }

    public function test_terminal_failure_cannot_erase_earlier_confirmed_receipt(): void
    {
        $order = $this->order();
        $this->payment($order, 'paid', 40, 'old');
        $payment = $this->payment($order, 'processing', 75, 'new');
        app(PaymentOrchestrator::class)->processWebhook($this->event($payment, 'checkout.session.async_payment_failed', [
            'id' => $payment->provider_session_id,
            'metadata' => ['payment_id' => $payment->id, 'order_id' => $order->id],
        ]));
        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame('partially_paid', $order->fresh()->payment_status);
        $this->assertSame(75.0, $order->outstandingAmount());
        $this->assertSame('pending_payment', $order->fresh()->status);
    }

    public function test_late_payment_intent_success_recovers_from_earlier_failed_notification(): void
    {
        $order = $this->order();
        $payment = $this->payment($order);
        app(PaymentOrchestrator::class)->processWebhook($this->event($payment, 'checkout.session.async_payment_failed', [
            'id' => $payment->provider_session_id,
            'metadata' => ['payment_id' => $payment->id, 'order_id' => $order->id],
        ]));
        $this->assertSame('failed', $payment->fresh()->status);
        app(PaymentOrchestrator::class)->processWebhook($this->event($payment, 'payment_intent.succeeded', [
            'id' => $payment->provider_payment_id,
            'metadata' => ['payment_id' => $payment->id, 'order_id' => $order->id],
            'amount_received' => 11500,
            'currency' => 'usd',
        ]));
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('design_review', $order->fresh()->status);
        $this->assertSame(1, $order->histories()->where('title', 'Payment confirmed')->count());
        app(PaymentOrchestrator::class)->processWebhook($this->event($payment, 'checkout.session.async_payment_succeeded', [
            'id' => $payment->provider_session_id,
            'payment_status' => 'paid', 'amount_total' => 11500, 'currency' => 'usd',
            'payment_intent' => $payment->provider_payment_id,
            'metadata' => ['payment_id' => $payment->id, 'order_id' => $order->id],
        ]));
        $this->assertSame(1, $order->histories()->where('title', 'Payment confirmed')->count());
    }

    public function test_mismatched_provider_intent_and_order_metadata_are_rejected(): void
    {
        $order = $this->order();
        $payment = $this->payment($order);
        $orchestrator = app(PaymentOrchestrator::class);
        foreach ([
            ['id' => 'pi_wrong', 'metadata' => ['payment_id' => $payment->id, 'order_id' => $order->id]],
            ['id' => $payment->provider_payment_id, 'metadata' => ['payment_id' => $payment->id, 'order_id' => 999999]],
        ] as $object) {
            try {
                $orchestrator->processWebhook($this->event($payment, 'payment_intent.succeeded', $object));
                $this->fail('Provider identifiers must not be accepted when inconsistent.');
            } catch (PaymentGatewayException $e) {
                $this->assertNotSame('', $e->getMessage());
            }
        }
        $this->assertSame('processing', $payment->fresh()->status);
    }

    public function test_wrong_webhook_amount_cannot_confirm_payment(): void
    {
        $order = $this->order();
        $payment = $this->payment($order);
        $this->expectException(PaymentGatewayException::class);
        app(PaymentOrchestrator::class)->processWebhook($this->event($payment, 'checkout.session.completed', [
            'id' => $payment->provider_session_id, 'payment_status' => 'paid',
            'amount_total' => 1, 'currency' => 'usd',
            'metadata' => ['payment_id' => $payment->id, 'order_id' => $order->id],
        ]));
    }

    public function test_no_new_session_is_created_while_an_existing_session_is_active(): void
    {
        $order = $this->order();
        $payment = $this->payment($order);
        $payment->forceFill([
            'expires_at' => now()->addMinutes(10),
            'metadata' => ['checkout_url' => 'https://checkout.stripe.test/same-session'],
        ])->save();
        $redirect = app(PaymentOrchestrator::class)->initiateOrderPayment($order, 'card', 'another-unique-idempotency-seed');
        $this->assertSame('https://checkout.stripe.test/same-session', $redirect->url);
        $this->assertSame(1, $order->payments()->count());
    }

    public function test_confirmed_receipt_amount_and_status_cannot_be_rewritten(): void
    {
        $order = $this->order();
        $payment = $this->payment($order, 'paid');
        try {
            $payment->update(['amount' => 10]);
            $this->fail('A confirmed Stripe amount must be immutable.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('cannot be', $exception->getMessage());
        }
        try {
            $payment->fresh()->update(['status' => 'failed']);
            $this->fail('A confirmed Stripe receipt must never regress.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('cannot be', $exception->getMessage());
        }
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('115.00', $payment->fresh()->amount);
    }

    public function test_a_second_payment_method_cannot_start_while_stripe_session_is_live(): void
    {
        $order = $this->order();
        $this->payment($order);
        try {
            app(PaymentOrchestrator::class)->initiateOrderPayment($order, 'invoice', 'another-unique-idempotency-seed');
            $this->fail('Live payment from another method must block new checkout.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('payment_method', $exception->errors());
        }
        $this->assertSame(1, $order->payments()->count());
    }

    private function order(): Order
    {
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        return Order::query()->create([
            'user_id' => $customer->id, 'order_number' => 'NP-'.Str::upper(Str::random(9)),
            'status' => 'pending_payment', 'payment_status' => 'processing',
            'fulfillment_status' => 'unfulfilled', 'currency' => 'USD',
            'customer_name' => $customer->name, 'customer_email' => $customer->email,
            'subtotal' => 100, 'shipping_total' => 10, 'tax_total' => 5,
            'grand_total' => 115, 'total_quantity' => 1, 'placed_at' => now(),
        ]);
    }

    private function payment(Order $order, string $status = 'processing', int $amount = 115, string $suffix = 'one'): OrderPayment
    {
        $method = PaymentMethod::query()->where('provider', 'stripe')->firstOrFail();
        return OrderPayment::query()->create([
            'order_id' => $order->id, 'payment_method_id' => $method->id,
            'gateway' => 'stripe', 'provider' => 'stripe',
            'provider_reference' => 'cs_test_'.$suffix.'_'.$order->id,
            'provider_session_id' => 'cs_test_'.$suffix.'_'.$order->id,
            'provider_payment_id' => 'pi_test_'.$suffix.'_'.$order->id,
            'idempotency_key' => hash('sha256', $suffix.'-'.$order->id),
            'status' => $status, 'amount' => $amount, 'currency' => 'USD',
            'attempted_at' => now(), 'paid_at' => $status === 'paid' ? now() : null,
            'refunded_amount' => 0,
        ]);
    }

    private function event(OrderPayment $payment, string $type, array $object): PaymentWebhookEvent
    {
        return PaymentWebhookEvent::query()->create([
            'provider' => 'stripe', 'provider_event_id' => 'evt_'.Str::lower(Str::random(20)),
            'event_type' => $type, 'payload_hash' => hash('sha256', json_encode($object)),
            'payload' => ['data' => ['object' => $object]],
            'status' => 'received', 'received_at' => now(),
        ]);
    }
}
