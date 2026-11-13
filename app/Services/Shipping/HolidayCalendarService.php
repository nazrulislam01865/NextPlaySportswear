<?php

namespace App\Services\Shipping;

use App\Events\DeliveryEstimateUpdated;
use App\Jobs\Shipping\SendHolidayEtaNotification;
use App\Models\ArtworkProof;
use Illuminate\Support\Facades\DB;
use App\Models\HolidayCalendar;
use App\Models\HolidayCalendarDate;
use App\Models\Order;
use App\Models\OrderShipment;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Contracts\Cache\LockTimeoutException;

class HolidayCalendarService
{
    private array $yearIndexCache = [];
    private array $revisionCache = [];

    public function __construct(
        private readonly HolidayCalendarIndexBuilder $indexBuilder,
        private readonly HolidayCalendarRevisionStore $revisionStore,
        private readonly IndexedBusinessDayCalculator $indexedCalculator,
    ) {}

    private function yearIndex(string $country, int $year): array
    {
        // Fetch the committed generation once per service lifetime; immutable cache keys
        // prevent a late builder from overwriting a newer revision.
        $code = $this->revisionStore->countryCode($country);
        $identity = "{$code}:{$year}";
        $revision = $this->revisionCache[$identity]
            ??= $this->revisionStore->current($code, $year);
        $key = "holiday:index:{$code}:{$year}:policy1:rev{$revision}";
        if (isset($this->yearIndexCache[$key])) return $this->yearIndexCache[$key];

        $index = Cache::get($key);
        if (! is_array($index) || ! isset($index['prefix'], $index['holidays'])) {
            try {
                $index = Cache::lock("holiday:build:{$code}:{$year}:rev{$revision}", 15)->block(3,
                    fn () => Cache::remember($key, self::CACHE_TTL_SECONDS,
                        fn () => $this->indexBuilder->build($code, $year, $revision)));
            } catch (LockTimeoutException) {
                // A slow builder must not serve stale data or fail checkout.
                $index = $this->indexBuilder->build($code, $year, $revision);
            }
        }
        return $this->yearIndexCache[$key] = $index;
    }

    private const CACHE_TTL_SECONDS = 86400; // 24 hours

    /**
     * Retrieve all active holiday dates for a specific country and year.
     *
     * @return Collection<string, HolidayCalendarDate> Keyed by 'Y-m-d' date string
     */
    public function getActiveHolidayDates(string $country, int $year): Collection
    {
        $country = $this->normalizeCountry($country);
        $cacheKey = $this->cacheKey($country, $year);

        $cached = Cache::get($cacheKey);
        if ($cached instanceof Collection) {
            return $cached;
        }

        Cache::forget($cacheKey);

        $dates = HolidayCalendarDate::query()
            ->whereHas('calendar', function ($query) use ($country, $year) {
                $query->active()
                    ->forCountry($country)
                    ->forYear($year);
            })
            ->orderBy('date')->orderBy('holiday_calendar_id')->orderBy('id')
            ->get()
            ->keyBy(fn (HolidayCalendarDate $date) => $date->date->format('Y-m-d'));

        Cache::put($cacheKey, $dates, self::CACHE_TTL_SECONDS);

        return $dates;
    }

    /**
     * Check if a specific calendar date is a non-working day (weekend or country holiday).
     */
    public function isNonWorkingDay(CarbonInterface $date, string $country): bool
    {
        if ($date->isWeekend()) {
            return true;
        }

        return $this->getHolidayOnDate($date, $country) !== null;
    }

    /**
     * Retrieve holiday details for a given date and country, if one exists.
     */
    public function getHolidayOnDate(CarbonInterface $date, string $country): ?HolidayCalendarDate
    {
        $dates = $this->getActiveHolidayDates($country, (int) $date->year);

        return $dates->get($date->format('Y-m-d'));
    }

    /**
     * Advance a starting date by N business days, skipping weekends and active country holidays.
     *
     * @return array{target_date: Carbon, holidays_encountered: array<string, string>}
     */
    public function addBusinessDays(CarbonInterface $startDate, int $businessDays, string $country): array
    {
        if (config('nextplay_shipping.holiday_index_enabled', true)) {
            $result = $this->indexedCalculator->add(
                $startDate, $businessDays, fn (int $year) => $this->yearIndex($country, $year)
            );
            if (config('nextplay_shipping.holiday_shadow_compare', false)) {
                $legacy = $this->addBusinessDaysLegacy($startDate, $businessDays, $country);
                if ($legacy['target_date']->toDateString() !== $result['target_date']->toDateString()
                    || $legacy['holidays_encountered'] !== $result['holidays_encountered']) {
                    report(new \RuntimeException('Holiday calendar indexed/legacy calculation mismatch'));
                }
            }
            return $result;
        }
        return $this->addBusinessDaysLegacy($startDate, $businessDays, $country);
    }

    private function addBusinessDaysLegacy(CarbonInterface $startDate, int $businessDays, string $country): array
    {
        $current = Carbon::parse($startDate);
        $holidaysEncountered = [];

        if ($businessDays <= 0) {
            while ($this->isNonWorkingDay($current, $country)) {
                if ($holiday = $this->getHolidayOnDate($current, $country)) {
                    $holidaysEncountered[$current->format('Y-m-d')] = $holiday->name;
                }
                $current->addDay();
            }

            return [
                'target_date' => $current,
                'holidays_encountered' => $holidaysEncountered,
            ];
        }

        $daysAdded = 0;
        while ($daysAdded < $businessDays) {
            $current->addDay();

            if ($current->isWeekend()) {
                continue;
            }

            if ($holiday = $this->getHolidayOnDate($current, $country)) {
                $holidaysEncountered[$current->format('Y-m-d')] = $holiday->name;
                continue;
            }

            $daysAdded++;
        }

        return [
            'target_date' => $current,
            'holidays_encountered' => $holidaysEncountered,
        ];
    }

    /**
     * Calculate delivery window (start and end dates) for an order, taking production + transit
     * days into account and skipping weekends and active country holidays.
     *
     * @return array{
     *     start_date: Carbon,
     *     end_date: Carbon,
     *     formatted_range: string,
     *     min_business_days: int,
     *     max_business_days: int,
     *     business_days_label: string,
     *     is_holiday_adjusted: bool,
     *     affected_holidays: array<int, string>,
     *     holiday_details: array<int, array{date: string, name: string}>,
     *     adjustment_reason: ?string,
     *     country: string
     * }
     */
    public function calculateDeliveryWindow(
        CarbonInterface|string $startDate,
        int $minDays = 8,
        int $maxDays = 12,
        ?string $country = null
    ): array {
        $start = Carbon::parse($startDate);
        $country = $this->normalizeCountry($country);
        $minDays = max(1, $minDays);
        $maxDays = max($minDays, $maxDays);

        $startResult = $this->addBusinessDays($start, $minDays, $country);
        $endResult = $this->addBusinessDays($start, $maxDays, $country);

        $mergedHolidays = array_merge(
            $startResult['holidays_encountered'],
            $endResult['holidays_encountered']
        );

        $uniqueHolidayNames = array_values(array_unique(array_values($mergedHolidays)));
        $isHolidayAdjusted = ! empty($uniqueHolidayNames);

        $holidayDetails = [];
        foreach ($mergedHolidays as $dateStr => $name) {
            $holidayDetails[] = [
                'date' => $dateStr,
                'name' => $name,
            ];
        }

        $adjustmentReason = null;
        if ($isHolidayAdjusted) {
            $count = count($uniqueHolidayNames);
            if ($count === 1) {
                $adjustmentReason = "{$uniqueHolidayNames[0]} falls within your estimated delivery date. The estimate has been extended to the next available day.";
            } elseif ($count === 2) {
                $adjustmentReason = "{$uniqueHolidayNames[0]} and {$uniqueHolidayNames[1]} fall within your estimated delivery date. The estimate has been extended to the next available day.";
            } else {
                $allExceptLast = implode(', ', array_slice($uniqueHolidayNames, 0, -1));
                $last = end($uniqueHolidayNames);
                $adjustmentReason = "{$allExceptLast}, and {$last} fall within your estimated delivery date. The estimate has been extended to the next available day.";
            }
        }

        $startDateFormatted = $startResult['target_date']->format('d M');
        $endDateFormatted = $endResult['target_date']->format('d M Y');
        $formattedRange = "{$startDateFormatted} – {$endDateFormatted}";

        if ($startResult['target_date']->equalTo($endResult['target_date'])) {
            $formattedRange = $endResult['target_date']->format('d M Y');
        }

        return [
            'start_date' => $startResult['target_date'],
            'end_date' => $endResult['target_date'],
            'formatted_range' => $formattedRange,
            'min_business_days' => $minDays,
            'max_business_days' => $maxDays,
            'business_days_label' => $minDays === $maxDays ? "{$minDays} business days" : "{$minDays}–{$maxDays} business days",
            'is_holiday_adjusted' => $isHolidayAdjusted,
            'affected_holidays' => $uniqueHolidayNames,
            'holiday_details' => $holidayDetails,
            'adjustment_reason' => $adjustmentReason,
            'country' => $country,
        ];
    }

    /**
     * Recalculate ETA for a specific order based on active holiday calendars.
     * Dispatches DeliveryEstimateUpdated event if the ETA changes.
     */
    public function recalculateOrderEta(Order $order, ?string $customHolidayReason = null, bool $queueNotifications = false, bool $notify = true): bool
    {
        if (in_array($order->status, ['completed', 'delivered', 'cancelled', 'shipped'], true)) return false;
        $anchor = $this->resolveEtaAnchor($order);
        if ($anchor === null) {
            // Do not invent an approval timestamp or silently replace a pending ETA.
            $meta = (array) ($order->holiday_adjustment_meta ?? []);
            if (($meta['eta_state'] ?? null) !== 'awaiting_artwork_approval'
                || $order->estimated_delivery_start_at !== null || $order->estimated_delivery_end_at !== null) {
                $meta['eta_state'] = 'awaiting_artwork_approval';
                unset($meta['anchor_at'], $meta['range']);
                $order->estimated_delivery_start_at = null;
                $order->estimated_delivery_end_at = null;
                $order->holiday_adjustment_applied = false;
                $order->holiday_adjustment_reason = null;
                $order->holiday_adjustment_meta = $meta;
                $order->save();
            }
            return false;
        }

        $country = $this->resolveOrderCountry($order);
        [$minDays, $maxDays] = $this->resolveOrderDays($order);
        $window = $this->calculateDeliveryWindow($anchor, $minDays, $maxDays, $country);
        $currentStart = $order->estimated_delivery_start_at?->format('Y-m-d');
        $currentEnd = $order->estimated_delivery_end_at?->format('Y-m-d');
        $newStart = $window['start_date']->format('Y-m-d');
        $newEnd = $window['end_date']->format('Y-m-d');
        $datesChanged = ($currentStart !== $newStart || $currentEnd !== $newEnd);
        $reason = $customHolidayReason ?: $window['adjustment_reason'];
        $original = (array) ($order->holiday_adjustment_meta ?? []);
        // Ignore timestamps when deciding whether a write is necessary.
        $meta = array_merge($original, [
            'holidays' => $window['affected_holidays'],
            'range' => $window['formatted_range'], 'country' => $country,
            'eta_state' => 'confirmed',
            'anchor_at' => $anchor->toIso8601String(),
        ]);
        unset($meta['recalculated_at']);
        $semanticChanged = $datesChanged
            || (bool) $order->holiday_adjustment_applied !== (bool) $window['is_holiday_adjusted']
            || (string) $order->holiday_adjustment_reason !== (string) $reason
            || array_diff_assoc(array_filter($meta, 'is_scalar'), array_filter($original, 'is_scalar')) !== []
            || ($original['holidays'] ?? null) !== $meta['holidays'];
        if (! $semanticChanged) return false;

        $oldEstimate = $order->formattedEstimatedDelivery() ?: 'Previous Estimate';
        $order->estimated_delivery_start_at = $window['start_date'];
        $order->estimated_delivery_end_at = $window['end_date'];
        $order->holiday_adjustment_applied = $window['is_holiday_adjusted'];
        $order->holiday_adjustment_reason = $reason;
        $order->holiday_adjustment_meta = $meta;
        $order->save();
        if (! $datesChanged) return false;

        $descriptionReason = $reason ?: ($notify
            ? 'Holiday calendar delivery date adjustment.'
            : 'Artwork approval delivery schedule confirmed.');
        $history = $order->histories()->create([
            'status' => $order->status,
            'title' => 'Delivery Estimate Updated',
            'description' => "Delivery estimate updated to {$window['formatted_range']}. {$descriptionReason}",
            'metadata' => ['previous_estimate' => $oldEstimate,
                'updated_estimate' => $window['formatted_range'], 'holidays' => $window['affected_holidays']],
            'occurred_at' => now(),
        ]);
        $order->loadMissing('shipments');
        // Completed shipments are immutable, even if the parent remains open.
        $eligible = $order->shipments->filter(fn ($shipment) =>
            ! in_array($shipment->status, ['delivered', 'shipped', 'in_transit', 'out_for_delivery', 'cancelled'], true));
        foreach ($eligible as $shipment) {
            $shipment->old_estimated_delivery_at = $shipment->estimated_delivery_at;
            $shipment->estimated_delivery_at = $window['end_date']->copy()->setTime(17, 0, 0);
            $shipment->holiday_reason = $descriptionReason;
            $shipment->save();
            if ($notify) $this->emitEtaNotification($order, $shipment, $oldEstimate, $descriptionReason, $queueNotifications, (int) $history->id);
        }
        if ($eligible->isEmpty() && $order->shipments->isEmpty() && $notify) {
            $this->emitEtaNotification($order, null, $oldEstimate, $descriptionReason, $queueNotifications, (int) $history->id);
        }

        return true;
    }

    private function emitEtaNotification(Order $order, ?OrderShipment $shipment, string $old, string $reason, bool $queued, int $historyId): void
    {
        if (! $queued) {
            event(new DeliveryEstimateUpdated(shipment: $shipment, oldEstimate: $old,
                holidayReason: $reason, order: $order));
            return;
        }
        $key = hash('sha256', implode('|', [$order->id, $shipment?->id ?? 'order', $historyId]));
        $inserted = DB::table('holiday_eta_notifications')->insertOrIgnore([
            'order_id' => $order->id, 'event_key' => $key,
            'payload' => json_encode(['shipment_id' => $shipment?->id,
                'old_estimate' => $old, 'reason' => $reason,
                'new_start_date' => $order->estimated_delivery_start_at?->toDateString(),
                'new_end_date' => $order->estimated_delivery_end_at?->toDateString()]),
            'created_at' => now(), 'updated_at' => now(), 'last_dispatched_at' => now(),
        ]);
        if ($inserted) {
            SendHolidayEtaNotification::dispatch($key)
                ->onQueue(config('nextplay_shipping.holiday_queue', 'default'))->afterCommit();
        }
    }

    /** An artwork-dependent order waits for ALL current proofs; latest approval defines the anchor. */
    public function resolveEtaAnchor(Order $order): ?Carbon
    {
        $shipping = (array) ($order->shipping_method ?? []);
        if (data_get($shipping, 'delivery_estimate.start_event') !== 'artwork_confirmation') {
            return Carbon::parse($order->placed_at ?? $order->created_at ?? now());
        }
        $proofs = ArtworkProof::query()->current()
            ->where('artwork_proofs.order_id', $order->id)->get();
        if ($proofs->isEmpty() || $proofs->contains(fn ($p) => $p->status !== ArtworkProof::APPROVED || ! $p->approved_at)) {
            return null;
        }
        return Carbon::parse($proofs->max('approved_at'));
    }

    public function orderWindowTouchesYear(Order $order, int $year): bool
    {
        $anchor = $this->resolveEtaAnchor($order);
        if ($anchor === null) return false;
        $end = $order->estimated_delivery_end_at;
        if ($end && $anchor->year <= $year && $end->year >= $year) return true;
        [$min, $max] = $this->resolveOrderDays($order);
        return $anchor->year <= $year && $anchor->copy()->addDays($max * 3 + 20)->year >= $year;
    }

    public function openOrdersQuery(string $country): \Illuminate\Database\Eloquent\Builder
    {
        $variants = $this->countryVariants($this->normalizeCountry($country));
        return Order::query()->whereNotIn('status', ['completed', 'delivered', 'cancelled', 'shipped'])
            ->where(function ($query) use ($variants): void {
                foreach ($variants as $v) {
                    $query->orWhere('shipping_address->country', $v)
                        ->orWhere('shipping_address->address->country', $v)
                        ->orWhere('shipping_address->country_code', $v)
                        ->orWhere('shipping_address->address->country_code', $v);
                }
            });
    }

    /**
     * Recalculate ETAs for all open orders shipping to a specific country.
     *
     * @return array{total_open: int, updated: int, notified: int}
     */
    public function recalculateOpenOrdersForCountry(string $country, ?string $holidayReason = null): array
    {
        $updated = 0;
        $notified = 0;
        $total = 0;
        $this->openOrdersQuery($country)->with('shipments')->chunkById(150, function ($orders) use (&$updated, &$notified, &$total, $holidayReason): void {
            foreach ($orders as $order) {
                $total++;
                if ($this->recalculateOrderEta($order, $holidayReason)) {
                    $updated++;
                    $notified += max(1, $order->shipments->count());
                }
            }
        });
        return ['total_open' => $total, 'updated' => $updated, 'notified' => $notified];
    }

    /**
     * Clear cached holiday dates for a country/year, or everything.
     */
    public function clearHolidayCache(?string $country = null, ?int $year = null): void
    {
        $this->yearIndexCache = [];
        $this->revisionCache = [];
        if ($country !== null && $year !== null) {
            Cache::forget($this->cacheKey($this->normalizeCountry($country), $year));
            // Public clearing API invalidates both legacy and indexed representations.
            $this->revisionStore->advance($country, $year);
            return;
        }
        // Dynamic, targeted global invalidation: includes historical/deleted pairs.
        // Do not flush unrelated application cache entries.
        $pairs = DB::table('holiday_calendar_revisions')->select('country_code', 'year')->get()
            ->concat(HolidayCalendar::query()->select('country_code', 'year')->distinct()->get())
            ->unique(fn ($pair) => $this->revisionStore->countryCode($pair->country_code).':'.$pair->year);
        foreach ($pairs as $pair) {
            $code = $this->revisionStore->countryCode($pair->country_code);
            $this->clearHolidayCache($code, (int) $pair->year);
        }
    }

    private function cacheKey(string $country, int $year): string
    {
        $slug = Str::slug($country);

        return "holiday_calendar_dates:{$slug}:{$year}";
    }

    public function normalizeCountry(?string $country): string
    {
        $trimmed = trim((string) $country);
        if ($trimmed === '') {
            return 'United Kingdom';
        }

        $upper = strtoupper($trimmed);
        if ($upper === 'GB' || $upper === 'UK' || $upper === 'GREAT BRITAIN' || strcasecmp($trimmed, 'United Kingdom') === 0) {
            return 'United Kingdom';
        }
        if ($upper === 'US' || $upper === 'USA' || $upper === 'UNITED STATES OF AMERICA' || strcasecmp($trimmed, 'United States') === 0) {
            return 'United States';
        }
        if ($upper === 'CA' || strcasecmp($trimmed, 'Canada') === 0) {
            return 'Canada';
        }

        return \App\Support\CountryCatalog::find($upper)['name'] ?? $trimmed;
    }

    public function countryVariants(string $country): array
    {
        return match ($country) {
            'United Kingdom' => ['United Kingdom', 'GB', 'UK', 'Great Britain'],
            'United States' => ['United States', 'US', 'USA'],
            'Canada' => ['Canada', 'CA'],
            default => array_values(array_unique([$country, $this->revisionStore->countryCode($country)])),
        };
    }

    public function resolveOrderCountry(Order $order): string
    {
        $address = (array) ($order->shipping_address ?? []);
        $nestedAddress = (array) ($address['address'] ?? []);

        $country = $address['country']
            ?? $nestedAddress['country']
            ?? $address['country_code']
            ?? $nestedAddress['country_code']
            ?? 'United Kingdom';

        return $this->normalizeCountry($country);
    }

    /**
     * @return array{0: int, 1: int}
     */
    public function resolveOrderDays(Order $order): array
    {
        $shippingMethod = (array) ($order->shipping_method ?? []);
        $deliveryEstimate = (array) ($shippingMethod['delivery_estimate'] ?? []);

        $min = (int) ($deliveryEstimate['total_minimum_business_days'] ?? $shippingMethod['minimum_days'] ?? 8);
        $max = (int) ($deliveryEstimate['total_maximum_business_days'] ?? $shippingMethod['maximum_days'] ?? 12);

        return [max(1, $min), max($min, $max)];
    }
}
