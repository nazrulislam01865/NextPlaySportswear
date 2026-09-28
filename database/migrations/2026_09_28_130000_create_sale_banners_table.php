<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_banners', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_campaign_id')->nullable()->constrained('sale_campaigns')->nullOnDelete();
            $table->string('name');
            $table->string('desktop_image_path')->nullable();
            $table->string('mobile_image_path')->nullable();
            $table->string('alt_text');
            $table->string('heading');
            $table->string('cta_label', 80);
            $table->string('destination_link', 2048);
            $table->json('placements');
            $table->unsignedInteger('priority')->default(1);
            $table->boolean('inherit_campaign_schedule')->default(true);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->string('timezone', 100)->default('Europe/London');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'priority', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_banners');
    }
};
