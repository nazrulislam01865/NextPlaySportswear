<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_shipping_methods')) {
            return;
        }

        Schema::table('product_shipping_methods', function (Blueprint $table): void {
            if (! Schema::hasColumn('product_shipping_methods', 'shipping_method_id')) {
                $table->foreignId('shipping_method_id')
                    ->nullable()
                    ->after('product_id')
                    ->constrained('shipping_methods')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('product_shipping_methods', 'charge_application')) {
                $table->string('charge_application', 40)->nullable()->after('charge_type');
            }

            if (! Schema::hasColumn('product_shipping_methods', 'base_price')) {
                $table->decimal('base_price', 12, 2)->default(0)->after('charge_type');
            }

            if (! Schema::hasColumn('product_shipping_methods', 'per_item_price')) {
                $table->decimal('per_item_price', 12, 2)->default(0)->after('base_price');
            }

            if (! Schema::hasColumn('product_shipping_methods', 'free_shipping_minimum')) {
                $table->decimal('free_shipping_minimum', 12, 2)->nullable()->after('per_item_price');
            }

            if (! Schema::hasColumn('product_shipping_methods', 'starts_after_artwork_approval')) {
                $table->boolean('starts_after_artwork_approval')->default(true)->after('maximum_days');
            }

            if (! Schema::hasColumn('product_shipping_methods', 'is_quote_based')) {
                $table->boolean('is_quote_based')->default(false)->after('starts_after_artwork_approval');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_shipping_methods')) {
            return;
        }

        Schema::table('product_shipping_methods', function (Blueprint $table): void {
            if (Schema::hasColumn('product_shipping_methods', 'shipping_method_id')) {
                $table->dropConstrainedForeignId('shipping_method_id');
            }

            foreach ([
                'charge_application',
                'base_price',
                'per_item_price',
                'free_shipping_minimum',
                'starts_after_artwork_approval',
                'is_quote_based',
            ] as $column) {
                if (Schema::hasColumn('product_shipping_methods', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
