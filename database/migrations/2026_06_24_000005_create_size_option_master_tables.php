<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('size_option_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 160);
            $table->string('slug', 180)->unique();
            $table->string('audience', 40)->default('unisex');
            $table->longText('description_html')->nullable();
            $table->string('chart_title', 180)->nullable();
            $table->text('chart_note')->nullable();
            $table->json('chart_columns')->nullable();
            $table->json('chart_rows')->nullable();
            $table->string('chart_image_path', 2048)->nullable();
            $table->string('chart_image_url', 2048)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by', 'sog_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'sog_updated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['audience', 'is_active', 'sort_order'], 'sog_audience_active_sort_idx');
        });

        Schema::create('size_options', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('size_option_group_id');
            $table->string('label', 80);
            $table->string('code', 80);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('size_option_group_id', 'so_group_fk')
                ->references('id')->on('size_option_groups')->cascadeOnDelete();
            $table->unique(['size_option_group_id', 'code'], 'so_group_code_uq');
            $table->index(['size_option_group_id', 'is_active', 'sort_order'], 'so_group_active_sort_idx');
        });

        Schema::table('product_size_groups', function (Blueprint $table): void {
            $table->unsignedBigInteger('size_option_group_id')->nullable()->after('product_id');
            $table->longText('description_html')->nullable()->after('code');
            $table->foreign('size_option_group_id', 'psg_size_master_fk')
                ->references('id')->on('size_option_groups')->nullOnDelete();
            $table->index('size_option_group_id', 'psg_size_master_idx');
        });
    }

    public function down(): void
    {
        Schema::table('product_size_groups', function (Blueprint $table): void {
            $table->dropForeign('psg_size_master_fk');
            $table->dropIndex('psg_size_master_idx');
            $table->dropColumn(['size_option_group_id', 'description_html']);
        });

        Schema::dropIfExists('size_options');
        Schema::dropIfExists('size_option_groups');
    }
};
