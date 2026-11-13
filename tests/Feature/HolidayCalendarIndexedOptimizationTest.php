<?php

namespace Tests\Feature;

use App\Jobs\Shipping\RecalculateHolidayOrdersBatch;
use App\Jobs\Shipping\SendHolidayEtaNotification;
use App\Contracts\EmailService;
use App\Services\Email\TransactionalEmailManager;
use App\Models\HolidayCalendar;
use App\Models\HolidayCalendarDate;
use App\Models\ArtworkProof;
use App\Models\OrderItem;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Services\Shipping\HolidayCalendarIndexBuilder;
use App\Services\Shipping\HolidayCalendarRevisionStore;
use App\Services\Shipping\HolidayCalendarService;
use Carbon\Carbon;
use Database\Seeders\HolidayCalendarSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HolidayCalendarIndexedOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(HolidayCalendarSeeder::class);
    }

    private function service(): HolidayCalendarService
    {
        return app(HolidayCalendarService::class);
    }

    public function test_indexed_and_legacy_algorithms_have_identical_dates_and_holiday_details(): void
    {
        $service = $this->service();
        foreach (['GB', 'US', 'CA', 'AU', 'NZ', 'DE'] as $country) {
            for ($month = 1; $month <= 12; $month++) {
                $date = Carbon::create(2026, $month, 15, 9, 30);
                foreach ([0, 1, 3, 8, 12, 45, 100] as $days) {
                    config()->set('nextplay_shipping.holiday_index_enabled', false);
                    $legacy = $service->addBusinessDays($date, $days, $country);
                    config()->set('nextplay_shipping.holiday_index_enabled', true);
                    $indexed = $service->addBusinessDays($date, $days, $country);
                    $this->assertSame($legacy['target_date']->toDateString(), $indexed['target_date']->toDateString(), "{$country}/{$date}/{$days}");
                    $this->assertSame($legacy['holidays_encountered'], $indexed['holidays_encountered'], "{$country}/{$date}/{$days}");
                }
            }
        }
    }

    public function test_year_index_contains_monotonic_prefix_and_correct_leap_year_length(): void
    {
        $builder = app(HolidayCalendarIndexBuilder::class);
        $leap = $builder->build('GB', 2028, 0);
        $normal = $builder->build('GB', 2026, 0);
        $this->assertCount(367, $leap['prefix']);
        $this->assertCount(366, $normal['prefix']);
        $this->assertSame(366, $leap['days']);
        $this->assertSame(0, $normal['prefix'][0]);
        $this->assertSame(0, $leap['prefix'][0]);
        for ($i = 1; $i <= 366; $i++) {
            $this->assertContains($leap['prefix'][$i] - $leap['prefix'][$i - 1], [0, 1]);
        }
    }

    public function test_christmas_weekend_zero_day_and_year_boundary_parity(): void
    {
        foreach (['2026-12-24', '2026-12-25', '2026-12-31', '2028-02-28', '2028-02-29'] as $start) {
            foreach ([0, 1, 6, 150] as $days) {
                config()->set('nextplay_shipping.holiday_index_enabled', false);
                $before = $this->service()->addBusinessDays(Carbon::parse($start), $days, 'GB');
                config()->set('nextplay_shipping.holiday_index_enabled', true);
                $after = $this->service()->addBusinessDays(Carbon::parse($start), $days, 'GB');
                $this->assertSame($before['target_date']->toDateString(), $after['target_date']->toDateString());
                $this->assertSame($before['holidays_encountered'], $after['holidays_encountered']);
            }
        }
    }

    public function test_explicit_invalidation_advances_generation_and_refreshes_index(): void
    {
        $revisions = app(HolidayCalendarRevisionStore::class);
        $service = $this->service();
        $before = $revisions->current('United Kingdom', 2026);
        $service->addBusinessDays(Carbon::parse('2026-11-09'), 2, 'GB');
        $calendar = HolidayCalendar::query()->where('country_code', 'GB')->firstOrFail();
        HolidayCalendarDate::create([
            'holiday_calendar_id' => $calendar->id, 'date' => '2026-11-10', 'name' => 'Temporary Closure',
        ]);
        $service->clearHolidayCache('GB', 2026);
        $this->assertSame($before + 1, $revisions->current('GB', 2026));
        $result = $service->addBusinessDays(Carbon::parse('2026-11-09'), 2, 'United Kingdom');
        $this->assertSame('2026-11-12', $result['target_date']->toDateString());
        $this->assertSame('Temporary Closure', $result['holidays_encountered']['2026-11-10']);
    }

    public function test_warm_year_index_is_reused_within_a_service_instance_without_queries(): void
    {
        $service = $this->service();
        $service->addBusinessDays(Carbon::parse('2026-05-04'), 3, 'GB');
        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            $service->addBusinessDays(Carbon::parse('2026-06-01'), 4, 'GB');
            $this->assertCount(0, DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }

    private function order(string $number, array $shipping = []): Order
    {
        return Order::query()->create([
            'order_number' => $number, 'status' => 'processing',
            'payment_status' => 'paid', 'fulfillment_status' => 'unfulfilled',
            'currency' => 'USD', 'customer_name' => 'Holiday QA',
            'customer_email' => 'holiday-qa@example.test',
            'shipping_address' => ['country' => 'United Kingdom', 'country_code' => 'GB'],
            'shipping_method' => $shipping ?: ['minimum_days' => 6, 'maximum_days' => 10],
            'placed_at' => Carbon::parse('2026-12-10 10:00:00'),
        ]);
    }

    public function test_recalculating_unchanged_order_performs_no_extra_save_history_or_mail(): void
    {
        $order = $this->order('NP-HOLIDAY-QA-1');
        $service = $this->service();
        $this->assertTrue($service->recalculateOrderEta($order, notify: false));
        $firstTimestamp = $order->fresh()->updated_at;
        $firstHistoryCount = $order->histories()->count();
        $this->assertFalse($service->recalculateOrderEta($order, notify: false));
        $this->assertSame($firstTimestamp->toIso8601String(), $order->fresh()->updated_at->toIso8601String());
        $this->assertSame($firstHistoryCount, $order->histories()->count());
    }

    public function test_artwork_dependent_order_remains_pending_without_any_approved_proofs(): void
    {
        $order = $this->order('NP-HOLIDAY-QA-2', [
            'minimum_days' => 9, 'maximum_days' => 14,
            'delivery_estimate' => ['start_event' => 'artwork_confirmation',
                'total_minimum_business_days' => 9, 'total_maximum_business_days' => 14],
        ]);
        $this->assertFalse($this->service()->recalculateOrderEta($order, notify: false));
        $this->assertNull($order->fresh()->estimated_delivery_start_at);
        $this->assertSame('awaiting_artwork_approval', data_get($order->fresh()->holiday_adjustment_meta, 'eta_state'));
        $this->assertSame([9, 14], $this->service()->resolveOrderDays($order));
    }

    public function test_batch_retries_do_not_duplicate_order_history_or_outbox(): void
    {
        $order = $this->order('NP-HOLIDAY-QA-3');
        $order->estimated_delivery_start_at = Carbon::parse('2026-12-18');
        $order->estimated_delivery_end_at = Carbon::parse('2026-12-24');
        $order->save();
        $revision = app(HolidayCalendarRevisionStore::class)->advance('GB', 2026);
        $job = new RecalculateHolidayOrdersBatch('GB', 2026, $revision, [$order->id]);
        $job->handle($this->service(), app(HolidayCalendarRevisionStore::class));
        $this->assertSame(1, $order->histories()->where('title', 'Delivery Estimate Updated')->count());
        $this->assertSame(1, DB::table('holiday_eta_notifications')->where('order_id', $order->id)->count());
        $job->handle($this->service(), app(HolidayCalendarRevisionStore::class));
        $this->assertSame(1, $order->histories()->where('title', 'Delivery Estimate Updated')->count());
        $this->assertSame(1, DB::table('holiday_eta_notifications')->where('order_id', $order->id)->count());
    }

    public function test_delivered_shipment_is_not_mutated_by_open_order_recalculation(): void
    {
        $order = $this->order('NP-HOLIDAY-QA-4');
        $shipment = OrderShipment::query()->create([
            'order_id' => $order->id, 'shipment_number' => 'QA-HOLIDAY-DELIVERED',
            'status' => 'delivered', 'estimated_delivery_at' => Carbon::parse('2026-12-18 17:00:00'),
        ]);
        $this->service()->recalculateOrderEta($order, notify: false);
        $this->assertSame('2026-12-18', $shipment->fresh()->estimated_delivery_at->toDateString());
    }
    public function test_artwork_approval_anchor_uses_latest_current_approval_only(): void
    {
        $order = $this->order('NP-HOLIDAY-QA-5', [
            'minimum_days' => 6, 'maximum_days' => 10,
            'delivery_estimate' => ['start_event' => 'artwork_confirmation',
                'total_minimum_business_days' => 6, 'total_maximum_business_days' => 10],
        ]);
        $first = OrderItem::create([
            'order_id' => $order->id, 'product_name' => 'Home Kit',
            'quantity' => 1, 'unit_price' => 10, 'line_total' => 10,
        ]);
        $second = OrderItem::create([
            'order_id' => $order->id, 'product_name' => 'Away Kit',
            'quantity' => 1, 'unit_price' => 10, 'line_total' => 10,
        ]);
        ArtworkProof::create([
            'order_id' => $order->id, 'order_item_id' => $first->id,
            'version' => 1, 'status' => ArtworkProof::APPROVED,
            'approved_at' => Carbon::parse('2026-12-12 12:00:00'),
            'file_path' => 'qa-art-1.png', 'original_name' => 'art-1.png', 'mime_type' => 'image/png',
        ]);
        $secondProof = ArtworkProof::create([
            'order_id' => $order->id, 'order_item_id' => $second->id,
            'version' => 1, 'status' => ArtworkProof::PENDING,
            'file_path' => 'qa-art-2.png', 'original_name' => 'art-2.png', 'mime_type' => 'image/png',
        ]);
        $this->assertNull($this->service()->resolveEtaAnchor($order));
        $secondProof->update(['status' => ArtworkProof::APPROVED,
            'approved_at' => Carbon::parse('2026-12-15 13:00:00')]);
        $this->assertSame('2026-12-15 13:00:00',
            $this->service()->resolveEtaAnchor($order)->format('Y-m-d H:i:s'));
        $this->assertTrue($this->service()->recalculateOrderEta($order, notify: false));
        $this->assertSame('2026-12-15',
            Carbon::parse(data_get($order->fresh()->holiday_adjustment_meta, 'anchor_at'))->toDateString());
    }

    public function test_same_day_holidays_count_once_and_use_deterministic_name(): void
    {
        $base = HolidayCalendar::query()->where('country_code', 'GB')->where('year', 2026)->firstOrFail();
        $other = HolidayCalendar::create([
            'name' => 'Second closure calendar', 'country_code' => 'GB',
            'country_name' => 'United Kingdom', 'year' => 2026, 'is_active' => true,
        ]);
        HolidayCalendarDate::create([
            'holiday_calendar_id' => $base->id, 'date' => '2026-10-08', 'name' => 'First',
        ]);
        HolidayCalendarDate::create([
            'holiday_calendar_id' => $other->id, 'date' => '2026-10-08', 'name' => 'Second',
        ]);
        $service = $this->service();
        $service->clearHolidayCache('GB', 2026);
        $result = $service->addBusinessDays(Carbon::parse('2026-10-07'), 1, 'GB');
        $this->assertSame('2026-10-09', $result['target_date']->toDateString());
        $this->assertSame(['2026-10-08' => 'Second'], $result['holidays_encountered']);
    }

    public function test_delayed_notification_never_sends_an_outdated_delivery_estimate(): void
    {
        $order = $this->order('NP-HOLIDAY-QA-OUTBOX');
        $order->estimated_delivery_start_at = Carbon::parse('2026-12-22');
        $order->estimated_delivery_end_at = Carbon::parse('2026-12-30');
        $order->save();
        $key = hash('sha256', 'NP-HOLIDAY-QA-OUTBOX-OLD');
        DB::table('holiday_eta_notifications')->insert([
            'order_id' => $order->id,
            'event_key' => $key,
            'payload' => json_encode([
                'shipment_id' => null, 'old_estimate' => '22 Dec 2026',
                'reason' => 'Previous calendar change',
                'new_start_date' => '2026-12-20', 'new_end_date' => '2026-12-28',
            ]),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        // TransactionalEmailManager is final; mock the injected transport contract
        // instead. A superseded outbox entry must never touch the email transport.
        $transport = \Mockery::mock(EmailService::class);
        $transport->shouldNotReceive('sendNow');
        $transport->shouldNotReceive('queue');
        $transport->shouldNotReceive('safelyQueue');
        (new SendHolidayEtaNotification($key))->handle(new TransactionalEmailManager($transport));
        $this->assertNotNull(DB::table('holiday_eta_notifications')
            ->where('event_key', $key)->value('queued_at'));
    }

    public function test_changed_calendar_revision_makes_old_batch_skip_without_order_write(): void
    {
        $order = $this->order('NP-HOLIDAY-QA-6');
        $revisions = app(HolidayCalendarRevisionStore::class);
        $outdated = $revisions->advance('GB', 2026);
        $revisions->advance('GB', 2026);
        $job = new RecalculateHolidayOrdersBatch('GB', 2026, $outdated, [$order->id]);
        $job->handle($this->service(), $revisions);
        $this->assertSame(0, $order->histories()->count());
        $this->assertNull($order->fresh()->estimated_delivery_end_at);
    }
}
