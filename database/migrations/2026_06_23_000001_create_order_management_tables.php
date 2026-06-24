<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_number', 40)->unique();
            $table->string('status', 40)->default('pending_payment');
            $table->string('payment_status', 30)->default('pending');
            $table->string('fulfillment_status', 30)->default('unfulfilled');
            $table->char('currency', 3)->default('USD');
            $table->string('customer_name', 180);
            $table->string('customer_email', 255);
            $table->string('customer_phone', 40)->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('customization_total', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->unsignedInteger('total_quantity')->default(0);
            $table->json('information')->nullable();
            $table->json('shipping_address')->nullable();
            $table->json('billing_address')->nullable();
            $table->json('shipping_method')->nullable();
            $table->json('payment_method')->nullable();
            $table->text('customer_note')->nullable();
            $table->text('admin_note')->nullable();
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'placed_at']);
            $table->index(['status', 'payment_status', 'fulfillment_status']);
            $table->index(['customer_email', 'order_number']);
        });

        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_slug', 240)->nullable();
            $table->string('product_name', 220);
            $table->string('sku', 120)->nullable();
            $table->string('image_url', 2048)->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('fulfilled_quantity')->default(0);
            $table->unsignedInteger('cancelled_quantity')->default(0);
            $table->unsignedInteger('returned_quantity')->default(0);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('customization_unit_price', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2);
            $table->json('customization')->nullable();
            $table->boolean('is_digital')->default(false);
            $table->timestamps();
            $table->index(['order_id', 'product_id']);
        });

        Schema::create('order_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 50)->default('unconfigured');
            $table->string('provider_reference', 190)->nullable()->index();
            $table->string('status', 30)->default('pending');
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('USD');
            $table->string('failure_code', 100)->nullable();
            $table->text('failure_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'status']);
        });

        Schema::create('order_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 50);
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['order_id', 'occurred_at']);
        });

        Schema::create('order_shipments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('shipment_number', 50)->unique();
            $table->string('status', 30)->default('preparing');
            $table->string('carrier', 100)->nullable();
            $table->string('service', 120)->nullable();
            $table->string('tracking_number', 190)->nullable()->index();
            $table->string('tracking_url', 2048)->nullable();
            $table->json('shipping_address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('estimated_delivery_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'status']);
        });

        Schema::create('order_shipment_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_shipment_id')->constrained('order_shipments')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();
            $table->unique(['order_shipment_id', 'order_item_id'], 'shipment_item_unique');
        });

        Schema::create('order_change_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('request_number', 50)->unique();
            $table->string('type', 20);
            $table->string('scope', 20)->default('entire_order');
            $table->string('status', 30)->default('pending');
            $table->string('reason_code', 80);
            $table->text('reason')->nullable();
            $table->json('requested_changes')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'type', 'status']);
        });

        Schema::create('order_return_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('return_number', 50)->unique();
            $table->string('type', 20)->default('return');
            $table->string('status', 30)->default('requested');
            $table->string('reason_code', 80);
            $table->text('reason')->nullable();
            $table->string('requested_resolution', 30)->default('refund');
            $table->text('exchange_notes')->nullable();
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status', 'requested_at']);
            $table->index(['order_id', 'type', 'status']);
        });

        Schema::create('order_return_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_return_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('item_condition', 60)->nullable();
            $table->text('customer_note')->nullable();
            $table->json('exchange_configuration')->nullable();
            $table->timestamps();
            $table->unique(['order_return_request_id', 'order_item_id'], 'return_item_unique');
        });

        Schema::create('order_return_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_return_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('file_path', 2048);
            $table->string('original_name', 255);
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();
            $table->index(['order_return_request_id', 'created_at'], 'return_attachment_request_created_idx');
        });

        Schema::create('order_refunds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_return_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('refund_number', 50)->unique();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('USD');
            $table->string('method', 50)->default('original_payment');
            $table->string('status', 30)->default('pending');
            $table->string('provider_reference', 190)->nullable();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'status']);
        });

        Schema::create('order_credit_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_refund_id')->nullable()->constrained()->nullOnDelete();
            $table->string('credit_note_number', 50)->unique();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('USD');
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('issued_at');
            $table->timestamps();
            $table->index(['order_id', 'issued_at']);
        });

        Schema::create('order_downloads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('token', 64)->unique();
            $table->string('title', 220);
            $table->string('file_path', 2048);
            $table->string('original_name', 255);
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedInteger('download_limit')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('license_note')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'is_active', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_downloads');
        Schema::dropIfExists('order_credit_notes');
        Schema::dropIfExists('order_refunds');
        Schema::dropIfExists('order_return_attachments');
        Schema::dropIfExists('order_return_items');
        Schema::dropIfExists('order_return_requests');
        Schema::dropIfExists('order_change_requests');
        Schema::dropIfExists('order_shipment_items');
        Schema::dropIfExists('order_shipments');
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
