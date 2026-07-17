<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('production_methods')) {
            Schema::create('production_methods', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 160);
                $table->string('code', 160)->unique();
                $table->text('description')->nullable();
                $table->unsignedInteger('minimum_days')->default(1);
                $table->unsignedInteger('maximum_days')->default(1);
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['is_active', 'sort_order']);
            });
        }

        if (Schema::hasTable('production_methods') && DB::table('production_methods')->count() === 0) {
            $now = now();
            DB::table('production_methods')->insert([
                [
                    'name' => 'Standard Production',
                    'code' => 'standard-production',
                    'description' => 'Regular production timeline for standard custom orders.',
                    'minimum_days' => 7,
                    'maximum_days' => 10,
                    'is_default' => true,
                    'is_active' => true,
                    'sort_order' => 10,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'Rush Production Review',
                    'code' => 'rush-production-review',
                    'description' => 'Production team reviews feasibility for tight event deadlines before order confirmation.',
                    'minimum_days' => 3,
                    'maximum_days' => 5,
                    'is_default' => false,
                    'is_active' => true,
                    'sort_order' => 20,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }

        if (Schema::hasTable('product_production_speeds') && ! Schema::hasColumn('product_production_speeds', 'production_method_id')) {
            Schema::table('product_production_speeds', function (Blueprint $table): void {
                $table->foreignId('production_method_id')
                    ->nullable()
                    ->after('product_id')
                    ->constrained('production_methods')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_production_speeds') && Schema::hasColumn('product_production_speeds', 'production_method_id')) {
            Schema::table('product_production_speeds', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('production_method_id');
            });
        }

        Schema::dropIfExists('production_methods');
    }
};
