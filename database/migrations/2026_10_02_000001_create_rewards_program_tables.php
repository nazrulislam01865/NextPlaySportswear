<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_program_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_active')->default(true);
            $table->decimal('default_progress_target', 12, 2)->default(100);
            $table->decimal('default_reward_amount', 12, 2)->default(5);
            $table->boolean('referral_enabled')->default(true);
            $table->decimal('referral_friend_reward_amount', 12, 2)->default(5);
            $table->decimal('referral_referrer_reward_amount', 12, 2)->default(5);
            $table->decimal('referral_minimum_order', 12, 2)->default(50);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        DB::table('reward_program_settings')->insert([
            'is_active' => true,
            'default_progress_target' => 100,
            'default_reward_amount' => 5,
            'referral_enabled' => true,
            'referral_friend_reward_amount' => 5,
            'referral_referrer_reward_amount' => 5,
            'referral_minimum_order' => 50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('customer_reward_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('progress_amount', 12, 2)->default(0);
            $table->decimal('progress_target_override', 12, 2)->nullable();
            $table->decimal('reward_amount_override', 12, 2)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('customer_referrals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->string('status', 30)->default('pending');
            $table->decimal('friend_reward_amount', 12, 2)->default(0);
            $table->decimal('referrer_reward_amount', 12, 2)->default(0);
            $table->decimal('minimum_order', 12, 2)->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('rewarded_at')->nullable();
            $table->timestamps();
            $table->index(['referrer_id', 'status']);
        });

        Schema::create('reward_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('customer_referral_id')->nullable()->constrained('customer_referrals')->nullOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40);
            $table->string('status', 24)->default('posted');
            $table->decimal('amount', 12, 2);
            $table->string('source_key', 190)->nullable()->unique();
            $table->string('description', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_transactions');
        Schema::dropIfExists('customer_referrals');
        Schema::dropIfExists('customer_reward_profiles');
        Schema::dropIfExists('reward_program_settings');
    }
};
