<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('holiday_calendar_revisions', function (Blueprint $table): void {
            $table->string('country_code', 10);
            $table->unsignedSmallInteger('year');
            $table->unsignedBigInteger('revision')->default(0);
            $table->timestamps();
            $table->primary(['country_code', 'year']);
        });

        Schema::create('holiday_eta_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('event_key', 64)->unique();
            $table->json('payload');
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('processing_at')->nullable();
            $table->timestamp('last_dispatched_at')->nullable();
            $table->timestamps();
            $table->index(['queued_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holiday_eta_notifications');
        Schema::dropIfExists('holiday_calendar_revisions');
    }
};
