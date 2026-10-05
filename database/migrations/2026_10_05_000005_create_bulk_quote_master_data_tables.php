<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('country_calling_codes', function (Blueprint $table): void {
            $table->id();
            $table->string('country_name', 120);
            $table->char('iso_code', 2)->unique();
            $table->string('dial_code', 12);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order', 'id'], 'country_calling_codes_active_sort_idx');
        });

        Schema::create('bulk_quote_budget_ranges', function (Blueprint $table): void {
            $table->id();
            $table->string('label', 120);
            $table->string('value', 80)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order', 'id'], 'bulk_quote_budget_ranges_active_sort_idx');
        });

        $now = now();

        DB::table('country_calling_codes')->insert([
            ['country_name' => 'United States', 'iso_code' => 'US', 'dial_code' => '+1', 'is_active' => true, 'sort_order' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['country_name' => 'Canada', 'iso_code' => 'CA', 'dial_code' => '+1', 'is_active' => true, 'sort_order' => 20, 'created_at' => $now, 'updated_at' => $now],
            ['country_name' => 'United Kingdom', 'iso_code' => 'GB', 'dial_code' => '+44', 'is_active' => true, 'sort_order' => 30, 'created_at' => $now, 'updated_at' => $now],
            ['country_name' => 'Australia', 'iso_code' => 'AU', 'dial_code' => '+61', 'is_active' => true, 'sort_order' => 40, 'created_at' => $now, 'updated_at' => $now],
            ['country_name' => 'Bangladesh', 'iso_code' => 'BD', 'dial_code' => '+880', 'is_active' => true, 'sort_order' => 50, 'created_at' => $now, 'updated_at' => $now],
            ['country_name' => 'New Zealand', 'iso_code' => 'NZ', 'dial_code' => '+64', 'is_active' => true, 'sort_order' => 60, 'created_at' => $now, 'updated_at' => $now],
            ['country_name' => 'United Arab Emirates', 'iso_code' => 'AE', 'dial_code' => '+971', 'is_active' => true, 'sort_order' => 70, 'created_at' => $now, 'updated_at' => $now],
            ['country_name' => 'India', 'iso_code' => 'IN', 'dial_code' => '+91', 'is_active' => true, 'sort_order' => 80, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('bulk_quote_budget_ranges')->insert([
            ['label' => 'Under $500', 'value' => 'under-500', 'is_active' => true, 'sort_order' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['label' => '$500–$1,500', 'value' => '500-1500', 'is_active' => true, 'sort_order' => 20, 'created_at' => $now, 'updated_at' => $now],
            ['label' => '$1,500–$5,000', 'value' => '1500-5000', 'is_active' => true, 'sort_order' => 30, 'created_at' => $now, 'updated_at' => $now],
            ['label' => '$5,000+', 'value' => '5000-plus', 'is_active' => true, 'sort_order' => 40, 'created_at' => $now, 'updated_at' => $now],
            ['label' => 'Not sure yet', 'value' => 'not-sure', 'is_active' => true, 'sort_order' => 50, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_quote_budget_ranges');
        Schema::dropIfExists('country_calling_codes');
    }
};
