<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 255);
            $table->string('internal_code', 100)->nullable()->unique();
            $table->string('status', 20)->default('draft');
            $table->string('discount_type', 20)->default('percentage');
            $table->decimal('discount_value', 12, 2);
            $table->decimal('maximum_discount', 12, 2)->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('timezone', 100)->default('Europe/London');
            $table->boolean('repeat_weekdays')->default(false);
            $table->json('weekdays')->nullable();
            $table->string('applies_to', 40)->default('all');
            $table->boolean('show_sale_badge')->default(true);
            $table->boolean('show_sale_page')->default(true);
            $table->unsignedInteger('priority')->default(1);
            $table->foreignId('homepage_slide_id')->nullable()->constrained('homepage_slides')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'starts_at', 'ends_at'], 'sale_campaign_schedule_idx');
            $table->index(['priority', 'id'], 'sale_campaign_priority_idx');
        });

        Schema::create('sale_campaign_category', function (Blueprint $table): void {
            $table->foreignId('sale_campaign_id')->constrained('sale_campaigns')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->primary(['sale_campaign_id', 'category_id'], 'sale_campaign_category_pk');
        });

        Schema::create('sale_campaign_product', function (Blueprint $table): void {
            $table->foreignId('sale_campaign_id')->constrained('sale_campaigns')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->primary(['sale_campaign_id', 'product_id'], 'sale_campaign_product_pk');
        });

        Schema::create('sale_campaign_excluded_product', function (Blueprint $table): void {
            $table->foreignId('sale_campaign_id')->constrained('sale_campaigns')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->primary(['sale_campaign_id', 'product_id'], 'sale_campaign_excluded_product_pk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_campaign_excluded_product');
        Schema::dropIfExists('sale_campaign_product');
        Schema::dropIfExists('sale_campaign_category');
        Schema::dropIfExists('sale_campaigns');
    }
};
