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
            return;
        }

        Schema::table('shipping_methods', function (Blueprint $table): void {
            if (! Schema::hasColumn('shipping_methods', 'charge_amount')) {
                $table->decimal('charge_amount', 12, 2)->default(0)->after('description');
            }

            if (! Schema::hasColumn('shipping_methods', 'charge_application')) {
                $table->string('charge_application', 40)->default('per_order')->after('charge_amount');
            }
        });

        if (Schema::hasTable('product_shipping_methods') && ! Schema::hasColumn('product_shipping_methods', 'charge_application')) {
            Schema::table('product_shipping_methods', function (Blueprint $table): void {
                $table->string('charge_application', 40)->nullable()->after('charge_type');
            });
        }

        DB::table('shipping_methods')
            ->where(function ($query): void {
                $query->whereNull('charge_amount')->orWhere('charge_amount', 0);
            })
            ->orderBy('id')
            ->chunkById(100, function ($methods): void {
                foreach ($methods as $method) {
                    $base = max(0, (float) ($method->base_price ?? 0));
                    $perItem = max(0, (float) ($method->per_item_price ?? 0));
                    $application = $base > 0 ? 'per_order' : ($perItem > 0 ? 'per_item' : 'included');
                    $amount = $base > 0 ? $base : $perItem;

                    DB::table('shipping_methods')->where('id', $method->id)->update([
                        'charge_amount' => $application === 'included' ? 0 : $amount,
                        'charge_application' => $application,
                        'free_shipping_minimum' => null,
                        'minimum_quantity' => null,
                        'maximum_quantity' => null,
                        'minimum_subtotal' => null,
                        'maximum_subtotal' => null,
                        'country' => null,
                        'state' => null,
                        'is_quote_based' => false,
                    ]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('shipping_methods')) {
            return;
        }

        if (Schema::hasTable('product_shipping_methods') && Schema::hasColumn('product_shipping_methods', 'charge_application')) {
            Schema::table('product_shipping_methods', function (Blueprint $table): void {
                $table->dropColumn('charge_application');
            });
        }

        Schema::table('shipping_methods', function (Blueprint $table): void {
            foreach (['charge_application', 'charge_amount'] as $column) {
                if (Schema::hasColumn('shipping_methods', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
