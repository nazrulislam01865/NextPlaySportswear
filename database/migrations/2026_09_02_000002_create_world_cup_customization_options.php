<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('world_cup_customization_options', function (Blueprint $table): void {
            $table->id();
            $table->string('category_key', 80);
            $table->string('type', 80);
            $table->string('name', 160);
            $table->string('slug', 180);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['category_key', 'type', 'slug'], 'wcco_category_type_slug_unique');
            $table->index(['category_key', 'type', 'is_active', 'sort_order'], 'wcco_lookup_index');
        });

        Schema::create('world_cup_customization_option_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('world_cup_customization_option_id');
            $table->foreign('world_cup_customization_option_id', 'wcco_images_option_fk')
                ->references('id')
                ->on('world_cup_customization_options')
                ->cascadeOnDelete();
            $table->string('name', 180);
            $table->string('image_path', 2048)->nullable();
            $table->string('image_url', 2048)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(
                ['world_cup_customization_option_id', 'is_primary', 'sort_order'],
                'wcco_images_option_primary_sort_index'
            );
        });

        Schema::table('product_option_values', function (Blueprint $table): void {
            $table->unsignedBigInteger('world_cup_customization_option_id')
                ->nullable()
                ->after('jersey_customization_option_id');
            $table->foreign('world_cup_customization_option_id', 'pov_world_cup_option_fk')
                ->references('id')
                ->on('world_cup_customization_options')
                ->nullOnDelete();
            $table->index(
                ['product_option_group_id', 'world_cup_customization_option_id'],
                'pov_group_world_cup_option_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('product_option_values', function (Blueprint $table): void {
            $table->dropForeign('pov_world_cup_option_fk');
            $table->dropIndex('pov_group_world_cup_option_idx');
            $table->dropColumn('world_cup_customization_option_id');
        });

        Schema::dropIfExists('world_cup_customization_option_images');
        Schema::dropIfExists('world_cup_customization_options');
    }
};
