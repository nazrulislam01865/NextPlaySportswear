<?php

namespace App\Console\Commands;

use App\Models\BulkQuoteRequest;
use App\Models\Order;
use App\Services\Shipping\RuralAreaSurchargeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** One-time classification of older records; the admin list never performs lookups. */
final class BackfillRemoteAreaFlags extends Command
{
    protected $signature = 'nextplay:backfill-remote-area-flags';

    protected $description = 'Classify existing orders and bulk quotes using configured remote-area postal rules';

    public function handle(RuralAreaSurchargeService $remoteAreas): int
    {
        $counts = ['orders' => 0, 'bulk_quote_requests' => 0, 'missing_postal' => 0];

        Order::query()->whereNull('is_remote_area')
            ->select(['id', 'shipping_address', 'rural_surcharge_total'])
            ->chunkById(200, function ($orders) use ($remoteAreas, &$counts): void {
                $remoteIds = [];
                $nonRemoteIds = [];
                $cache = [];
                foreach ($orders as $order) {
                    $address = (array) data_get($order->shipping_address, 'address', $order->shipping_address ?? []);
                    $postal = trim((string) ($address['postal_code'] ?? ''));
                    $city = trim((string) ($address['city'] ?? ''));
                    if ($postal === '' && $city === '') {
                        // Positive legacy surcharge is reliable evidence even if the address was removed.
                        if ((float) $order->rural_surcharge_total <= 0) {
                            $counts['missing_postal']++;
                            continue;
                        }
                    }
                    $key = implode('|', [$postal, $address['country'] ?? '', $address['state'] ?? '', $city]);
                    if ((float) $order->rural_surcharge_total > 0) {
                        $matched = true; // Use the already-recorded surcharge; no lookup needed.
                    } else {
                        if (! array_key_exists($key, $cache)) {
                            $cache[$key] = $remoteAreas->resolve($postal, $address['country'] ?? 'United States', $address['state'] ?? null, $city ?: null) !== null;
                        }
                        $matched = $cache[$key];
                    }
                    if ($matched) {
                        $remoteIds[] = $order->id;
                    } else {
                        $nonRemoteIds[] = $order->id;
                    }
                }
                $counts['orders'] += $this->updateExistingFlags('orders', $remoteIds, $nonRemoteIds);
            });

        BulkQuoteRequest::query()->whereNull('is_remote_area')
            ->select(['id', 'postal_code', 'country', 'state_province'])
            ->chunkById(200, function ($quotes) use ($remoteAreas, &$counts): void {
                $remoteIds = [];
                $nonRemoteIds = [];
                $cache = [];
                foreach ($quotes as $quote) {
                    $postal = trim((string) $quote->postal_code);
                    if ($postal === '') {
                        $counts['missing_postal']++;
                        continue;
                    }
                    $key = implode('|', [$postal, $quote->country ?? '', $quote->state_province ?? '']);
                    if (! array_key_exists($key, $cache)) {
                        $cache[$key] = $remoteAreas->resolve($postal, $quote->country, $quote->state_province) !== null;
                    }
                    if ($cache[$key]) {
                        $remoteIds[] = $quote->id;
                    } else {
                        $nonRemoteIds[] = $quote->id;
                    }
                }
                $counts['bulk_quote_requests'] += $this->updateExistingFlags('bulk_quote_requests', $remoteIds, $nonRemoteIds);
            });

        $this->info("Classified {$counts['orders']} orders and {$counts['bulk_quote_requests']} bulk quotes. {$counts['missing_postal']} records without sufficient destination information were left unclassified.");

        return self::SUCCESS;
    }

    /** Update only existing records, in at most two queries per chunk. */
    private function updateExistingFlags(string $table, array $remoteIds, array $nonRemoteIds): int
    {
        $updated = 0;

        if ($remoteIds !== []) {
            $updated += DB::table($table)->whereIn('id', $remoteIds)
                ->whereNull('is_remote_area')->update(['is_remote_area' => true]);
        }

        if ($nonRemoteIds !== []) {
            $updated += DB::table($table)->whereIn('id', $nonRemoteIds)
                ->whereNull('is_remote_area')->update(['is_remote_area' => false]);
        }

        return $updated;
    }
}

