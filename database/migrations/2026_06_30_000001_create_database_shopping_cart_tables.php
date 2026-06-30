<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopping_carts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 120)->nullable();
            $table->string('status', 30)->default('active');
            $table->char('currency', 3)->default('USD');
            $table->string('coupon_code', 80)->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status', 'updated_at']);
            $table->index(['session_id', 'status', 'updated_at']);
        });

        Schema::create('shopping_cart_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shopping_cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_slug', 240);
            $table->string('item_key', 64);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('customization_unit_price', 12, 2)->default(0);
            $table->decimal('line_subtotal', 12, 2)->default(0);
            $table->decimal('customization_total', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->json('customization')->nullable();
            $table->json('product_snapshot')->nullable();
            $table->timestamps();

            $table->unique(['shopping_cart_id', 'item_key'], 'shopping_cart_item_unique_key');
            $table->index(['product_id', 'product_slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopping_cart_items');
        Schema::dropIfExists('shopping_carts');
    }
};
