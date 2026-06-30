<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rural_area_surcharges')) {
            Schema::create('rural_area_surcharges', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 150);
                $table->string('country', 120)->default('United States');
                $table->string('state', 120)->nullable();
                $table->text('postal_code_patterns');
                $table->decimal('amount', 12, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['country', 'state']);
                $table->index('is_active');
            });
        }

        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'rural_surcharge_total')) {
                $table->decimal('rural_surcharge_total', 12, 2)->default(0)->after('shipping_total');
            }
        });

        Schema::table('customer_addresses', function (Blueprint $table): void {
            if (! Schema::hasColumn('customer_addresses', 'delivery_instruction')) {
                $table->text('delivery_instruction')->nullable()->after('email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table): void {
            if (Schema::hasColumn('customer_addresses', 'delivery_instruction')) {
                $table->dropColumn('delivery_instruction');
            }
        });

        Schema::table('orders', function (Blueprint $table): void {
            if (Schema::hasColumn('orders', 'rural_surcharge_total')) {
                $table->dropColumn('rural_surcharge_total');
            }
        });

        Schema::dropIfExists('rural_area_surcharges');
    }
};
