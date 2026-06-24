<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jersey_customization_options', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 60);
            $table->string('name', 160);
            $table->string('slug', 180);
            $table->string('color_hex', 7)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['type', 'slug'], 'jco_type_slug_unique');
            $table->index(['type', 'is_active', 'sort_order'], 'jco_type_active_sort_index');
        });

        Schema::create('jersey_customization_option_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('jersey_customization_option_id');
            $table->foreign(
                'jersey_customization_option_id',
                'jco_images_option_fk'
            )
                ->references('id')
                ->on('jersey_customization_options')
                ->cascadeOnDelete();
            $table->string('name', 180);
            $table->string('image_path', 2048)->nullable();
            $table->string('image_url', 2048)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(
                ['jersey_customization_option_id', 'is_primary', 'sort_order'],
                'jco_images_option_primary_sort_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jersey_customization_option_images');
        Schema::dropIfExists('jersey_customization_options');
    }
};
