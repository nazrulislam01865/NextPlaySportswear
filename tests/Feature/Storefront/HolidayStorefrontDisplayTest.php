<?php

namespace Tests\Feature\Storefront;

use App\Http\Middleware\EnforceCustomerSessionVersion;
use App\Models\HolidayCalendar;
use App\Models\HolidayCalendarDate;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderShipment;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\Order\OrderExperienceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class HolidayStorefrontDisplayTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
        ]);
    }

    public function test_screen_3_checkout_shipping_methods_render_holiday_notice(): void
    {
        $holidayService = app(\App\Services\Shipping\HolidayCalendarService::class);
        $holidayService->clearHolidayCache();

        $calendar = HolidayCalendar::query()->create([
            'name' => 'UK Bank Holidays 2026',
            'country_code' => 'GB',
            'country_name' => 'United Kingdom',
            'year' => (int) date('Y'),
            'is_active' => true,
        ]);

        // Add a holiday on the next weekday
        $holidayDate = now()->isWeekend()
            ? now()->next(Carbon::TUESDAY)->format('Y-m-d')
            : now()->addWeekday()->format('Y-m-d');

        HolidayCalendarDate::query()->create([
            'holiday_calendar_id' => $calendar->id,
            'date' => $holidayDate,
            'name' => 'Special Spring Holiday',
        ]);

        $holidayService->clearHolidayCache('United Kingdom', (int) date('Y'));

        ShippingMethod::query()->create([
            'name' => 'UK Express Courier',
            'code' => 'uk-express',
            'minimum_days' => 1,
            'maximum_days' => 3,
            'charge_amount' => 10.00,
            'is_active' => true,
        ]);

        $shippingAddress = [
            'country' => 'United Kingdom',
            'country_code' => 'GB',
            'postal_code' => 'SW1A 1AA',
        ];

        $service = app(\App\Services\Shipping\ShippingMethodService::class);
        $methods = $service->availableMethods(['subtotal' => 100, 'quantity' => 1], $shippingAddress);

        $this->assertNotEmpty($methods);
        $method = collect($methods)->firstWhere('code', 'uk-express');
        $this->assertNotNull($method);
        $this->assertTrue((bool) ($method['holiday_adjusted'] ?? false));
        $this->assertStringContainsString('Special Spring Holiday', (string) $method['holiday_notice']);
    }

    public function test_screen_4_order_confirmation_page_displays_holiday_adjustment_card(): void
    {
        $order = $this->createTestOrder([
            'holiday_adjustment_applied' => true,
            'holiday_adjustment_reason' => 'Extended due to UK Bank Holiday (Christmas Day, 25 Dec)',
            'estimated_delivery_start_at' => Carbon::parse('2026-12-28'),
            'estimated_delivery_end_at' => Carbon::parse('2026-12-30'),
        ]);

        // Put order in placed_order session
        session()->put('nextplay_checkout', [
            'placed_order' => app(OrderExperienceService::class)->orderSnapshot($order),
        ]);

        $response = $this->get(route('order.confirmation'));
        $response->assertOk();
        $response->assertSee('Holiday Adjusted');
        $response->assertSee('Extended due to UK Bank Holiday (Christmas Day, 25 Dec)');
    }

    public function test_screen_5_public_order_tracking_page_renders_holiday_notice_banner(): void
    {
        $order = $this->createTestOrder([
            'customer_email' => 'tracking-user@example.com',
            'holiday_adjustment_applied' => true,
            'holiday_adjustment_reason' => 'Delivery estimate includes additional transit time due to Columbus Day',
            'estimated_delivery_start_at' => Carbon::parse('2026-10-14'),
            'estimated_delivery_end_at' => Carbon::parse('2026-10-16'),
        ]);

        $response = $this->from(route('orders.track'))->post(route('orders.track.lookup'), [
            'order_number' => $order->order_number,
            'email' => 'tracking-user@example.com',
        ]);

        $response->assertRedirect(route('orders.track'));

        $trackingResponse = $this->get(route('orders.track'));
        $trackingResponse->assertOk();
        $trackingResponse->assertSee($order->order_number);
        $trackingResponse->assertSee('Holiday Notice');
        $trackingResponse->assertSee('Delivery estimate includes additional transit time due to Columbus Day');
    }

    public function test_screen_7_account_order_details_displays_holiday_adjustment(): void
    {
        $order = $this->createTestOrder([
            'user_id' => $this->customer->id,
            'holiday_adjustment_applied' => true,
            'holiday_adjustment_reason' => 'Adjusted for Christmas Day bank holiday',
            'estimated_delivery_start_at' => Carbon::parse('2026-12-28'),
            'estimated_delivery_end_at' => Carbon::parse('2026-12-30'),
        ]);

        $response = $this->actingAsCustomer($this->customer)
            ->get(route('account.orders.show', $order));

        $response->assertOk();
        $response->assertSee('Holiday Adjusted');
        $response->assertSee('Adjusted for Christmas Day bank holiday');
    }

    public function test_screen_7_account_shipment_details_displays_holiday_adjustment(): void
    {
        $order = $this->createTestOrder([
            'user_id' => $this->customer->id,
            'holiday_adjustment_applied' => true,
            'holiday_adjustment_reason' => 'Holiday schedule adjustment applied',
        ]);

        $shipment = OrderShipment::query()->create([
            'order_id' => $order->id,
            'shipment_number' => 'SHP-HOLIDAY-999',
            'carrier' => 'Royal Mail',
            'service' => 'Tracked 48',
            'status' => 'in_transit',
            'holiday_reason' => 'Extended due to Christmas Day bank holiday closure',
            'estimated_delivery_at' => Carbon::parse('2026-12-28 17:00:00'),
        ]);

        $response = $this->actingAsCustomer($this->customer)
            ->get(route('account.orders.shipments.show', [$order, $shipment]));

        $response->assertOk();
        $response->assertSee('Holiday Adjusted');
        $response->assertSee('Extended due to Christmas Day bank holiday closure');
    }

    private function actingAsCustomer(User $customer): static
    {
        $this->withSession([
            EnforceCustomerSessionVersion::SESSION_KEY => (int) $customer->auth_session_version,
        ]);

        return $this->actingAs($customer, 'web');
    }

    private function createTestOrder(array $overrides = []): Order
    {
        $order = Order::query()->create(array_merge([
            'user_id' => $this->customer->id,
            'order_number' => 'NP-' . Str::upper(Str::random(8)),
            'status' => 'processing',
            'payment_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'GBP',
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
            'subtotal' => 80,
            'customization_total' => 0,
            'discount_total' => 0,
            'shipping_total' => 5,
            'tax_total' => 0,
            'grand_total' => 85,
            'total_quantity' => 1,
            'shipping_address' => [
                'name' => 'Test Customer',
                'address_line_1' => '10 Downing St',
                'city' => 'London',
                'postal_code' => 'SW1A 2AA',
                'country' => 'United Kingdom',
                'country_code' => 'GB',
            ],
            'shipping_method' => [
                'title' => 'Standard Shipping',
                'eta' => 'Estimated after production: 5–7 business days',
            ],
            'placed_at' => now(),
        ], $overrides));

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_name' => 'Custom Jersey',
            'product_slug' => 'custom-jersey',
            'sku' => 'NP-JERSEY-001',
            'quantity' => 1,
            'unit_price' => 80,
            'customization_unit_price' => 0,
            'line_total' => 80,
            'customization' => [],
            'is_digital' => false,
        ]);

        return $order->fresh(['items', 'shipments']);
    }
}
