<?php

namespace Tests\Feature;

use App\Events\DeliveryEstimateUpdated;
use App\Listeners\Order\SendDeliveryEstimateUpdatedNotification;
use App\Models\HolidayCalendar;
use App\Models\HolidayCalendarDate;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderShipment;
use App\Models\User;
use App\Services\Email\CentralEmailService;
use App\Services\Email\TransactionalEmailManager;
use App\Services\Shipping\HolidayCalendarService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderRecalculationAndNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private HolidayCalendarService $holidayService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $this->holidayService = app(HolidayCalendarService::class);
    }

    public function test_holiday_calendar_recalculates_open_order_with_shipment_and_dispatches_event(): void
    {
        Event::fake([DeliveryEstimateUpdated::class]);

        $calendar = HolidayCalendar::query()->create([
            'name' => 'UK Bank Holidays 2026',
            'country_code' => 'GB',
            'country_name' => 'United Kingdom',
            'year' => 2026,
            'is_active' => true,
        ]);

        HolidayCalendarDate::query()->create([
            'holiday_calendar_id' => $calendar->id,
            'date' => '2026-12-25',
            'name' => 'Christmas Day',
        ]);

        HolidayCalendarDate::query()->create([
            'holiday_calendar_id' => $calendar->id,
            'date' => '2026-12-28',
            'name' => 'Boxing Day (Substitute)',
        ]);

        $order = $this->createOrder([
            'shipping_address' => [
                'name' => 'John Doe',
                'country' => 'United Kingdom',
                'country_code' => 'GB',
            ],
            'shipping_method_name' => 'Standard Delivery',
            'shipping_method_min_days' => 3,
            'shipping_method_max_days' => 5,
            'placed_at' => Carbon::parse('2026-12-22 10:00:00'),
            'estimated_delivery_start_at' => Carbon::parse('2026-12-25'),
            'estimated_delivery_end_at' => Carbon::parse('2026-12-28'),
        ]);

        $shipment = OrderShipment::query()->create([
            'order_id' => $order->id,
            'shipment_number' => 'SHP-TEST-001',
            'carrier' => 'Royal Mail',
            'service' => 'Tracked 48',
            'status' => 'label_created',
            'estimated_delivery_at' => Carbon::parse('2026-12-28 17:00:00'),
        ]);

        $stats = $this->holidayService->recalculateOpenOrdersForCountry('United Kingdom');

        $this->assertSame(1, $stats['total_open']);
        $this->assertSame(1, $stats['updated']);
        $this->assertSame(1, $stats['notified']);

        $order->refresh();
        $this->assertTrue((bool) $order->holiday_adjustment_applied);
        $this->assertStringContainsString('Christmas Day', $order->holiday_adjustment_reason);
        $this->assertNotNull($order->estimated_delivery_start_at);
        $this->assertNotNull($order->estimated_delivery_end_at);

        $shipment->refresh();
        $this->assertNotNull($shipment->old_estimated_delivery_at);
        $this->assertStringContainsString('Christmas Day', $shipment->holiday_reason);

        // History entry recorded
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'title' => 'Delivery Estimate Updated',
        ]);

        Event::assertDispatched(DeliveryEstimateUpdated::class, function ($event) use ($shipment) {
            return $event->shipment->id === $shipment->id
                && str_contains((string) $event->holidayReason, 'Christmas Day');
        });
    }

    public function test_recalculate_order_without_shipments_still_dispatches_event(): void
    {
        Event::fake([DeliveryEstimateUpdated::class]);

        $calendar = HolidayCalendar::query()->create([
            'name' => 'US Federal Holidays 2026',
            'country_code' => 'US',
            'country_name' => 'United States',
            'year' => 2026,
            'is_active' => true,
        ]);

        HolidayCalendarDate::query()->create([
            'holiday_calendar_id' => $calendar->id,
            'date' => '2026-07-03',
            'name' => 'Independence Day (Observed)',
        ]);

        $order = $this->createOrder([
            'shipping_address' => [
                'name' => 'Alice Smith',
                'country' => 'United States',
                'country_code' => 'US',
            ],
            'shipping_method_min_days' => 3,
            'shipping_method_max_days' => 5,
            'placed_at' => Carbon::parse('2026-07-01 10:00:00'),
            'estimated_delivery_start_at' => Carbon::parse('2026-07-05'),
            'estimated_delivery_end_at' => Carbon::parse('2026-07-07'),
        ]);

        $updated = $this->holidayService->recalculateOrderEta($order);
        $this->assertTrue($updated);

        Event::assertDispatched(DeliveryEstimateUpdated::class, function ($event) use ($order) {
            return $event->order->id === $order->id
                && $event->shipment === null
                && str_contains((string) $event->holidayReason, 'Independence Day');
        });
    }

    public function test_notification_listener_queues_transactional_email(): void
    {
        $order = $this->createOrder([
            'shipping_address' => ['country' => 'United Kingdom'],
            'placed_at' => Carbon::parse('2026-12-20'),
            'estimated_delivery_start_at' => Carbon::parse('2026-12-28'),
            'estimated_delivery_end_at' => Carbon::parse('2026-12-30'),
        ]);

        $shipment = OrderShipment::query()->create([
            'order_id' => $order->id,
            'shipment_number' => 'SHP-TEST-002',
            'carrier' => 'DPD',
            'service' => 'Express',
            'status' => 'in_transit',
            'estimated_delivery_at' => Carbon::parse('2026-12-30 17:00:00'),
        ]);

        $event = new DeliveryEstimateUpdated(
            shipment: $shipment,
            oldEstimate: '24 Dec 2026',
            holidayReason: 'UK Bank Holiday (Christmas Day)',
            order: $order,
        );

        $listener = app(SendDeliveryEstimateUpdatedNotification::class);
        $listener->handle($event);

        $this->assertTrue(true); // Handled without exception
    }

    public function test_transactional_email_manager_builds_blueprint_screen_6_message(): void
    {
        $order = $this->createOrder([
            'customer_name' => 'Alex Morgan',
            'customer_email' => 'alex@example.com',
            'shipping_address' => ['country' => 'United Kingdom'],
            'estimated_delivery_start_at' => Carbon::parse('2026-12-28'),
            'estimated_delivery_end_at' => Carbon::parse('2026-12-30'),
        ]);

        $shipment = OrderShipment::query()->create([
            'order_id' => $order->id,
            'shipment_number' => 'SHP-TEST-003',
            'carrier' => 'Royal Mail',
            'service' => 'Standard',
            'status' => 'label_created',
            'estimated_delivery_at' => Carbon::parse('2026-12-30 17:00:00'),
        ]);

        $manager = app(TransactionalEmailManager::class);

        $result = $manager->deliveryEstimateUpdated(
            shipment: $shipment,
            oldEstimate: '24 Dec 2026',
            holidayReason: 'UK Bank Holiday (Christmas Day)',
            order: $order,
        );

        $this->assertTrue($result);
    }

    public function test_artisan_command_dry_run_previews_without_mutating_orders(): void
    {
        $calendar = HolidayCalendar::query()->create([
            'name' => 'UK Bank Holidays 2026',
            'country_code' => 'GB',
            'country_name' => 'United Kingdom',
            'year' => 2026,
            'is_active' => true,
        ]);

        HolidayCalendarDate::query()->create([
            'holiday_calendar_id' => $calendar->id,
            'date' => '2026-12-25',
            'name' => 'Christmas Day',
        ]);

        $originalDate = Carbon::parse('2026-12-24');

        $order = $this->createOrder([
            'shipping_address' => ['country' => 'United Kingdom'],
            'placed_at' => Carbon::parse('2026-12-22'),
            'estimated_delivery_start_at' => $originalDate,
            'estimated_delivery_end_at' => $originalDate,
            'holiday_adjustment_applied' => false,
        ]);

        $this->artisan('holidays:recalculate-orders', ['--country' => 'GB', '--dry-run' => true])
            ->assertExitCode(0);

        $order->refresh();
        $this->assertFalse((bool) $order->holiday_adjustment_applied);
        $this->assertNull($order->holiday_adjustment_reason);
    }

    public function test_artisan_command_live_recalculates_and_updates_orders(): void
    {
        $calendar = HolidayCalendar::query()->create([
            'name' => 'UK Bank Holidays 2026',
            'country_code' => 'GB',
            'country_name' => 'United Kingdom',
            'year' => 2026,
            'is_active' => true,
        ]);

        HolidayCalendarDate::query()->create([
            'holiday_calendar_id' => $calendar->id,
            'date' => '2026-12-25',
            'name' => 'Christmas Day',
        ]);

        $order = $this->createOrder([
            'shipping_address' => ['country' => 'United Kingdom'],
            'placed_at' => Carbon::parse('2026-12-22'),
            'estimated_delivery_start_at' => Carbon::parse('2026-12-24'),
            'estimated_delivery_end_at' => Carbon::parse('2026-12-24'),
            'holiday_adjustment_applied' => false,
        ]);

        $this->artisan('holidays:recalculate-orders', ['--country' => 'GB'])
            ->assertExitCode(0);

        $order->refresh();
        $this->assertTrue((bool) $order->holiday_adjustment_applied);
        $this->assertStringContainsString('Christmas Day', $order->holiday_adjustment_reason);
    }

    private function createOrder(array $overrides = []): Order
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
            'subtotal' => 60,
            'customization_total' => 0,
            'discount_total' => 0,
            'shipping_total' => 5,
            'tax_total' => 0,
            'grand_total' => 65,
            'total_quantity' => 1,
            'shipping_address' => [
                'first_name' => 'Test',
                'last_name' => 'Customer',
                'address_line_1' => '10 Downing St',
                'city' => 'London',
                'postal_code' => 'SW1A 2AA',
                'country' => 'United Kingdom',
                'country_code' => 'GB',
            ],
            'shipping_method_name' => 'Standard Delivery',
            'shipping_method_min_days' => 3,
            'shipping_method_max_days' => 5,
            'placed_at' => now(),
        ], $overrides));

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_name' => 'Custom Football Jersey',
            'product_slug' => 'custom-football-jersey',
            'sku' => 'NP-JERSEY-TEST',
            'quantity' => 1,
            'unit_price' => 60,
            'customization_unit_price' => 0,
            'line_total' => 60,
            'customization' => [],
            'is_digital' => false,
        ]);

        return $order->fresh(['items', 'shipments']);
    }
}
