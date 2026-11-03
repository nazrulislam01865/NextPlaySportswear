<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Separate unique provider identity from older refund records, which may
        // contain non-unique or manually entered legacy provider references.
        Schema::create('stripe_refund_syncs', function (Blueprint $table): void {
            $table->id();
            $table->boolean('livemode');
            $table->string('provider_refund_id', 190);
            $table->foreignId('order_refund_id')->nullable()->constrained('order_refunds')->nullOnDelete();
            $table->string('status', 30)->default('exception');
            $table->string('exception_code', 80)->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
            $table->unique(['livemode', 'provider_refund_id'], 'stripe_refund_identity_unique');
            $table->index(['status', 'checked_at']);
        });

        Schema::create('order_installment_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->foreignId('bulk_quote_request_id')->unique()->constrained('bulk_quote_requests')->restrictOnDelete();
            $table->char('currency', 3);
            $table->unsignedBigInteger('total_minor');
            $table->char('accepted_terms_hash', 64);
            $table->timestamp('accepted_at');
            $table->timestamps();
        });

        Schema::create('order_payment_installments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_installment_schedule_id')->constrained('order_installment_schedules')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->unsignedTinyInteger('sequence');
            $table->string('kind', 20);
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->timestamp('due_at')->nullable();
            $table->timestamps();
            $table->unique(['order_id', 'sequence'], 'order_installment_sequence_unique');
        });

        Schema::table('order_payments', function (Blueprint $table): void {
            $table->foreignId('order_payment_installment_id')->nullable()
                ->constrained('order_payment_installments')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_payments', fn (Blueprint $table) => $table->dropConstrainedForeignId('order_payment_installment_id'));
        Schema::dropIfExists('order_payment_installments');
        Schema::dropIfExists('order_installment_schedules');
        Schema::dropIfExists('stripe_refund_syncs');
    }
};
