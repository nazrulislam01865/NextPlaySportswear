<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name', 220);
            $table->string('slug', 240)->unique();
            $table->string('sku', 120)->unique();
            $table->string('status', 30)->default('draft');
            $table->string('product_type', 100)->nullable();
            $table->string('brand', 120)->nullable();
            $table->string('badge_label', 80)->nullable();
            $table->string('badge_color', 30)->nullable();
            $table->text('short_description')->nullable();
            $table->longText('description_html')->nullable();
            $table->json('features')->nullable();
            $table->json('specifications')->nullable();
            $table->decimal('base_price', 12, 2)->default(0);
            $table->decimal('compare_at_price', 12, 2)->nullable();
            $table->decimal('cost_price', 12, 2)->nullable();
            $table->char('currency', 3)->default('USD');
            $table->unsignedInteger('minimum_quantity')->default(1);
            $table->unsignedInteger('maximum_quantity')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_customizable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('track_inventory')->default(false);
            $table->integer('stock_quantity')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->boolean('allow_backorder')->default(false);
            $table->decimal('weight', 10, 3)->nullable();
            $table->json('dimensions')->nullable();
            $table->string('shipping_class', 100)->nullable();
            $table->string('tax_class', 100)->nullable();
            $table->json('tags')->nullable();
            $table->json('price_table_headers')->nullable();
            $table->json('price_table_rows')->nullable();
            $table->unsignedInteger('price_table_highlight_column')->nullable();
            $table->text('price_table_note')->nullable();
            $table->string('meta_title', 255)->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->string('canonical_url', 2048)->nullable();
            $table->string('og_title', 255)->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image_url', 2048)->nullable();
            $table->boolean('robots_index')->default(true);
            $table->boolean('robots_follow')->default(true);
            $table->json('schema_json')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_active', 'is_featured']);
            $table->index(['category_id', 'subcategory_id', 'status']);
            $table->index(['track_inventory', 'stock_quantity']);
        });

        Schema::create('product_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path', 2048)->nullable();
            $table->string('url', 2048)->nullable();
            $table->string('alt_text', 255)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['product_id', 'is_primary', 'sort_order']);
        });

        Schema::create('product_option_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('code', 160);
            $table->string('section', 40)->default('product');
            $table->string('type', 40)->default('select');
            $table->text('description')->nullable();
            $table->string('placeholder', 255)->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('minimum_selections')->nullable();
            $table->unsignedInteger('maximum_selections')->nullable();
            $table->string('accepted_file_types', 500)->nullable();
            $table->unsignedInteger('maximum_file_size_mb')->nullable();
            $table->json('validation_rules')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'code']);
            $table->index(['product_id', 'section', 'is_active', 'sort_order'], 'pog_product_section_active_sort_idx');
        });

        Schema::create('product_option_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_option_group_id')->constrained()->cascadeOnDelete();
            $table->string('label', 180);
            $table->string('code', 180);
            $table->text('description')->nullable();
            $table->string('color_hex', 20)->nullable();
            $table->string('image_path', 2048)->nullable();
            $table->string('image_url', 2048)->nullable();
            $table->decimal('price_adjustment', 12, 2)->default(0);
            $table->integer('stock_quantity')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['product_option_group_id', 'code'], 'product_option_value_code_unique');
            $table->index(['product_option_group_id', 'is_active', 'sort_order'], 'product_option_values_lookup_index');
        });

        Schema::create('product_size_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('code', 120);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'code']);
        });

        Schema::create('product_sizes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_size_group_id')->constrained()->cascadeOnDelete();
            $table->string('label', 80);
            $table->string('code', 80);
            $table->decimal('price_adjustment', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['product_size_group_id', 'code']);
        });

        Schema::create('product_price_tiers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('label', 120)->nullable();
            $table->unsignedInteger('minimum_quantity');
            $table->unsignedInteger('maximum_quantity')->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->decimal('compare_at_price', 12, 2)->nullable();
            $table->string('savings_label', 120)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['product_id', 'minimum_quantity', 'maximum_quantity'], 'ppt_product_qty_range_idx');
        });

        Schema::create('product_artwork_methods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('code', 160);
            $table->string('icon', 40)->nullable();
            $table->text('description')->nullable();
            $table->decimal('price_adjustment', 12, 2)->default(0);
            $table->boolean('requires_upload')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'code']);
        });

        Schema::create('product_production_speeds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('code', 160);
            $table->text('description')->nullable();
            $table->decimal('price_adjustment', 12, 2)->default(0);
            $table->unsignedInteger('minimum_days')->default(1);
            $table->unsignedInteger('maximum_days')->default(1);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'code']);
        });

        Schema::create('product_faqs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('question', 500);
            $table->text('answer');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['product_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_faqs');
        Schema::dropIfExists('product_production_speeds');
        Schema::dropIfExists('product_artwork_methods');
        Schema::dropIfExists('product_price_tiers');
        Schema::dropIfExists('product_sizes');
        Schema::dropIfExists('product_size_groups');
        Schema::dropIfExists('product_option_values');
        Schema::dropIfExists('product_option_groups');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
    }
};
