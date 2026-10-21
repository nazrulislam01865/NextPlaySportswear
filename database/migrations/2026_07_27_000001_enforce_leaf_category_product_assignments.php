<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('categories')
            || ! Schema::hasTable('products')
            || ! Schema::hasTable('category_product')) {
            return;
        }

        $leafIds = DB::table('categories as category')
            ->whereNull('category.deleted_at')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('categories as child')
                    ->whereColumn('child.parent_id', 'category.id')
                    ->whereNull('child.deleted_at');
            })
            ->pluck('category.id')
            ->map(fn ($id): int => (int) $id)
            ->values();

        // This historical migration may still be pending on an installation.
        // A category becoming a parent is not permission to remove its existing
        // product placements or clear legacy references.
        if ($leafIds->isEmpty()) {
            return;
        }

        $leafSet = array_fill_keys($leafIds->all(), true);

        DB::table('products')
            ->select(['id', 'category_id', 'subcategory_id', 'sort_order'])
            ->orderBy('id')
            ->chunkById(250, function ($products) use ($leafSet): void {
                foreach ($products as $product) {
                    $legacyLeafId = null;
                    $legacySubcategoryId = (int) ($product->subcategory_id ?? 0);
                    $legacyCategoryId = (int) ($product->category_id ?? 0);

                    if ($legacySubcategoryId > 0 && isset($leafSet[$legacySubcategoryId])) {
                        $legacyLeafId = $legacySubcategoryId;
                    } elseif ($legacyCategoryId > 0 && isset($leafSet[$legacyCategoryId])) {
                        $legacyLeafId = $legacyCategoryId;
                    }

                    if ($legacyLeafId) {
                        // Insert only if absent; never overwrite a category's
                        // existing featured flag, sorting or primary assignment.
                        DB::table('category_product')->insertOrIgnore([
                            'category_id' => $legacyLeafId,
                            'product_id' => $product->id,
                            'is_primary' => false,
                            'is_featured' => false,
                            'sort_order' => (int) ($product->sort_order ?? 0),
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]);
                    }

                    $assignments = DB::table('category_product as cp')
                        ->join('categories as category', 'category.id', '=', 'cp.category_id')
                        ->where('cp.product_id', $product->id)
                        ->whereNull('category.deleted_at')
                        ->select([
                            'cp.category_id',
                            'cp.is_primary',
                            'cp.sort_order',
                            'category.depth',
                        ])
                        ->orderByDesc('cp.is_primary')
                        ->orderByDesc('category.depth')
                        ->orderBy('cp.sort_order')
                        ->orderBy('cp.category_id')
                        ->get();

                    if ($assignments->isEmpty()) {
                        // The legacy fields may be the only remaining link.
                        continue;
                    }

                    $assignmentIds = $assignments
                        ->pluck('category_id')
                        ->map(fn ($id): int => (int) $id);
                    $existingPrimary = $assignments->firstWhere('is_primary', true);
                    $primaryLeafId = $existingPrimary
                        ? (int) $existingPrimary->category_id
                        : ($legacyLeafId && $assignmentIds->contains($legacyLeafId)
                            ? $legacyLeafId
                            : (int) $assignments->first()->category_id);

                    DB::table('category_product')
                        ->where('product_id', $product->id)
                        ->update(['is_primary' => false, 'updated_at' => now()]);
                    DB::table('category_product')
                        ->where('product_id', $product->id)
                        ->where('category_id', $primaryLeafId)
                        ->update(['is_primary' => true, 'updated_at' => now()]);

                    $rootCategoryId = $primaryLeafId;
                    if (Schema::hasTable('category_closure')) {
                        $rootCategoryId = (int) (DB::table('category_closure as cc')
                            ->join('categories as ancestor', 'ancestor.id', '=', 'cc.ancestor_id')
                            ->where('cc.descendant_id', $primaryLeafId)
                            ->whereNull('ancestor.deleted_at')
                            ->orderByDesc('cc.depth')
                            ->value('cc.ancestor_id') ?: $primaryLeafId);
                    }

                    DB::table('products')->where('id', $product->id)->update([
                        'category_id' => $rootCategoryId,
                        'subcategory_id' => $rootCategoryId === $primaryLeafId ? null : $primaryLeafId,
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Parent assignments cannot be reconstructed reliably. Leaf assignments
        // remain valid if this migration is rolled back.
    }
};
