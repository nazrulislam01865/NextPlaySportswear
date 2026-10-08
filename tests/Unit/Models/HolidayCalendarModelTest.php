<?php

namespace Tests\Unit\Models;

use App\Models\HolidayCalendar;
use App\Models\HolidayCalendarDate;
use Database\Seeders\HolidayCalendarSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HolidayCalendarModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(HolidayCalendarSeeder::class);
    }

    public function test_can_query_seeded_holiday_calendars_with_dates(): void
    {
        $calendars = HolidayCalendar::with('dates')->get();

        $this->assertGreaterThanOrEqual(4, $calendars->count());

        $uk = $calendars->firstWhere('name', 'UK Public Holidays 2026');
        $this->assertNotNull($uk);
        $this->assertSame('GB', $uk->country_code);
        $this->assertSame('United Kingdom', $uk->country_name);
        $this->assertSame(2026, $uk->year);
        $this->assertTrue($uk->is_active);
        $this->assertCount(8, $uk->dates);
        $this->assertSame('🇬🇧', $uk->flagEmoji());

        $usa = $calendars->firstWhere('name', 'USA Federal Holidays 2026');
        $this->assertNotNull($usa);
        $this->assertSame('US', $usa->country_code);
        $this->assertSame(11, $usa->dates->count());
        $this->assertSame('🇺🇸', $usa->flagEmoji());

        $canada = $calendars->firstWhere('name', 'Canada Holidays 2026');
        $this->assertNotNull($canada);
        $this->assertFalse($canada->is_active);
        $this->assertSame(10, $canada->dates->count());
        $this->assertSame('🇨🇦', $canada->flagEmoji());
    }

    public function test_scopes_work_correctly(): void
    {
        $activeCount = HolidayCalendar::query()->active()->count();
        $this->assertGreaterThanOrEqual(3, $activeCount);

        $ukCalendars = HolidayCalendar::query()->forCountry('United Kingdom')->forYear(2026)->get();
        $this->assertCount(2, $ukCalendars);
    }

    public function test_order_and_shipment_holiday_estimate_fields(): void
    {
        $order = new \App\Models\Order([
            'order_number' => 'NP-TEST-001',
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'estimated_delivery_start_at' => '2026-12-18',
            'estimated_delivery_end_at' => '2026-12-28',
            'holiday_adjustment_applied' => true,
            'holiday_adjustment_reason' => 'Christmas Day falls within your estimated delivery date. The estimate has been extended to the next available day.',
            'holiday_adjustment_meta' => ['holidays' => ['Christmas Day']],
        ]);

        $this->assertTrue($order->holiday_adjustment_applied);
        $this->assertSame('18 Dec – 28 Dec 2026', $order->formattedEstimatedDelivery());
        $this->assertSame('Christmas Day', $order->holiday_adjustment_meta['holidays'][0]);

        $shipment = new \App\Models\OrderShipment([
            'shipment_number' => 'SH-TEST-001',
            'estimated_delivery_at' => '2026-12-28 14:00:00',
            'old_estimated_delivery_at' => '2026-12-25 14:00:00',
            'holiday_reason' => 'Christmas Day carrier closure',
        ]);

        $this->assertSame('Christmas Day carrier closure', $shipment->holiday_reason);
        $this->assertNotNull($shipment->old_estimated_delivery_at);
    }
}
