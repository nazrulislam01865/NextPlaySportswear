<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_wishlists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('product_id');
            $table->timestamps();

            $table->unique(['user_id', 'product_id'], 'pw_user_product_unique');
            $table->index(['product_id', 'created_at'], 'pw_product_created_idx');

            $table->foreign('user_id', 'pw_user_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('product_id', 'pw_product_fk')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_wishlists');
    }
};
