<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artwork_preferences', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 20); // pantone or style
            $table->string('name', 160);
            $table->string('code', 80);
            $table->string('hex_color', 7)->nullable(); // Pantone screen preview, not a color conversion
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['type', 'code']);
            $table->index(['type', 'is_active', 'sort_order']);
        });

        Schema::create('artwork_preference_product', function (Blueprint $table): void {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('artwork_preference_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_id', 'artwork_preference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artwork_preference_product');
        Schema::dropIfExists('artwork_preferences');
    }
};
