<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('coupons')) {
            Schema::create('coupons', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 160);
                $table->string('code', 60)->unique();
                $table->text('description')->nullable();
                $table->string('discount_type', 20)->default('percentage');
                $table->decimal('discount_value', 12, 2)->default(0);
                $table->decimal('minimum_subtotal', 12, 2)->default(0);
                $table->decimal('maximum_discount', 12, 2)->nullable();
                $table->unsignedInteger('usage_limit')->nullable();
                $table->unsignedInteger('usage_limit_per_customer')->nullable();
                $table->unsignedInteger('used_count')->default(0);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['code', 'is_active']);
                $table->index(['starts_at', 'expires_at']);
            });
        }

        if (! Schema::hasTable('coupon_redemptions')) {
            Schema::create('coupon_redemptions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('shopping_cart_id')->nullable()->constrained()->nullOnDelete();
                $table->string('customer_email', 255)->nullable();
                $table->string('coupon_code', 60);
                $table->decimal('cart_subtotal', 12, 2)->default(0);
                $table->decimal('discount_amount', 12, 2)->default(0);
                $table->timestamp('redeemed_at')->nullable();
                $table->timestamps();

                $table->unique(['coupon_id', 'order_id'], 'coupon_order_unique');
                $table->index(['coupon_id', 'user_id']);
                $table->index(['coupon_id', 'customer_email']);
            });
        }

        if (Schema::hasTable('shopping_carts') && ! Schema::hasColumn('shopping_carts', 'coupon_id')) {
            Schema::table('shopping_carts', function (Blueprint $table): void {
                $table->foreignId('coupon_id')->nullable()->after('coupon_code')->constrained('coupons')->nullOnDelete();
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table): void {
                if (! Schema::hasColumn('orders', 'coupon_id')) {
                    $table->foreignId('coupon_id')->nullable()->after('currency')->constrained('coupons')->nullOnDelete();
                }
                if (! Schema::hasColumn('orders', 'coupon_code')) {
                    $table->string('coupon_code', 60)->nullable()->after('coupon_id');
                }
                if (! Schema::hasColumn('orders', 'coupon_snapshot')) {
                    $table->json('coupon_snapshot')->nullable()->after('coupon_code');
                }
            });
        }

        if (Schema::hasTable('coupons') && DB::table('coupons')->count() === 0) {
            DB::table('coupons')->insert([
                [
                    'name' => 'Team Order 10% Off',
                    'code' => 'TEAM10',
                    'description' => '10% off qualifying team orders. Maximum discount $75.',
                    'discount_type' => 'percentage',
                    'discount_value' => 10,
                    'minimum_subtotal' => 100,
                    'maximum_discount' => 75,
                    'usage_limit' => null,
                    'usage_limit_per_customer' => null,
                    'used_count' => 0,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'NextPlay $25 Off',
                    'code' => 'NEXTPLAY25',
                    'description' => '$25 off orders of $250 or more.',
                    'discount_type' => 'fixed',
                    'discount_value' => 25,
                    'minimum_subtotal' => 250,
                    'maximum_discount' => 25,
                    'usage_limit' => null,
                    'usage_limit_per_customer' => null,
                    'used_count' => 0,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table): void {
                if (Schema::hasColumn('orders', 'coupon_id')) {
                    $table->dropConstrainedForeignId('coupon_id');
                }
                if (Schema::hasColumn('orders', 'coupon_code')) {
                    $table->dropColumn('coupon_code');
                }
                if (Schema::hasColumn('orders', 'coupon_snapshot')) {
                    $table->dropColumn('coupon_snapshot');
                }
            });
        }

        if (Schema::hasTable('shopping_carts') && Schema::hasColumn('shopping_carts', 'coupon_id')) {
            Schema::table('shopping_carts', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('coupon_id');
            });
        }

        Schema::dropIfExists('coupon_redemptions');
        Schema::dropIfExists('coupons');
    }
};
