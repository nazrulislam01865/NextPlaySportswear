<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shipping_methods')) {
            Schema::create('shipping_methods', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 160);
                $table->string('code', 160)->unique();
                $table->text('description')->nullable();
                $table->decimal('base_price', 12, 2)->default(0);
                $table->decimal('per_item_price', 12, 2)->default(0);
                $table->decimal('free_shipping_minimum', 12, 2)->nullable();
                $table->unsignedInteger('minimum_quantity')->nullable();
                $table->unsignedInteger('maximum_quantity')->nullable();
                $table->decimal('minimum_subtotal', 12, 2)->nullable();
                $table->decimal('maximum_subtotal', 12, 2)->nullable();
                $table->string('country', 120)->nullable();
                $table->string('state', 120)->nullable();
                $table->unsignedInteger('minimum_days')->default(1);
                $table->unsignedInteger('maximum_days')->default(7);
                $table->boolean('starts_after_artwork_approval')->default(true);
                $table->boolean('is_quote_based')->default(false);
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['is_active', 'sort_order']);
                $table->index(['country', 'state']);
            });
        }

        if (Schema::hasTable('shipping_methods') && DB::table('shipping_methods')->count() === 0) {
            $now = now();

            DB::table('shipping_methods')->insert([
                [
                    'name' => 'Standard Shipping',
                    'code' => 'standard',
                    'description' => 'Best for regular orders without event deadline pressure.',
                    'base_price' => 12.00,
                    'per_item_price' => 2.25,
                    'free_shipping_minimum' => 450.00,
                    'minimum_quantity' => null,
                    'maximum_quantity' => null,
                    'minimum_subtotal' => null,
                    'maximum_subtotal' => null,
                    'country' => null,
                    'state' => null,
                    'minimum_days' => 5,
                    'maximum_days' => 7,
                    'starts_after_artwork_approval' => true,
                    'is_quote_based' => false,
                    'is_default' => true,
                    'is_active' => true,
                    'sort_order' => 10,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'Expedited Shipping',
                    'code' => 'expedited',
                    'description' => 'Faster delivery after production is complete.',
                    'base_price' => 24.00,
                    'per_item_price' => 3.50,
                    'free_shipping_minimum' => null,
                    'minimum_quantity' => null,
                    'maximum_quantity' => null,
                    'minimum_subtotal' => null,
                    'maximum_subtotal' => null,
                    'country' => null,
                    'state' => null,
                    'minimum_days' => 2,
                    'maximum_days' => 4,
                    'starts_after_artwork_approval' => true,
                    'is_quote_based' => false,
                    'is_default' => false,
                    'is_active' => true,
                    'sort_order' => 20,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'Rush Event Delivery Review',
                    'code' => 'rush-review',
                    'description' => 'For event or tournament deadlines. Support confirms production and delivery feasibility before payment capture.',
                    'base_price' => 0.00,
                    'per_item_price' => 0.00,
                    'free_shipping_minimum' => null,
                    'minimum_quantity' => null,
                    'maximum_quantity' => null,
                    'minimum_subtotal' => null,
                    'maximum_subtotal' => null,
                    'country' => null,
                    'state' => null,
                    'minimum_days' => 1,
                    'maximum_days' => 3,
                    'starts_after_artwork_approval' => true,
                    'is_quote_based' => true,
                    'is_default' => false,
                    'is_active' => true,
                    'sort_order' => 30,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'Bulk Freight / Team Shipment',
                    'code' => 'bulk-freight',
                    'description' => 'Recommended for 50+ pieces, schools, leagues, and carton/freight shipments.',
                    'base_price' => 0.00,
                    'per_item_price' => 0.00,
                    'free_shipping_minimum' => null,
                    'minimum_quantity' => 50,
                    'maximum_quantity' => null,
                    'minimum_subtotal' => null,
                    'maximum_subtotal' => null,
                    'country' => null,
                    'state' => null,
                    'minimum_days' => 5,
                    'maximum_days' => 10,
                    'starts_after_artwork_approval' => true,
                    'is_quote_based' => true,
                    'is_default' => false,
                    'is_active' => true,
                    'sort_order' => 40,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }

        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'product_shipping_total')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->decimal('product_shipping_total', 12, 2)->default(0)->after('rural_surcharge_total');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'product_shipping_total')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropColumn('product_shipping_total');
            });
        }

        Schema::dropIfExists('shipping_methods');
    }
};
