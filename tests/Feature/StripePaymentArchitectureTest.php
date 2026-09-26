<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\OrderReturnRequest;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\Payments\PaymentMethodService;
use App\Services\Payments\PaymentOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Fakes\FakeStripeGateway;
use Tests\TestCase;

class StripePaymentArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        FakeStripeGateway::reset();
        config()->set('payments.gateways.stripe.driver', FakeStripeGateway::class);
        config()->set('payments.gateways.stripe.enabled', true);
        config()->set('payments.gateways.stripe.capabilities', [
            'is_online' => true,
            'requires_provider_redirect' => true,
            'requires_manual_review' => false,
            'allows_saved_methods' => false,
        ]);
    }

    public function test_gateway_capabilities_override_unsafe_admin_flags(): void
    {
        $method = PaymentMethod::query()->where('provider', 'stripe')->firstOrFail();
        $method->update([
            'is_online' => false,
            'requires_provider_redirect' => false,
            'requires_manual_review' => true,
            'allows_saved_methods' => true,
            'is_active' => true,
        ]);

        $methods = app(PaymentMethodService::class)->availableMethods(['total' => 115]);
        $stripe = collect($methods)->firstWhere('provider', 'stripe');

        $this->assertNotNull($stripe);
        $this->assertTrue($stripe['is_online']);
        $this->assertTrue($stripe['requires_provider_redirect']);
        $this->assertFalse($stripe['requires_manual_review']);
        $this->assertFalse($stripe['allows_saved_methods']);
    }

    public function test_provider_refund_is_idempotent_and_updates_internal_totals(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $order = $this->paidOrder($customer);
        $paymentMethod = PaymentMethod::query()->where('provider', 'stripe')->firstOrFail();

        $payment = OrderPayment::query()->create([
            'order_id' => $order->id,
            'payment_method_id' => $paymentMethod->id,
            'gateway' => 'stripe',
            'provider' => 'stripe',
            'provider_reference' => 'cs_test_paid',
            'provider_session_id' => 'cs_test_paid',
            'provider_payment_id' => 'pi_test_paid',
            'idempotency_key' => hash('sha256', 'paid-test'),
            'status' => 'paid',
            'amount' => 115,
            'currency' => 'USD',
            'attempted_at' => now()->subMinute(),
            'paid_at' => now(),
            'refunded_amount' => 0,
        ]);

        $return = OrderReturnRequest::query()->create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'return_number' => 'RET-'.Str::upper(Str::random(8)),
            'type' => 'return',
            'status' => 'received',
            'reason_code' => 'size_issue',
            'reason' => 'Refund requested by customer.',
            'requested_resolution' => 'refund',
            'approved_amount' => 40,
            'requested_at' => now()->subDay(),
            'received_at' => now(),
        ]);

        $orchestrator = app(PaymentOrchestrator::class);
        $first = $orchestrator->issueReturnRefund($return, 40);
        $second = $orchestrator->issueReturnRefund($return->fresh(), 40);

        $this->assertSame('issued', $first->status);
        $this->assertSame($first->id, $second->id);
        $this->assertCount(1, FakeStripeGateway::$refundCalls);
        $this->assertStringStartsWith('refund:RFN-', FakeStripeGateway::$refundCalls[0]['idempotencyKey']);
        $this->assertSame('40.00', $payment->fresh()->refunded_amount);
        $this->assertSame('partially_refunded', $order->fresh()->payment_status);
        $this->assertNotNull($first->fresh()->creditNote);
    }

    public function test_untrusted_payment_return_never_renders_demo_order_data(): void
    {
        $this->get(route('payments.return', ['provider' => 'stripe']))
            ->assertOk()
            ->assertSee('Payment verification in progress')
            ->assertDontSee('NP-DEMO-10482');
    }

    private function paidOrder(User $customer): Order
    {
        return Order::query()->create([
            'user_id' => $customer->id,
            'order_number' => 'NP-'.Str::upper(Str::random(10)),
            'status' => 'completed',
            'payment_status' => 'paid',
            'fulfillment_status' => 'fulfilled',
            'currency' => 'USD',
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'subtotal' => 100,
            'customization_total' => 0,
            'discount_total' => 0,
            'shipping_total' => 10,
            'tax_total' => 5,
            'grand_total' => 115,
            'total_quantity' => 2,
            'placed_at' => now()->subDays(3),
            'paid_at' => now()->subDays(3),
            'completed_at' => now()->subDay(),
        ]);
    }
}
