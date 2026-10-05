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
     * Existing per-product price adjustments and quantity ranges are preserved
     * when a product already has the same method code.
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
        $methodCodes = $methods->pluck('code')->values();

        $updated = DB::transaction(function () use ($productIds, $methods, $methodCodes, $actorId, $now): int {
            $lockedProductIds = Product::query()
                ->whereIn('id', $productIds->all())
                ->lockForUpdate()
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->values();

            if ($lockedProductIds->isEmpty()) {
                return 0;
            }

            // Match the product edit form: the chosen master methods become the
            // exact set for each selected product. Do not leave stale choices active.
            ProductProductionSpeed::query()
                ->whereIn('product_id', $lockedProductIds->all())
                ->whereNotIn('code', $methodCodes->all())
                ->delete();

            $rows = [];
            foreach ($lockedProductIds as $productId) {
                foreach ($methods->values() as $index => $method) {
                    $rows[] = [
                        'product_id' => $productId,
                        'production_method_id' => $method->id,
                        'name' => $method->name,
                        'code' => $method->code,
                        'description' => $method->description,
                        // Used only for inserts. Existing product-specific charges
                        // are deliberately not included in the upsert update list.
                        'price_adjustment' => 0,
                        'minimum_quantity' => 1,
                        'maximum_quantity' => null,
                        'minimum_days' => max(0, (int) $method->minimum_days),
                        'maximum_days' => max(0, (int) $method->maximum_days),
                        'is_active' => true,
                        'sort_order' => $index,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                ProductProductionSpeed::query()->upsert(
                    $chunk,
                    ['product_id', 'code'],
                    [
                        'production_method_id',
                        'name',
                        'description',
                        'minimum_days',
                        'maximum_days',
                        'is_active',
                        'sort_order',
                        'updated_at',
                    ]
                );
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
