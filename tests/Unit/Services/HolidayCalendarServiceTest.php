<?php

namespace Tests\Unit\Services;

use App\Events\DeliveryEstimateUpdated;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Services\Shipping\HolidayCalendarService;
use Carbon\Carbon;
use Database\Seeders\HolidayCalendarSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class HolidayCalendarServiceTest extends TestCase
{
    use RefreshDatabase;

    private HolidayCalendarService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(HolidayCalendarSeeder::class);
        $this->service = app(HolidayCalendarService::class);
        $this->service->clearHolidayCache();
    }

    public function test_add_business_days_skips_weekends_when_no_holidays(): void
    {
        // Wednesday Oct 7, 2026. Adding 3 business days: Thu 8, Fri 9, [Sat 10, Sun 11 skip], Mon 12.
        $start = Carbon::parse('2026-10-07');
        $result = $this->service->addBusinessDays($start, 3, 'United Kingdom');

        $this->assertSame('2026-10-12', $result['target_date']->format('Y-m-d'));
        $this->assertEmpty($result['holidays_encountered']);
    }

    public function test_add_business_days_skips_active_country_holidays(): void
    {
        // UK Christmas period 2026:
        // Dec 24 (Thu) is Christmas Eve Carrier Half-Day
        // Dec 25 (Fri) is Christmas Day (Holiday)
        // Dec 26 (Sat), Dec 27 (Sun) are weekends
        // Dec 28 (Mon) is Boxing Day Observed (Holiday)
        // Dec 29 (Tue) is Carrier Hub Maintenance Day (Holiday)
        // Dec 30 (Wed) is next available business day!
        $start = Carbon::parse('2026-12-24');
        $result = $this->service->addBusinessDays($start, 1, 'United Kingdom');

        $this->assertSame('2026-12-30', $result['target_date']->format('Y-m-d'));
        $this->assertArrayHasKey('2026-12-25', $result['holidays_encountered']);
        $this->assertSame('Christmas Day', $result['holidays_encountered']['2026-12-25']);
        $this->assertArrayHasKey('2026-12-28', $result['holidays_encountered']);
        $this->assertSame('Boxing Day (Observed)', $result['holidays_encountered']['2026-12-28']);
        $this->assertArrayHasKey('2026-12-29', $result['holidays_encountered']);
        $this->assertSame('Carrier Hub Maintenance Day', $result['holidays_encountered']['2026-12-29']);
    }

    public function test_inactive_calendar_does_not_affect_business_days(): void
    {
        // Canada calendar is inactive in seeds
        // Start: Thursday 2026-12-24. Canada Christmas Dec 25 should NOT be skipped since calendar is inactive.
        // Adding 1 business day should land on Dec 25.
        $start = Carbon::parse('2026-12-24');
        $result = $this->service->addBusinessDays($start, 1, 'Canada');

        $this->assertSame('2026-12-25', $result['target_date']->format('Y-m-d'));
        $this->assertEmpty($result['holidays_encountered']);
    }

    public function test_calculate_delivery_window_detects_holidays_and_builds_human_reason(): void
    {
        // Placed Dec 10, 2026
        // Min 6 business days -> Fri Dec 18, 2026
        // Max 10 business days -> skips Dec 24, 25, 28, 29 -> lands on Wed Dec 30, 2026
        $window = $this->service->calculateDeliveryWindow('2026-12-10', 6, 10, 'United Kingdom');

        $this->assertSame('2026-12-18', $window['start_date']->format('Y-m-d'));
        $this->assertSame('2026-12-30', $window['end_date']->format('Y-m-d'));
        $this->assertSame('18 Dec – 30 Dec 2026', $window['formatted_range']);
        $this->assertTrue($window['is_holiday_adjusted']);
        $this->assertContains('Christmas Day', $window['affected_holidays']);
        $this->assertNotNull($window['adjustment_reason']);
        $this->assertStringContainsString('within your estimated delivery date', $window['adjustment_reason']);
        $this->assertStringContainsString('The estimate has been extended to the next available day', $window['adjustment_reason']);
    }

    public function test_recalculate_order_eta_updates_order_and_dispatches_notification_event(): void
    {
        Event::fake([DeliveryEstimateUpdated::class]);

        $order = Order::query()->create([
            'order_number' => 'NP-TEST-12345',
            'status' => 'processing',
            'payment_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'USD',
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'subtotal' => 100,
            'grand_total' => 100,
            'shipping_address' => [
                'country' => 'United Kingdom',
                'address_line_1' => '123 High Street',
                'city' => 'London',
                'postal_code' => 'SW1A 1AA',
            ],
            'shipping_method' => [
                'title' => 'Standard Shipping',
                'minimum_days' => 6,
                'maximum_days' => 10,
            ],
            'placed_at' => Carbon::parse('2026-12-10 10:00:00'),
            'estimated_delivery_start_at' => '2026-12-18',
            'estimated_delivery_end_at' => '2026-12-25', // Old estimate before holiday adjustment
        ]);

        $shipment = OrderShipment::query()->create([
            'order_id' => $order->id,
            'shipment_number' => 'SH-TEST-12345',
            'status' => 'preparing',
            'carrier' => 'UPS Ground',
            'estimated_delivery_at' => Carbon::parse('2026-12-25 17:00:00'),
        ]);

        $updated = $this->service->recalculateOrderEta($order);

        $this->assertTrue($updated);
        $order->refresh();
        $this->assertSame('2026-12-30', $order->estimated_delivery_end_at->format('Y-m-d'));
        $this->assertTrue($order->holiday_adjustment_applied);
        $this->assertNotNull($order->holiday_adjustment_reason);

        // Check history was recorded
        $this->assertTrue($order->histories()->where('title', 'Delivery Estimate Updated')->exists());

        // Check shipment was updated
        $shipment->refresh();
        $this->assertSame('2026-12-30', $shipment->estimated_delivery_at->format('Y-m-d'));

        // Check DeliveryEstimateUpdated event was dispatched
        Event::assertDispatched(DeliveryEstimateUpdated::class, function ($event) use ($shipment) {
            return $event->shipment->id === $shipment->id
                && str_contains($event->holidayReason, 'within your estimated delivery date');
        });
    }

    public function test_recalculate_open_orders_for_country_processes_matching_orders(): void
    {
        Event::fake([DeliveryEstimateUpdated::class]);

        // Order 1 in UK
        Order::query()->create([
            'order_number' => 'NP-OPEN-UK-1',
            'status' => 'processing',
            'payment_status' => 'paid',
            'customer_name' => 'Alice UK',
            'customer_email' => 'alice@example.co.uk',
            'shipping_address' => ['country' => 'United Kingdom'],
            'placed_at' => Carbon::parse('2026-12-10'),
            'estimated_delivery_start_at' => '2026-12-18',
            'estimated_delivery_end_at' => '2026-12-24',
        ]);

        // Order 2 in USA (should not be affected when updating UK)
        Order::query()->create([
            'order_number' => 'NP-OPEN-US-2',
            'status' => 'processing',
            'payment_status' => 'paid',
            'customer_name' => 'Bob USA',
            'customer_email' => 'bob@example.com',
            'shipping_address' => ['country' => 'United States'],
            'placed_at' => Carbon::parse('2026-12-10'),
            'estimated_delivery_start_at' => '2026-12-18',
            'estimated_delivery_end_at' => '2026-12-24',
        ]);

        $summary = $this->service->recalculateOpenOrdersForCountry('United Kingdom');

        $this->assertSame(1, $summary['total_open']);
        $this->assertSame(1, $summary['updated']);
    }
}
