<?php

namespace App\Console\Commands;

use App\Models\HolidayCalendar;
use App\Models\Order;
use App\Services\Shipping\HolidayCalendarService;
use Illuminate\Console\Command;

final class RecalculateHolidayOrders extends Command
{
    protected $signature = 'holidays:recalculate-orders
                            {--country= : Specific country code or country name (e.g. GB, UK, USA)}
                            {--dry-run : Preview recalculation without saving changes or notifying customers}';

    protected $description = 'Recalculate delivery estimates for open orders based on active holiday calendars';

    public function handle(HolidayCalendarService $service): int
    {
        $countryInput = trim((string) $this->option('country'));
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun ? '🔍 Running delivery estimate recalculation in DRY RUN mode...' : '🚀 Recalculating delivery estimates for open orders...');

        $countries = [];
        if ($countryInput !== '') {
            $countries[] = $countryInput;
        } else {
            $countries = HolidayCalendar::query()
                ->active()
                ->select('country_name')
                ->distinct()
                ->pluck('country_name')
                ->all();

            if (empty($countries)) {
                $this->warn('No active holiday calendars found. Nothing to recalculate.');

                return self::SUCCESS;
            }
        }

        $totalOpen = 0;
        $totalUpdated = 0;
        $totalNotified = 0;
        $previewRows = [];

        foreach ($countries as $country) {
            $normalizedCountry = $service->normalizeCountry($country);
            $this->line("Evaluating open orders for <comment>{$normalizedCountry}</comment>...");

            if ($dryRun) {
                // Dry run inspection
                $countryVariants = $service->countryVariants($normalizedCountry);
                $orders = Order::query()
                    ->whereNotIn('status', ['completed', 'delivered', 'cancelled'])
                    ->where(function ($query) use ($countryVariants): void {
                        foreach ($countryVariants as $variant) {
                            $query->orWhere('shipping_address->country', $variant)
                                ->orWhere('shipping_address->address->country', $variant)
                                ->orWhere('shipping_address->country_code', $variant)
                                ->orWhere('shipping_address->address->country_code', $variant);
                        }
                    })
                    ->get();

                $totalOpen += $orders->count();

                foreach ($orders as $order) {
                    [$minDays, $maxDays] = $service->resolveOrderDays($order);
                    $placedDate = $order->placed_at ?? $order->created_at ?? now();
                    $window = $service->calculateDeliveryWindow($placedDate, $minDays, $maxDays, $normalizedCountry);

                    $currentStart = $order->estimated_delivery_start_at?->format('Y-m-d');
                    $currentEnd = $order->estimated_delivery_end_at?->format('Y-m-d');
                    $newStart = $window['start_date']->format('Y-m-d');
                    $newEnd = $window['end_date']->format('Y-m-d');

                    $isChanged = ($currentStart !== $newStart || $currentEnd !== $newEnd);

                    if ($isChanged) {
                        $totalUpdated++;
                        $totalNotified += max(1, $order->shipments()->count());
                        $previewRows[] = [
                            $order->order_number,
                            $normalizedCountry,
                            $order->formattedEstimatedDelivery() ?: 'None',
                            $window['formatted_range'],
                            $window['adjustment_reason'] ?: 'None',
                        ];
                    }
                }
            } else {
                $stats = $service->recalculateOpenOrdersForCountry($normalizedCountry);
                $totalOpen += $stats['total_open'];
                $totalUpdated += $stats['updated'];
                $totalNotified += $stats['notified'];
            }
        }

        if ($dryRun && ! empty($previewRows)) {
            $this->table(
                ['Order #', 'Country', 'Current Estimate', 'New Estimate', 'Holiday Adjustment'],
                $previewRows
            );
        }

        $this->newLine();
        $this->info("Done! Processed {$totalOpen} open order(s).");
        if ($dryRun) {
            $this->info("Preview: {$totalUpdated} order(s) would be updated ({$totalNotified} shipment notification(s)).");
        } else {
            $this->info("Successfully updated {$totalUpdated} order(s) and queued {$totalNotified} customer notification(s).");
        }

        return self::SUCCESS;
    }
}
