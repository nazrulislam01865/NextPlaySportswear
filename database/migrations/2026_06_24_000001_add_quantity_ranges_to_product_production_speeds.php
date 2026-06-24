<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_production_speeds', function (Blueprint $table): void {
            $table->unsignedInteger('minimum_quantity')->default(1)->after('price_adjustment');
            $table->unsignedInteger('maximum_quantity')->nullable()->after('minimum_quantity');
            $table->index(
                ['product_id', 'minimum_quantity', 'maximum_quantity'],
                'production_speed_product_qty_idx'
            );
        });

        // Preserve existing production rows while aligning them to the product's
        // current visible price tiers wherever an equivalent row is available.
        DB::table('product_production_speeds')
            ->select('product_id')
            ->distinct()
            ->orderBy('product_id')
            ->pluck('product_id')
            ->each(function (int $productId): void {
                $ranges = DB::table('product_price_tiers')
                    ->where('product_id', $productId)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get(['minimum_quantity', 'maximum_quantity'])
                    ->values();

                DB::table('product_production_speeds')
                    ->where('product_id', $productId)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get(['id'])
                    ->values()
                    ->each(function (object $speed, int $index) use ($ranges): void {
                        $range = $ranges->get($index) ?? $ranges->first();

                        if ($range) {
                            DB::table('product_production_speeds')
                                ->where('id', $speed->id)
                                ->update([
                                    'minimum_quantity' => (int) $range->minimum_quantity,
                                    'maximum_quantity' => $range->maximum_quantity === null
                                        ? null
                                        : (int) $range->maximum_quantity,
                                ]);
                        }
                    });
            });
    }

    public function down(): void
    {
        Schema::table('product_production_speeds', function (Blueprint $table): void {
            $table->dropIndex('production_speed_product_qty_idx');
            $table->dropColumn(['minimum_quantity', 'maximum_quantity']);
        });
    }
};
