<?php

namespace Tests\Feature;

use App\Events\DeliveryEstimateUpdated;
use App\Jobs\Shipping\RecalculateHolidayOrdersBatch;
use App\Jobs\Shipping\ScheduleHolidayRecalculation;
use App\Jobs\Shipping\SendHolidayEtaNotification;
use App\Http\Middleware\EnforceCustomerSessionVersion;
use App\Models\HolidayCalendar;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderShipment;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\Order\OrderExperienceService;
use App\Services\Shipping\HolidayCalendarService;
use App\Services\Shipping\HolidayCalendarRevisionStore;
use App\Services\Shipping\ShippingMethodService;
use App\Support\AdminRbac;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class HolidayCalendarEndToEndFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        AdminRbac::syncDefaults(true);

        $this->admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
        ]);
    }

    /**
     * Test the complete 7-step lifecycle from the design blueprint:
     * 1. Admin Creates Calendar & Non-Delivery Dates
     * 2. System Stores Dates & Invalidates Cache
     * 3. Recalculate Open Orders for that Country
     * 4. Check Delivery Dates against new non-delivery dates
     * 5. Update Order ETA & Metadata
     * 6. Notify Customer via DeliveryEstimateUpdated Event / Email
     * 7. Storefront displays (Checkout, Confirmation, Tracking, Account)
     */
    public function test_complete_holiday_calendar_flow_from_admin_to_customer_storefront(): void
    {
        // Keep the calendar and checkout fixtures deterministic in any test year.
        $this->travelTo(Carbon::parse('2026-11-13 10:00:00'));
        Event::fake([DeliveryEstimateUpdated::class]);
        // Calendar edits now enqueue jobs; run the worker explicitly to test its
        // persisted ETA and outbox contract, without running a sync queue inline.
        Queue::fake([
            ScheduleHolidayRecalculation::class,
            RecalculateHolidayOrdersBatch::class,
            SendHolidayEtaNotification::class,
        ]);

        $holidayService = app(HolidayCalendarService::class);
        $holidayService->clearHolidayCache();

        // -----------------------------------------------------------------
        // PRE-CONDITION: Create an active open order in the UK before holiday
        // -----------------------------------------------------------------
        $order = Order::query()->create([
            'user_id' => $this->customer->id,
            'order_number' => 'NP-E2E-77788',
            'status' => 'processing',
            'payment_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'GBP',
            'customer_name' => 'Jane Smith',
            'customer_email' => 'jane.smith@example.co.uk',
            'subtotal' => 120,
            'grand_total' => 120,
            'shipping_address' => [
                'name' => 'Jane Smith',
                'address_line_1' => '221B Baker St',
                'city' => 'London',
                'postal_code' => 'NW1 6XE',
                'country' => 'United Kingdom',
                'country_code' => 'GB',
            ],
            'shipping_method' => [
                'title' => 'Standard Tracked',
                'minimum_days' => 6,
                'maximum_days' => 10,
            ],
            'placed_at' => Carbon::parse('2026-12-10 10:00:00'),
            'estimated_delivery_start_at' => Carbon::parse('2026-12-18'),
            'estimated_delivery_end_at' => Carbon::parse('2026-12-24'),
            'holiday_adjustment_applied' => false,
        ]);

        $shipment = OrderShipment::query()->create([
            'order_id' => $order->id,
            'shipment_number' => 'SHP-E2E-777',
            'carrier' => 'Royal Mail',
            'service' => 'Tracked 48',
            'status' => 'preparing',
            'estimated_delivery_at' => Carbon::parse('2026-12-24 18:00:00'),
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_name' => 'Elite Football Kit',
            'product_slug' => 'elite-football-kit',
            'sku' => 'NP-KIT-001',
            'quantity' => 1,
            'unit_price' => 120,
            'line_total' => 120,
            'customization' => [],
        ]);

        // -----------------------------------------------------------------
        // STEP 1 & 2: Admin creates Holiday Calendar with dates (Screen 1 & 2)
        // -----------------------------------------------------------------
        $nearHolidayDate = now()->isWeekend()
            ? now()->next(Carbon::TUESDAY)->format('Y-m-d')
            : now()->addWeekday()->format('Y-m-d');

        $postData = [
            'name' => 'UK National Holidays 2026',
            'country_code' => 'GB',
            'country_name' => 'United Kingdom',
            'year' => 2026,
            'is_active' => 1,
            'description' => 'Official national bank holidays for the United Kingdom',
            'dates' => [
                ['date' => $nearHolidayDate, 'name' => 'Autumn Bank Holiday', 'notes' => 'Seasonal holiday'],
                ['date' => '2026-12-24', 'name' => 'Christmas Eve Carrier Closure', 'notes' => 'Early cutoff'],
                ['date' => '2026-12-25', 'name' => 'Christmas Day', 'notes' => 'Public Holiday'],
                ['date' => '2026-12-28', 'name' => 'Boxing Day (Observed)', 'notes' => 'Bank Holiday'],
            ],
        ];

        $adminResponse = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.holiday-calendars.store'), $postData);

        $adminResponse->assertRedirect(route('admin.holiday-calendars.index'));
        $this->assertDatabaseHas('holiday_calendars', [
            'name' => 'UK National Holidays 2026',
            'country_code' => 'GB',
            'year' => 2026,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('holiday_calendar_dates', [
            'name' => 'Christmas Day',
        ]);

        // -----------------------------------------------------------------
        // STEP 3, 4, 5 & 6: Simulate the queued recalculation worker.
        // The separate OrderRecalculationAndNotificationTest covers the legacy
        // synchronous Artisan command. The optimized flow uses the outbox.
        // -----------------------------------------------------------------
        $revisions = app(HolidayCalendarRevisionStore::class);
        $revision = $revisions->current('GB', 2026);
        $this->assertGreaterThan(0, $revision);
        $batch = new RecalculateHolidayOrdersBatch('GB', 2026, $revision, [$order->id]);
        $batch->handle($holidayService, $revisions);

        // Verify Step 5: Order ETA is updated and holiday reason is recorded
        $order->refresh();
        $this->assertTrue($order->holiday_adjustment_applied);
        $this->assertNotNull($order->holiday_adjustment_reason);
        $this->assertStringContainsString('Christmas Day', $order->holiday_adjustment_reason);
        $this->assertSame('2026-12-29', $order->estimated_delivery_end_at->format('Y-m-d'));

        // Verify shipment is updated
        $shipment->refresh();
        $this->assertSame('2026-12-29', $shipment->estimated_delivery_at->format('Y-m-d'));
        $this->assertNotNull($shipment->holiday_reason);

        // Step 6: the async path records a durable notification outbox entry.
        // It intentionally does not dispatch the legacy immediate event.
        $outbox = DB::table('holiday_eta_notifications')
            ->where('order_id', $order->id)->first();
        $this->assertNotNull($outbox);
        $payload = json_decode($outbox->payload, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($shipment->id, (int) $payload['shipment_id']);
        $this->assertSame('2026-12-29', $payload['new_end_date']);
        $this->assertStringContainsString('Christmas Day', $payload['reason']);
        Event::assertNotDispatched(DeliveryEstimateUpdated::class);

        // A retried job must not duplicate customer notification requests.
        $batch->handle($holidayService, $revisions);
        $this->assertSame(1, DB::table('holiday_eta_notifications')
            ->where('order_id', $order->id)->count());

        // -----------------------------------------------------------------
        // STEP 7 & SCREEN 3: Checkout Shipping Method with Holiday Notice
        // -----------------------------------------------------------------
        ShippingMethod::query()->create([
            'name' => 'Royal Mail Tracked 24',
            'code' => 'rm-tracked-24',
            'minimum_days' => 6,
            'maximum_days' => 10,
            'charge_amount' => 15.00,
            'is_active' => true,
        ]);

        $shippingService = app(ShippingMethodService::class);
        $methods = $shippingService->availableMethods(
            ['subtotal' => 120, 'quantity' => 1],
            ['country' => 'United Kingdom', 'country_code' => 'GB', 'postal_code' => 'NW1 6XE']
        );

        $method = collect($methods)->firstWhere('code', 'rm-tracked-24');
        $this->assertNotNull($method);
        $this->assertTrue((bool) ($method['holiday_adjusted'] ?? false));
        $this->assertStringContainsString('Autumn Bank Holiday', (string) $method['holiday_notice']);

        // -----------------------------------------------------------------
        // STEP 7 & SCREEN 4: Storefront Order Confirmation
        // -----------------------------------------------------------------
        session()->put('nextplay_checkout', [
            'placed_order' => app(OrderExperienceService::class)->orderSnapshot($order),
        ]);

        // The confirmation endpoint only exposes a live order to its owner.
        // The earlier admin request changed the default auth guard, so
        // explicitly authenticate the customer instead of rendering a demo.
        $confirmationResponse = $this->withSession([
            EnforceCustomerSessionVersion::SESSION_KEY => (int) $this->customer->auth_session_version,
        ])->actingAs($this->customer, 'web')->get(route('order.confirmation'));
        $confirmationResponse->assertOk();
        $confirmationResponse->assertSee($order->order_number);
        $confirmationResponse->assertSee('Holiday Adjusted');
        $confirmationResponse->assertSee('Delivery Window Notice');

        // -----------------------------------------------------------------
        // STEP 7 & SCREEN 5: Storefront Order Tracking
        // -----------------------------------------------------------------
        $this->post(route('orders.track.lookup'), [
            'order_number' => $order->order_number,
            'email' => 'jane.smith@example.co.uk',
        ])->assertRedirect(route('orders.track'));

        $trackingResponse = $this->get(route('orders.track'));
        $trackingResponse->assertOk();
        $trackingResponse->assertSee($order->order_number);
        $trackingResponse->assertSee('Holiday Notice');

        // -----------------------------------------------------------------
        // STEP 7 & SCREEN 7: Customer Account Order Details
        // -----------------------------------------------------------------
        $accountResponse = $this->withSession([
            EnforceCustomerSessionVersion::SESSION_KEY => (int) $this->customer->auth_session_version,
        ])->actingAs($this->customer, 'web')->get(route('account.orders.show', $order));

        $accountResponse->assertOk();
        $accountResponse->assertSee('Holiday Adjusted');
        $accountResponse->assertSee($order->holiday_adjustment_reason);
    }
}
