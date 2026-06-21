<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_addresses')) {
            return;
        }

        Schema::create('customer_addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30)->default('shipping');
            $table->string('first_name', 120);
            $table->string('last_name', 120);
            $table->string('company_name', 160)->nullable();
            $table->string('address_line_1', 190);
            $table->string('address_line_2', 190)->nullable();
            $table->string('city', 120);
            $table->string('state', 120)->nullable();
            $table->string('country', 120)->default('United States');
            $table->string('postal_code', 30);
            $table->string('phone', 40)->nullable();
            $table->string('email', 255)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
