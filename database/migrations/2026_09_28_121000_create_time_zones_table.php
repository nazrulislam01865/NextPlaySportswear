<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_zones', function (Blueprint $table): void {
            $table->id();
            $table->string('label', 160);
            $table->string('identifier', 100)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order', 'id'], 'time_zones_active_sort_idx');
        });

        $now = now();
        DB::table('time_zones')->insert([
            ['label' => 'London', 'identifier' => 'Europe/London', 'is_active' => true, 'sort_order' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['label' => 'UTC', 'identifier' => 'UTC', 'is_active' => true, 'sort_order' => 20, 'created_at' => $now, 'updated_at' => $now],
            ['label' => 'Dhaka', 'identifier' => 'Asia/Dhaka', 'is_active' => true, 'sort_order' => 30, 'created_at' => $now, 'updated_at' => $now],
            ['label' => 'Dubai', 'identifier' => 'Asia/Dubai', 'is_active' => true, 'sort_order' => 40, 'created_at' => $now, 'updated_at' => $now],
            ['label' => 'New York', 'identifier' => 'America/New_York', 'is_active' => true, 'sort_order' => 50, 'created_at' => $now, 'updated_at' => $now],
            ['label' => 'Los Angeles', 'identifier' => 'America/Los_Angeles', 'is_active' => true, 'sort_order' => 60, 'created_at' => $now, 'updated_at' => $now],
            ['label' => 'Paris', 'identifier' => 'Europe/Paris', 'is_active' => true, 'sort_order' => 70, 'created_at' => $now, 'updated_at' => $now],
            ['label' => 'Singapore', 'identifier' => 'Asia/Singapore', 'is_active' => true, 'sort_order' => 80, 'created_at' => $now, 'updated_at' => $now],
            ['label' => 'Sydney', 'identifier' => 'Australia/Sydney', 'is_active' => true, 'sort_order' => 90, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('time_zones');
    }
};
