<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holiday_calendars', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('country_code', 10)->index();
            $table->string('country_name', 120);
            $table->unsignedSmallInteger('year')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['country_code', 'year', 'is_active']);
        });

        Schema::create('holiday_calendar_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('holiday_calendar_id')
                ->constrained('holiday_calendars')
                ->cascadeOnDelete();
            $table->date('date')->index();
            $table->string('name', 160);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['holiday_calendar_id', 'date'], 'calendar_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holiday_calendar_dates');
        Schema::dropIfExists('holiday_calendars');
    }
};
