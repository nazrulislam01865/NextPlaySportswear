<?php

namespace App\Services\Shipping;

use App\Events\DeliveryEstimateUpdated;
use App\Models\HolidayCalendar;
use App\Models\HolidayCalendarDate;
use App\Models\Order;
use App\Models\OrderShipment;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class HolidayCalendarService
{
    private const CACHE_TTL_SECONDS = 86400; // 24 hours

    /**
     * Retrieve all active holiday dates for a specific country and year.
     *
     * @return Collection<string, HolidayCalendarDate> Keyed by 'Y-m-d' date string
     */
    public function getActiveHolidayDates(string $country, int $year): Collection
    {
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
            ->orderBy('date')
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
    public function recalculateOrderEta(Order $order, ?string $customHolidayReason = null): bool
    {
        $country = $this->resolveOrderCountry($order);
        [$minDays, $maxDays] = $this->resolveOrderDays($order);

        $placedDate = $order->placed_at ?? $order->created_at ?? now();
        $window = $this->calculateDeliveryWindow($placedDate, $minDays, $maxDays, $country);

        $currentStart = $order->estimated_delivery_start_at?->format('Y-m-d');
        $currentEnd = $order->estimated_delivery_end_at?->format('Y-m-d');
        $newStart = $window['start_date']->format('Y-m-d');
        $newEnd = $window['end_date']->format('Y-m-d');

        $isChanged = ($currentStart !== $newStart || $currentEnd !== $newEnd);

        // Always update order with fresh calculated data
        $oldEstimate = $order->formattedEstimatedDelivery() ?: 'Previous Estimate';

        $order->estimated_delivery_start_at = $window['start_date'];
        $order->estimated_delivery_end_at = $window['end_date'];
        $order->holiday_adjustment_applied = $window['is_holiday_adjusted'];
        $order->holiday_adjustment_reason = $customHolidayReason ?: $window['adjustment_reason'];
        $order->holiday_adjustment_meta = [
            'holidays' => $window['affected_holidays'],
            'range' => $window['formatted_range'],
            'country' => $country,
            'recalculated_at' => now()->toIso8601String(),
        ];
        $order->save();

        if (! $isChanged) {
            return false;
        }

        // Update active shipments and notify customer
        $order->loadMissing('shipments');
        $reason = $customHolidayReason ?: ($window['adjustment_reason'] ?: 'Holiday calendar delivery date adjustment.');

        if ($order->shipments->isNotEmpty()) {
            foreach ($order->shipments as $shipment) {
                $shipment->old_estimated_delivery_at = $shipment->estimated_delivery_at;
                $shipment->estimated_delivery_at = $window['end_date']->copy()->setTime(17, 0, 0);
                $shipment->holiday_reason = $reason;
                $shipment->save();

                event(new DeliveryEstimateUpdated(
                    shipment: $shipment,
                    oldEstimate: $oldEstimate,
                    holidayReason: $reason,
                    order: $order,
                ));
            }
        } else {
            event(new DeliveryEstimateUpdated(
                shipment: null,
                oldEstimate: $oldEstimate,
                holidayReason: $reason,
                order: $order,
            ));
        }

        // Record history entry
        $order->histories()->create([
            'status' => $order->status,
            'title' => 'Delivery Estimate Updated',
            'description' => "Delivery estimate updated to {$window['formatted_range']}. {$reason}",
            'metadata' => [
                'previous_estimate' => $oldEstimate,
                'updated_estimate' => $window['formatted_range'],
                'holidays' => $window['affected_holidays'],
            ],
            'occurred_at' => now(),
        ]);

        return true;
    }

    /**
     * Recalculate ETAs for all open orders shipping to a specific country.
     *
     * @return array{total_open: int, updated: int, notified: int}
     */
    public function recalculateOpenOrdersForCountry(string $country, ?string $holidayReason = null): array
    {
        $normalizedCountry = $this->normalizeCountry($country);
        $countryVariants = $this->countryVariants($normalizedCountry);

        $openOrders = Order::query()
            ->whereNotIn('status', ['completed', 'delivered', 'cancelled'])
            ->where(function ($query) use ($countryVariants): void {
                foreach ($countryVariants as $variant) {
                    $query->orWhere('shipping_address->country', $variant)
                        ->orWhere('shipping_address->address->country', $variant)
                        ->orWhere('shipping_address->country_code', $variant)
                        ->orWhere('shipping_address->address->country_code', $variant);
                }
            })
            ->with(['shipments'])
            ->get();

        $updatedCount = 0;
        $notifiedShipments = 0;

        foreach ($openOrders as $order) {
            $wasUpdated = $this->recalculateOrderEta($order, $holidayReason);
            if ($wasUpdated) {
                $updatedCount++;
                $notifiedShipments += max(1, $order->shipments->count());
            }
        }

        return [
            'total_open' => $openOrders->count(),
            'updated' => $updatedCount,
            'notified' => $notifiedShipments,
        ];
    }

    /**
     * Clear cached holiday dates for a country/year, or everything.
     */
    public function clearHolidayCache(?string $country = null, ?int $year = null): void
    {
        if ($country !== null && $year !== null) {
            Cache::forget($this->cacheKey($country, $year));

            return;
        }

        // Clear general cache keys for major countries and recent years
        $countries = ['GB', 'United Kingdom', 'US', 'United States', 'CA', 'Canada', 'AU', 'Australia'];
        $years = [2025, 2026, 2027];

        foreach ($countries as $c) {
            foreach ($years as $y) {
                Cache::forget($this->cacheKey($c, $y));
            }
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
        if ($upper === 'GB' || $upper === 'UK' || strcasecmp($trimmed, 'United Kingdom') === 0) {
            return 'United Kingdom';
        }
        if ($upper === 'US' || $upper === 'USA' || strcasecmp($trimmed, 'United States') === 0) {
            return 'United States';
        }
        if ($upper === 'CA' || strcasecmp($trimmed, 'Canada') === 0) {
            return 'Canada';
        }

        return $trimmed;
    }

    public function countryVariants(string $country): array
    {
        return match ($country) {
            'United Kingdom' => ['United Kingdom', 'GB', 'UK', 'Great Britain'],
            'United States' => ['United States', 'US', 'USA'],
            'Canada' => ['Canada', 'CA'],
            default => [$country],
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
