<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Payments\Gateways\StripeGateway;
use App\Models\OrderReturnRequest;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Payments\Exceptions\PaymentGatewayException;
use App\Services\Order\OrderWorkflowService;
use App\Services\Payments\PaymentOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PaymentReadOnlyPhase1Test extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_super_admin_cannot_submit_payment_status_or_financial_fields(): void
    {
        foreach (['admin', 'super_admin'] as $role) {
            $admin = User::factory()->create(['role' => $role, 'is_active' => true]);
            $order = $this->order();

            $this->actingAs($admin, 'admin')
                ->from(route('admin.orders.show', $order))
                ->patch(route('admin.orders.update', $order), [
                    'status' => 'pending_payment',
                    'fulfillment_status' => 'unfulfilled',
                    'payment_status' => 'paid',
                    'paid_at' => now()->toIso8601String(),
                    'grand_total' => '0.01',
                ])
                ->assertRedirect(route('admin.orders.show', $order))
                ->assertSessionHasErrors(['payment_status', 'paid_at', 'grand_total']);

            $this->assertSame('pending', $order->fresh()->payment_status);
            $this->assertNull($order->fresh()->paid_at);
            $this->assertSame('115.00', $order->fresh()->grand_total);
            $this->assertDatabaseCount('order_payments', 0);
        }
    }

    public function test_approval_of_paid_order_does_not_create_a_payment_timestamp(): void
    {
        $order = $this->order([
            'status' => 'payment_review',
            'payment_status' => 'paid',
            'paid_at' => null,
        ]);

        $this->actingAs($this->admin(), 'admin')
            ->patch(route('admin.orders.approve', $order))
            ->assertRedirect();

        $this->assertSame('design_review', $order->fresh()->status);
        $this->assertNull($order->fresh()->paid_at);
        $this->assertDatabaseCount('order_payments', 0);
    }

    public function test_admin_cannot_fake_payment_lifecycle_by_changing_order_status(): void
    {
        $admin = $this->admin();
        $order = $this->order(['status' => 'on_hold']);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.update', $order), [
                'status' => 'payment_failed',
                'fulfillment_status' => 'unfulfilled',
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame('on_hold', $order->fresh()->status);
    }

    public function test_admin_can_still_update_non_financial_order_notes(): void
    {
        $order = $this->order();

        $this->actingAs($this->admin(), 'admin')
            ->patch(route('admin.orders.update', $order), [
                'status' => 'pending_payment',
                'fulfillment_status' => 'unfulfilled',
                'admin_note' => 'Customer was contacted.',
            ])->assertRedirect();

        $order->refresh();
        $this->assertSame('Customer was contacted.', $order->admin_note);
        $this->assertSame('pending', $order->payment_status);
        $this->assertDatabaseCount('order_payments', 0);
    }

    public function test_admin_and_super_admin_cannot_change_refunds_or_approved_amounts(): void
    {
        foreach (['admin', 'super_admin'] as $role) {
            $customer = $this->customer();
            $order = $this->order(['user_id' => $customer->id]);
            $return = $this->returnRequest($order, $customer);
            $admin = User::factory()->create(['role' => $role, 'is_active' => true]);

            $this->actingAs($admin, 'admin')
                ->from(route('admin.returns.show', $return))
                ->patch(route('admin.returns.update', $return), [
                    'status' => 'approved',
                    'admin_note' => 'Test only',
                    'approved_amount' => '40',
                    'refund_status' => 'issued',
                    'provider_reference' => 're_fake',
                ])
                ->assertSessionHasErrors(['approved_amount', 'refund_status', 'provider_reference']);

            $this->assertSame('requested', $return->fresh()->status);
            $this->assertNull($return->fresh()->approved_amount);
            $this->assertSame('pending', $order->fresh()->payment_status);
            $this->assertDatabaseCount('order_refunds', 0);
        }
    }

    public function test_admin_can_review_a_return_without_creating_any_refund(): void
    {
        $customer = $this->customer();
        $order = $this->order(['user_id' => $customer->id]);
        $return = $this->returnRequest($order, $customer);

        $this->actingAs($this->admin(), 'admin')
            ->patch(route('admin.returns.update', $return), [
                'status' => 'under_review',
                'admin_note' => 'Awaiting item inspection.',
            ])->assertRedirect();

        $this->assertSame('under_review', $return->fresh()->status);
        $this->assertSame('Awaiting item inspection.', $return->fresh()->admin_note);
        $this->assertDatabaseCount('order_refunds', 0);
    }

    public function test_service_layer_rejects_financial_payload_even_without_http_validation(): void
    {
        $order = $this->order();
        $admin = $this->admin();

        try {
            app(OrderWorkflowService::class)->updateOrder($order, $admin, [
                'status' => 'pending_payment',
                'fulfillment_status' => 'unfulfilled',
                'payment_status' => 'paid',
            ]);
            $this->fail('The service accepted an administrator payment override.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('payment_status', $exception->errors());
        }

        $this->assertDatabaseCount('order_payments', 0);
        $this->assertSame('pending', $order->fresh()->payment_status);

        $return = $this->returnRequest($order, $order->user);
        try {
            app(OrderWorkflowService::class)->updateReturn($return, $admin, [
                'status' => 'approved',
                'refund_status' => 'issued',
            ]);
            $this->fail('The service accepted an administrator refund override.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('refund_status', $exception->errors());
        }
        $this->assertDatabaseCount('order_refunds', 0);
    }

    public function test_admin_payment_method_module_is_read_only_even_for_super_admins(): void
    {
        $method = PaymentMethod::query()->firstOrFail();

        foreach (['admin', 'super_admin'] as $role) {
            $admin = User::factory()->create(['role' => $role, 'is_active' => true]);
            $this->actingAs($admin, 'admin');

            $this->get(route('admin.payment-methods.index'))->assertOk()
                ->assertDontSee('Add Payment Method');
            $this->get(route('admin.payment-methods.create'))->assertForbidden();
            $this->get(route('admin.payment-methods.edit', $method))->assertForbidden();
            $this->post(route('admin.payment-methods.store'), ['name' => 'Fake'])->assertForbidden();
            $this->put(route('admin.payment-methods.update', $method), ['is_active' => false])->assertForbidden();
            $this->delete(route('admin.payment-methods.destroy', $method))->assertForbidden();
        }

        $this->assertNotNull($method->fresh());
        $this->assertSame((bool) $method->is_active, (bool) $method->fresh()->is_active);
    }

    public function test_stripe_gateway_adapter_itself_refuses_application_refund_calls(): void
    {
        try {
            app(StripeGateway::class)->refund(new OrderPayment(), 40.0);
            $this->fail('StripeGateway must not issue application-initiated refunds.');
        } catch (PaymentGatewayException $exception) {
            $this->assertStringContainsString('Stripe Dashboard', $exception->getMessage());
        }
    }

    public function test_backend_cannot_issue_refunds_even_from_a_direct_service_call(): void
    {
        $order = $this->order();
        $return = $this->returnRequest($order, $order->user);

        try {
            app(PaymentOrchestrator::class)->issueReturnRefund($return, 40.0);
            $this->fail('Application-issued refunds must be disabled.');
        } catch (PaymentGatewayException $exception) {
            $this->assertStringContainsString('Stripe finance owner', $exception->getMessage());
        }

        $this->assertDatabaseCount('order_refunds', 0);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer', 'is_active' => true]);
    }

    private function order(array $overrides = []): Order
    {
        $customer = $this->customer();

        return Order::query()->create(array_merge([
            'user_id' => $customer->id,
            'order_number' => 'NP-'.Str::upper(Str::random(10)),
            'status' => 'pending_payment',
            'payment_status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'USD',
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'subtotal' => 100,
            'customization_total' => 0,
            'discount_total' => 0,
            'shipping_total' => 10,
            'tax_total' => 5,
            'grand_total' => 115,
            'total_quantity' => 1,
            'placed_at' => now(),
        ], $overrides));
    }

    private function returnRequest(Order $order, User $customer): OrderReturnRequest
    {
        return OrderReturnRequest::query()->create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'return_number' => 'RET-'.Str::upper(Str::random(10)),
            'type' => 'return',
            'status' => 'requested',
            'reason_code' => 'size_issue',
            'requested_resolution' => 'refund',
            'requested_at' => now(),
        ]);
    }
}
