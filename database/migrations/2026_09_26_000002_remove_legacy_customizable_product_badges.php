<?php

use App\Services\Storefront\ProductCatalogCacheService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'badge_label')) {
            return;
        }

        $query = DB::table('products')
            ->whereIn(DB::raw('LOWER(TRIM(badge_label))'), [
                'customizable',
                'customisable',
                'c',
            ]);

        if (Schema::hasColumn('products', 'is_customizable')) {
            $query->where('is_customizable', true);
        }

        $updates = ['badge_label' => null];

        if (Schema::hasColumn('products', 'updated_at')) {
            $updates['updated_at'] = now();
        }

        $query->update($updates);

        // Product-list/homepage payloads are versioned in cache. Invalidate them
        // as part of the data cleanup so a removed legacy badge cannot survive
        // in a warm storefront cache after deployment.
        app(ProductCatalogCacheService::class)->flush();
    }

    public function down(): void
    {
        // Intentionally irreversible: legacy default badge values cannot be
        // distinguished from intentionally entered values after cleanup.
    }
};
