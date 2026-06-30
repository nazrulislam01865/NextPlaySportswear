<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_methods')) {
            Schema::create('payment_methods', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 160);
                $table->string('code', 160)->unique();
                $table->string('provider', 80)->default('manual');
                $table->string('payment_type', 50)->default('manual');
                $table->string('badge', 80)->nullable();
                $table->text('description')->nullable();
                $table->text('instructions')->nullable();
                $table->decimal('minimum_total', 12, 2)->nullable();
                $table->decimal('maximum_total', 12, 2)->nullable();
                $table->boolean('is_online')->default(false);
                $table->boolean('requires_provider_redirect')->default(false);
                $table->boolean('requires_manual_review')->default(false);
                $table->boolean('allows_saved_methods')->default(false);
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['is_active', 'sort_order']);
                $table->index(['payment_type', 'provider']);
            });
        }

        if (Schema::hasTable('payment_methods') && DB::table('payment_methods')->count() === 0) {
            $now = now();

            DB::table('payment_methods')->insert([
                [
                    'name' => 'Credit / Debit Card',
                    'code' => 'card',
                    'provider' => 'stripe',
                    'payment_type' => 'card',
                    'badge' => 'Secure',
                    'description' => 'Pay securely by card through a PCI-compliant hosted payment provider.',
                    'instructions' => 'The application never stores raw card numbers or CVV. The final amount is confirmed by Laravel before redirecting to the payment provider.',
                    'minimum_total' => null,
                    'maximum_total' => null,
                    'is_online' => true,
                    'requires_provider_redirect' => true,
                    'requires_manual_review' => false,
                    'allows_saved_methods' => true,
                    'is_default' => true,
                    'is_active' => true,
                    'sort_order' => 10,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'PayPal',
                    'code' => 'paypal',
                    'provider' => 'paypal',
                    'payment_type' => 'paypal',
                    'badge' => 'PayPal',
                    'description' => 'Use PayPal checkout for eligible online orders.',
                    'instructions' => 'You will be redirected to PayPal after reviewing the order.',
                    'minimum_total' => null,
                    'maximum_total' => null,
                    'is_online' => true,
                    'requires_provider_redirect' => true,
                    'requires_manual_review' => false,
                    'allows_saved_methods' => false,
                    'is_default' => false,
                    'is_active' => true,
                    'sort_order' => 20,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'Request Invoice for Bulk Order',
                    'code' => 'invoice',
                    'provider' => 'manual',
                    'payment_type' => 'invoice',
                    'badge' => 'Invoice',
                    'description' => 'Best for schools, leagues, businesses, and quote-based custom orders.',
                    'instructions' => 'The order will be submitted for admin review. Admin can confirm payment terms before production starts.',
                    'minimum_total' => null,
                    'maximum_total' => null,
                    'is_online' => false,
                    'requires_provider_redirect' => false,
                    'requires_manual_review' => true,
                    'allows_saved_methods' => false,
                    'is_default' => false,
                    'is_active' => true,
                    'sort_order' => 30,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'Bank Transfer',
                    'code' => 'bank-transfer',
                    'provider' => 'manual',
                    'payment_type' => 'bank_transfer',
                    'badge' => 'Manual',
                    'description' => 'Submit the order and pay by bank transfer after admin confirms payment instructions.',
                    'instructions' => 'Admin should send bank transfer details and verify payment manually before production.',
                    'minimum_total' => null,
                    'maximum_total' => null,
                    'is_online' => false,
                    'requires_provider_redirect' => false,
                    'requires_manual_review' => true,
                    'allows_saved_methods' => false,
                    'is_default' => false,
                    'is_active' => false,
                    'sort_order' => 40,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
