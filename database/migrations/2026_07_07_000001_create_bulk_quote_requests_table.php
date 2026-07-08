<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_quote_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 32)->unique();
            $table->string('full_name', 120);
            $table->string('organization', 160);
            $table->string('email', 190)->index();
            $table->string('phone', 40);
            $table->string('product_type', 190);
            $table->string('estimated_quantity', 40)->index();
            $table->string('sizes_needed', 190);
            $table->string('budget_range', 80)->nullable();
            $table->text('artwork_details');
            $table->json('customization_types')->nullable();
            $table->text('shipping_address');
            $table->string('country', 80)->index();
            $table->string('state_province', 120)->nullable();
            $table->string('postal_code', 40)->nullable();
            $table->string('preferred_shipping_method', 80)->nullable();
            $table->date('needed_by');
            $table->date('event_date')->nullable();
            $table->json('attachment')->nullable();
            $table->text('additional_notes')->nullable();
            $table->string('status', 30)->default('new')->index();
            $table->char('ip_hash', 64)->nullable()->index();
            $table->char('user_agent_hash', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_quote_requests');
    }
};
