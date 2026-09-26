<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_methods')) {
            DB::table('payment_methods')->where('provider', 'stripe')->update([
                'is_online' => true,
                'requires_provider_redirect' => true,
                'requires_manual_review' => false,
                'allows_saved_methods' => false,
            ]);

            DB::table('payment_methods')->where('provider', 'manual')->update([
                'is_online' => false,
                'requires_provider_redirect' => false,
                'requires_manual_review' => true,
                'allows_saved_methods' => false,
            ]);
        }

        if (Schema::hasTable('order_refunds') && Schema::hasColumn('order_refunds', 'provider_reference')) {
            Schema::table('order_refunds', function (Blueprint $table): void {
                $table->index('provider_reference', 'order_refunds_provider_reference_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('order_refunds') && Schema::hasColumn('order_refunds', 'provider_reference')) {
            Schema::table('order_refunds', function (Blueprint $table): void {
                $table->dropIndex('order_refunds_provider_reference_idx');
            });
        }
    }
};
