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
                $service->openOrdersQuery($normalizedCountry)->chunkById(150, function ($orders) use (
                    $service, $normalizedCountry, &$totalOpen, &$totalUpdated, &$totalNotified, &$previewRows
                ): void {
                    foreach ($orders as $order) {
                        $totalOpen++;
                        $anchor = $service->resolveEtaAnchor($order);
                        if ($anchor === null) continue; // Awaiting artwork approval.
                        [$minDays, $maxDays] = $service->resolveOrderDays($order);
                        $window = $service->calculateDeliveryWindow($anchor, $minDays, $maxDays, $normalizedCountry);
                        $currentStart = $order->estimated_delivery_start_at?->format('Y-m-d');
                        $currentEnd = $order->estimated_delivery_end_at?->format('Y-m-d');
                        if ($currentStart === $window['start_date']->format('Y-m-d')
                            && $currentEnd === $window['end_date']->format('Y-m-d')) continue;
                        $totalUpdated++;
                        $totalNotified += max(1, $order->shipments()->count());
                        if (count($previewRows) < 100) {
                            $previewRows[] = [
                                $order->order_number, $normalizedCountry,
                                $order->formattedEstimatedDelivery() ?: 'None',
                                $window['formatted_range'], $window['adjustment_reason'] ?: 'None',
                            ];
                        }
                    }
                });
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
            if ($totalUpdated > count($previewRows)) {
                $this->line('Showing first 100 changed orders only; all matching orders were counted.');
            }
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
