<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('genders', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('slug', 120)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        $now = now();
        DB::table('genders')->insert([
            ['name' => 'Men', 'slug' => 'men', 'is_active' => true, 'sort_order' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Women', 'slug' => 'women', 'is_active' => true, 'sort_order' => 20, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kids', 'slug' => 'kids', 'is_active' => true, 'sort_order' => 30, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Unisex', 'slug' => 'unisex', 'is_active' => true, 'sort_order' => 40, 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::table('products', function (Blueprint $table): void {
            $table->foreignId('gender_id')
                ->nullable()
                ->after('product_type')
                ->constrained('genders')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('gender_id');
        });

        Schema::dropIfExists('genders');
    }
};
