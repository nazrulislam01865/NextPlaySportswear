<?php

namespace Tests\Feature;

use App\Models\BulkQuoteRequest;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\OrderRefund;
use App\Models\PaymentMethod;
use App\Models\PaymentWebhookEvent;
use App\Models\StripeRefundSync;
use App\Models\User;
use App\Payments\Exceptions\PaymentGatewayException;
use App\Services\Payments\ApprovedInstallmentService;
use App\Services\Payments\PaymentOrchestrator;
use App\Services\Payments\StripeRefundProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Fakes\FakeStripeGateway;
use Tests\TestCase;

class StripeRefundAndInstallmentPhase45Test extends TestCase
{
    use RefreshDatabase;

    private Phase45RefundProviderFake $provider;

    protected function setUp(): void
    {
        parent::setUp();
        FakeStripeGateway::reset();
        config()->set('payments.gateways.stripe.driver', FakeStripeGateway::class);
        config()->set('payments.gateways.stripe.enabled', true);
        config()->set('payments.gateways.stripe.secret_key', 'sk_test_fake');
        config()->set('payments.installments.enabled', true);
        $this->provider = new Phase45RefundProviderFake();
        app()->instance(StripeRefundProvider::class, $this->provider);
    }

    public function test_dashboard_refund_without_metadata_is_matched_by_stripe_charge_and_intent_once(): void
    {
        $order = $this->order();
        $payment = $this->paidPayment($order);
        $id = $this->setRefund($payment, 3000, 'succeeded');
        $this->webhook('refund.created', $id);
        $this->webhook('refund.updated', $id);
        $this->assertSame(1, OrderRefund::query()->count());
        $refund = OrderRefund::query()->firstOrFail();
        $this->assertSame('issued', $refund->status);
        $this->assertSame('30.00', $refund->amount);
        $this->assertSame('30.00', $payment->fresh()->refunded_amount);
        $this->assertSame('partially_refunded', $order->fresh()->payment_status);
        $this->assertSame(1, $refund->creditNote()->count());
        $this->assertSame(1, StripeRefundSync::query()->count());

        // Stripe events can arrive late: current provider status determines truth.
        $this->provider->refunds[$id]['status'] = 'pending';
        $this->webhook('refund.failed', $id);
        $this->assertSame('issued', $refund->fresh()->status);
        $this->assertSame('30.00', $payment->fresh()->refunded_amount);
    }

    public function test_multiple_partial_refunds_then_full_refund_are_not_counted_twice(): void
    {
        $order = $this->order();
        $payment = $this->paidPayment($order);
        $one = $this->setRefund($payment, 1500, 'succeeded');
        $two = $this->setRefund($payment, 10000, 'succeeded');
        $this->webhook('refund.created', $one);
        $this->webhook('refund.created', $two);
        $this->assertSame('refunded', $order->fresh()->payment_status);
        $this->assertSame('115.00', $payment->fresh()->refunded_amount);
        $this->assertSame(2, $order->refunds()->count());
        $this->webhook('refund.updated', $two);
        $this->assertSame(2, $order->refunds()->count());
    }

    public function test_refund_cannot_match_a_charge_with_an_incorrect_captured_amount(): void
    {
        $order = $this->order();
        $payment = $this->paidPayment($order);
        $id = $this->setRefund($payment, 1000, 'succeeded');
        $chargeId = $this->provider->refunds[$id]['charge'];
        $this->provider->charges[$chargeId]['amount'] = 12000;
        $this->webhook('refund.created', $id);
        $this->assertSame(0, OrderRefund::query()->count());
        $this->assertSame('unmatched_capture', StripeRefundSync::query()->firstOrFail()->exception_code);
    }

    public function test_a_second_checkout_request_cannot_skip_a_pending_deposit(): void
    {
        $order = $this->acceptedQuoteOrder([4000, 7500]);
        $method = PaymentMethod::query()->where('provider', 'stripe')->firstOrFail();
        $method->forceFill(['is_active' => true])->save();
        app(PaymentOrchestrator::class)->initiateOrderPayment($order, $method->code, 'initial-attempt-key-111');
        app(PaymentOrchestrator::class)->initiateOrderPayment($order, $method->code, 'second-attempt-key-222');
        $this->assertSame(1, $order->payments()->count());
        $this->assertSame('40.00', $order->payments()->firstOrFail()->amount);
    }

    public function test_accepted_schedule_amounts_are_immutable(): void
    {
        $order = $this->acceptedQuoteOrder([4000, 7500]);
        app(ApprovedInstallmentService::class)->ensureForOrder($order);
        $first = $order->installmentSchedule->installments()->firstOrFail();
        $this->expectException(\LogicException::class);
        $first->update(['amount_minor' => 100]);
    }

    public function test_unmatched_refund_is_a_review_exception_not_attached_to_an_arbitrary_order(): void
    {
        $order = $this->order();
        $this->paidPayment($order);
        $id = 're_'.Str::random(18);
        $charge = 'ch_'.Str::random(18);
        $this->provider->refunds[$id] = $this->snapshot($id, $charge, 2000, 'succeeded');
        $this->provider->charges[$charge] = $this->charge('pi_'.Str::random(18));
        $this->provider->charges[$charge]['id'] = $charge;
        $this->webhook('refund.created', $id);
        $this->assertSame(0, OrderRefund::query()->count());
        $this->assertSame('exception', StripeRefundSync::query()->where('provider_refund_id', $id)->firstOrFail()->status);
    }

    public function test_failed_refund_never_counts_and_success_can_be_reconciled_later(): void
    {
        $order = $this->order();
        $payment = $this->paidPayment($order);
        $id = $this->setRefund($payment, 2000, 'failed');
        $this->webhook('refund.failed', $id);
        $this->assertSame('failed', OrderRefund::query()->firstOrFail()->status);
        $this->assertSame('0.00', $payment->fresh()->refunded_amount);
        $this->provider->refunds[$id]['status'] = 'succeeded';
        $this->webhook('refund.updated', $id);
        $this->assertSame('issued', OrderRefund::query()->firstOrFail()->status);
        $this->assertSame('20.00', $payment->fresh()->refunded_amount);
    }

    public function test_approved_deposit_then_balance_requires_distinct_verified_checkout_attempts(): void
    {
        $order = $this->acceptedQuoteOrder([4000, 7500]);
        $method = PaymentMethod::query()->where('provider', 'stripe')->firstOrFail();
        $method->forceFill(['is_active' => true])->save();
        $first = app(PaymentOrchestrator::class)->initiateOrderPayment($order, $method->code, 'unique-deposit-payment-key');
        $this->assertTrue($first->requiresRedirect());
        $deposit = $order->payments()->firstOrFail();
        $this->assertSame('40.00', $deposit->amount);
        $this->assertNotNull($deposit->order_payment_installment_id);
        app(PaymentOrchestrator::class)->reconcile($deposit);
        $this->assertSame('partially_paid', $order->fresh()->payment_status);
        $this->assertSame('pending_payment', $order->fresh()->status);
        $this->assertNull($order->fresh()->paid_at);
        $this->assertTrue($order->fresh()->canPay());

        $second = app(PaymentOrchestrator::class)->initiateOrderPayment($order->fresh(), $method->code, 'unique-balance-payment-key');
        $this->assertTrue($second->requiresRedirect());
        $balance = $order->payments()->latest('id')->firstOrFail();
        $this->assertSame('75.00', $balance->amount);
        $this->assertNotSame($deposit->order_payment_installment_id, $balance->order_payment_installment_id);
        app(PaymentOrchestrator::class)->reconcile($balance);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('design_review', $order->fresh()->status);
        $this->assertSame(2, $order->payments()->count());
        $this->assertSame(11500, $order->fresh()->financialState()['gross_captured_minor']);
    }

    public function test_quote_text_alone_does_not_authorize_a_deposit(): void
    {
        $order = $this->acceptedQuoteOrder(null, '50% deposit; balance before dispatch');
        $this->expectException(PaymentGatewayException::class);
        app(ApprovedInstallmentService::class)->ensureForOrder($order);
    }

    public function test_unapproved_or_mismatched_quote_installments_cannot_create_payment_attempts(): void
    {
        $order = $this->acceptedQuoteOrder([4000, 7200]);
        $this->expectException(PaymentGatewayException::class);
        app(ApprovedInstallmentService::class)->ensureForOrder($order);
    }

    private function order(): Order
    {
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        return Order::query()->create([
            'user_id' => $customer->id, 'order_number' => 'NP-'.Str::upper(Str::random(10)),
            'status' => 'pending_payment', 'payment_status' => 'pending',
            'fulfillment_status' => 'unfulfilled', 'currency' => 'USD',
            'customer_name' => $customer->name, 'customer_email' => $customer->email,
            'grand_total' => 115, 'subtotal' => 115, 'total_quantity' => 1, 'placed_at' => now(),
        ]);
    }

    private function paidPayment(Order $order): OrderPayment
    {
        $method = PaymentMethod::query()->where('provider', 'stripe')->firstOrFail();
        $payment = OrderPayment::query()->create([
            'order_id' => $order->id, 'payment_method_id' => $method->id,
            'gateway' => 'stripe', 'provider' => 'stripe',
            'idempotency_key' => hash('sha256', $order->id.'confirmed'),
            'provider_session_id' => 'cs_'.Str::random(18),
            'provider_payment_id' => 'pi_'.Str::random(18),
            'status' => 'paid', 'amount' => 115, 'currency' => 'USD',
            'attempted_at' => now(), 'paid_at' => now(), 'refunded_amount' => 0,
        ]);
        $order->forceFill(['payment_status' => 'paid', 'paid_at' => now()])->save();
        return $payment;
    }

    private function setRefund(OrderPayment $payment, int $amount, string $status): string
    {
        $id = 're_'.Str::random(18);
        $chargeId = 'ch_'.Str::random(18);
        $this->provider->refunds[$id] = $this->snapshot($id, $chargeId, $amount, $status);
        $this->provider->charges[$chargeId] = $this->charge((string) $payment->provider_payment_id);
        $this->provider->charges[$chargeId]['id'] = $chargeId;
        return $id;
    }

    private function snapshot(string $id, string $charge, int $amount, string $status): array
    {
        return compact('id', 'charge', 'amount', 'status') + ['livemode' => false, 'currency' => 'usd'];
    }

    private function charge(string $intentId): array
    {
        return ['payment_intent' => $intentId, 'paid' => true, 'captured' => true,
            'livemode' => false, 'amount' => 11500, 'currency' => 'usd'];
    }

    private function webhook(string $type, string $id): void
    {
        $event = PaymentWebhookEvent::query()->create([
            'provider' => 'stripe', 'provider_event_id' => 'evt_'.Str::random(19),
            'event_type' => $type, 'payload_hash' => hash('sha256', $id.$type),
            'payload' => ['data' => ['object' => ['id' => $id]]],
            'status' => 'received', 'received_at' => now(),
        ]);
        app(PaymentOrchestrator::class)->processWebhook($event);
    }

    private function acceptedQuoteOrder(?array $amounts, string $paymentTerms = 'Deposit plus balance'): Order
    {
        $order = $this->order();
        $terms = ['payment_terms' => $paymentTerms];
        if ($amounts !== null) {
            $terms['payment_installments'] = [
                ['kind' => 'deposit', 'amount_minor' => $amounts[0]],
                ['kind' => 'balance', 'amount_minor' => $amounts[1]],
            ];
        }
        $quote = BulkQuoteRequest::query()->create([
            'reference' => 'BQ-'.Str::upper(Str::random(10)),
            'full_name' => 'Team Customer', 'organization' => 'Local Team',
            'email' => $order->customer_email, 'phone' => '12345678',
            'product_type' => 'Teamwear', 'estimated_quantity' => '10-20',
            'sizes_needed' => 'S M L', 'artwork_details' => 'Customer artwork',
            'shipping_address' => 'Example Street', 'country' => 'United States',
            'needed_by' => now()->addMonth()->toDateString(),
            'status' => BulkQuoteRequest::STATUS_CONVERTED,
            'guest_decision' => 'create_order', 'converted_order_id' => $order->id,
            'quotation_version' => 1, 'quotation_sent_at' => now()->subDay(),
            'quoted_amount' => 115, 'quote_currency' => 'USD', 'quotation_details' => $terms,
        ]);
        $order->forceFill([
            'status' => 'quote_invoice_requested', 'payment_status' => 'pending',
            'information' => [
                'source' => 'guest_bulk_quote', 'bulk_quote_id' => $quote->id, 'version' => 1,
            ],
        ])->save();
        return $order->fresh();
    }
}

class Phase45RefundProviderFake extends StripeRefundProvider
{
    public array $refunds = [];
    public array $charges = [];

    public function refund(string $refundId): array
    {
        return $this->refunds[$refundId];
    }

    public function charge(string $chargeId): array
    {
        return $this->charges[$chargeId];
    }

    public function refundIdsForCharge(string $chargeId): array
    {
        return array_keys(array_filter($this->refunds, static fn ($r) => $r['charge'] === $chargeId));
    }
}
