<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // This migration repairs deployments where the fabric pricing feature was
        // partially installed before the tier table was present. It is safe to run
        // after the original fabric pricing migration and safe on fresh installs.
        if (! Schema::hasTable('product_fabric_price_tables')) {
            Schema::create('product_fabric_price_tables', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('jersey_customization_option_id')->nullable()->constrained('jersey_customization_options')->nullOnDelete();
                $table->string('fabric_key', 220);
                $table->string('fabric_code', 180)->nullable();
                $table->string('fabric_label', 180);
                $table->json('price_table_headers')->nullable();
                $table->json('price_table_rows')->nullable();
                $table->json('price_table_ranges')->nullable();
                $table->unsignedTinyInteger('price_table_highlight_column')->default(1);
                $table->text('price_table_note')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['product_id', 'fabric_key'], 'prod_fabric_price_table_unique');
                $table->index(['product_id', 'is_active', 'sort_order'], 'prod_fabric_price_table_lookup');
            });
        }

        if (! Schema::hasTable('product_fabric_price_tiers')) {
            Schema::create('product_fabric_price_tiers', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('product_fabric_price_table_id');
                $table->string('label')->nullable();
                $table->unsignedInteger('minimum_quantity')->default(1);
                $table->unsignedInteger('maximum_quantity')->nullable();
                $table->decimal('unit_price', 12, 2)->default(0);
                $table->decimal('compare_at_price', 12, 2)->nullable();
                $table->string('savings_label')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->foreign('product_fabric_price_table_id', 'fabric_price_tiers_table_fk')
                    ->references('id')
                    ->on('product_fabric_price_tables')
                    ->cascadeOnDelete();
                $table->index(['product_fabric_price_table_id', 'minimum_quantity'], 'fabric_price_tier_lookup');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_fabric_price_tiers');
    }
};
