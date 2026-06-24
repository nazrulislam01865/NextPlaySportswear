<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('product_profile', 40)->default('standard')->after('product_type');
            $table->boolean('shipping_methods_enabled')->default(false)->after('shipping_class');
            $table->boolean('jersey_roster_enabled')->default(false)->after('shipping_methods_enabled');
            $table->boolean('jersey_roster_optional')->default(true)->after('jersey_roster_enabled');
            $table->string('jersey_roster_title', 180)->nullable()->after('jersey_roster_optional');
            $table->json('jersey_roster_fields')->nullable()->after('jersey_roster_title');
        });

        Schema::table('product_option_groups', function (Blueprint $table): void {
            $table->string('display_mode', 20)->default('customer')->after('type');
            $table->string('fixed_value_code', 180)->nullable()->after('display_mode');
            $table->text('fixed_text_value')->nullable()->after('fixed_value_code');
            $table->boolean('show_in_summary')->default(true)->after('fixed_text_value');
        });

        Schema::table('product_option_values', function (Blueprint $table): void {
            $table->string('charge_type', 24)->default('per_unit')->after('price_adjustment');
            $table->json('image_gallery')->nullable()->after('image_url');
        });

        Schema::table('product_size_groups', function (Blueprint $table): void {
            $table->boolean('chart_enabled')->default(false)->after('code');
            $table->string('chart_title', 180)->nullable()->after('chart_enabled');
            $table->text('chart_note')->nullable()->after('chart_title');
            $table->json('chart_columns')->nullable()->after('chart_note');
            $table->json('chart_rows')->nullable()->after('chart_columns');
            $table->string('chart_image_path', 2048)->nullable()->after('chart_rows');
            $table->string('chart_image_url', 2048)->nullable()->after('chart_image_path');
        });

        Schema::create('product_shipping_methods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('code', 160);
            $table->text('description')->nullable();
            $table->decimal('price_adjustment', 12, 2)->default(0);
            $table->string('charge_type', 24)->default('per_unit');
            $table->unsignedInteger('minimum_days')->default(1);
            $table->unsignedInteger('maximum_days')->default(1);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'code'], 'ship_method_product_code_uq');
            $table->index(['product_id', 'is_active', 'sort_order'], 'ship_method_product_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_shipping_methods');

        Schema::table('product_size_groups', function (Blueprint $table): void {
            $table->dropColumn([
                'chart_enabled', 'chart_title', 'chart_note', 'chart_columns', 'chart_rows',
                'chart_image_path', 'chart_image_url',
            ]);
        });

        Schema::table('product_option_values', function (Blueprint $table): void {
            $table->dropColumn(['charge_type', 'image_gallery']);
        });

        Schema::table('product_option_groups', function (Blueprint $table): void {
            $table->dropColumn(['display_mode', 'fixed_value_code', 'fixed_text_value', 'show_in_summary']);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn([
                'product_profile', 'shipping_methods_enabled', 'jersey_roster_enabled',
                'jersey_roster_optional', 'jersey_roster_title', 'jersey_roster_fields',
            ]);
        });
    }
};
