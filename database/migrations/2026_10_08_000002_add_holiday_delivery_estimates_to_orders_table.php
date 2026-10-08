<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->date('estimated_delivery_start_at')->nullable()->after('delivered_at');
            $table->date('estimated_delivery_end_at')->nullable()->after('estimated_delivery_start_at');
            $table->boolean('holiday_adjustment_applied')->default(false)->after('estimated_delivery_end_at');
            $table->string('holiday_adjustment_reason', 255)->nullable()->after('holiday_adjustment_applied');
            $table->json('holiday_adjustment_meta')->nullable()->after('holiday_adjustment_reason');
        });

        Schema::table('order_shipments', function (Blueprint $table) {
            $table->timestamp('old_estimated_delivery_at')->nullable()->after('estimated_delivery_at');
            $table->string('holiday_reason', 255)->nullable()->after('old_estimated_delivery_at');
        });
    }

    public function down(): void
    {
        Schema::table('order_shipments', function (Blueprint $table) {
            $table->dropColumn(['old_estimated_delivery_at', 'holiday_reason']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'estimated_delivery_start_at',
                'estimated_delivery_end_at',
                'holiday_adjustment_applied',
                'holiday_adjustment_reason',
                'holiday_adjustment_meta',
            ]);
        });
    }
};
