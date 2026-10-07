<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductProductionSpeed;
use App\Models\ProductionMethod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductProductionMethodBulkService
{
    /**
     * Enable production methods for the selected products and make the selected
     * master-data methods the exact production-method set for those products.
     *
     * Existing per-product quantity ranges, working days, and charges are preserved
     * when a product already has the same master method assigned.
     *
     * @param  Collection<int, int>|array<int, int>  $productIds
     * @param  Collection<int, int>|array<int, int>  $productionMethodIds
     * @return array{updated:int, methods:Collection<int, ProductionMethod>}
     */
    public function enableAndSet(Collection|array $productIds, Collection|array $productionMethodIds, ?int $actorId): array
    {
        $productIds = collect($productIds)
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $methodIds = collect($productionMethodIds)
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $methods = ProductionMethod::query()
            ->whereIn('id', $methodIds->all())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        if ($productIds->isEmpty() || $methods->isEmpty()) {
            return ['updated' => 0, 'methods' => $methods];
        }

        $now = now();
        $methodIds = $methods->pluck('id')->map(fn ($id): int => (int) $id)->values();
        $methodCodes = $methods->pluck('code')->values();

        $updated = DB::transaction(function () use ($productIds, $methods, $methodIds, $methodCodes, $actorId, $now): int {
            $lockedProductIds = Product::query()
                ->whereIn('id', $productIds->all())
                ->lockForUpdate()
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->values();

            if ($lockedProductIds->isEmpty()) {
                return 0;
            }

            // Link rows created by the previous master-data implementation before
            // quantity bands used production_method_id consistently.
            foreach ($methods as $method) {
                ProductProductionSpeed::query()
                    ->whereIn('product_id', $lockedProductIds->all())
                    ->whereNull('production_method_id')
                    ->where(function ($legacy) use ($method): void {
                        $legacy->where('code', $method->code)
                            ->orWhere('code', 'like', $method->code.'-%')
                            ->orWhere('name', $method->name);
                    })
                    ->update([
                        'production_method_id' => $method->id,
                        'name' => $method->name,
                        'description' => $method->description,
                        'is_active' => true,
                        'updated_at' => $now,
                    ]);
            }

            // Selected master methods remain the exact set, but every existing
            // product-specific quantity/day/charge row for those methods is kept.
            ProductProductionSpeed::query()
                ->whereIn('product_id', $lockedProductIds->all())
                ->where(function ($query) use ($methodIds, $methodCodes): void {
                    $query->where(function ($linked) use ($methodIds): void {
                        $linked->whereNotNull('production_method_id')
                            ->whereNotIn('production_method_id', $methodIds->all());
                    })->orWhere(function ($legacy) use ($methodCodes): void {
                        $legacy->whereNull('production_method_id')
                            ->whereNotIn('code', $methodCodes->all());
                    });
                })
                ->delete();

            foreach ($methods as $method) {
                ProductProductionSpeed::query()
                    ->whereIn('product_id', $lockedProductIds->all())
                    ->where('production_method_id', $method->id)
                    ->update([
                        'name' => $method->name,
                        'description' => $method->description,
                        'is_active' => true,
                        'updated_at' => $now,
                    ]);
            }

            $existingPairs = ProductProductionSpeed::query()
                ->whereIn('product_id', $lockedProductIds->all())
                ->whereIn('production_method_id', $methodIds->all())
                ->get(['product_id', 'production_method_id'])
                ->map(fn (ProductProductionSpeed $speed): string => $speed->product_id.':'.$speed->production_method_id)
                ->flip();

            $rows = [];
            foreach ($lockedProductIds as $productId) {
                foreach ($methods->values() as $index => $method) {
                    if ($existingPairs->has($productId.':'.$method->id)) {
                        continue;
                    }

                    // Bulk assignment cannot decide a product's quantity timeline.
                    // Create a safe "to be confirmed" placeholder; the admin sets
                    // the real quantity/day/charge rules on the product add/edit page.
                    $rows[] = [
                        'product_id' => $productId,
                        'production_method_id' => $method->id,
                        'name' => $method->name,
                        'code' => $method->code,
                        'description' => $method->description,
                        'price_adjustment' => 0,
                        'minimum_quantity' => 1,
                        'maximum_quantity' => null,
                        'minimum_days' => 0,
                        'maximum_days' => 0,
                        'is_active' => true,
                        'sort_order' => $index,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                ProductProductionSpeed::query()->insert($chunk);
            }

            return Product::query()
                ->whereIn('id', $lockedProductIds->all())
                ->update([
                    'production_methods_enabled' => true,
                    'last_update_summary' => 'Production methods enabled: '.$methods->pluck('name')->join(', '),
                    'updated_by' => $actorId,
                    'updated_at' => $now,
                ]);
        });

        return ['updated' => $updated, 'methods' => $methods];
    }

    /**
     * Disable production methods without deleting their assignments, so a bulk
     * disable is reversible and never destroys per-product charges.
     *
     * @param  Collection<int, int>|array<int, int>  $productIds
     */
    public function disable(Collection|array $productIds, ?int $actorId): int
    {
        $productIds = collect($productIds)
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return 0;
        }

        return DB::transaction(function () use ($productIds, $actorId): int {
            $lockedProductIds = Product::query()
                ->whereIn('id', $productIds->all())
                ->lockForUpdate()
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->values();

            if ($lockedProductIds->isEmpty()) {
                return 0;
            }

            return Product::query()
                ->whereIn('id', $lockedProductIds->all())
                ->update([
                    'production_methods_enabled' => false,
                    'last_update_summary' => 'Production methods disabled',
                    'updated_by' => $actorId,
                    'updated_at' => now(),
                ]);
        });
    }
}
