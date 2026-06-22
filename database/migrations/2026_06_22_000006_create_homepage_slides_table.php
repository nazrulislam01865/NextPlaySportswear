<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_slides', function (Blueprint $table): void {
            $table->id();
            $table->string('eyebrow', 160)->nullable();
            $table->string('title', 255)->nullable();
            $table->text('description')->nullable();

            $table->text('image_path')->nullable();
            $table->text('image_url')->nullable();
            $table->string('image_alt', 255)->nullable();
            $table->string('image_focal_position', 30)->default('center');

            $table->boolean('show_content')->default(true);
            $table->boolean('show_eyebrow')->default(true);
            $table->boolean('show_title')->default(true);
            $table->boolean('show_description')->default(true);

            $table->boolean('show_primary_button')->default(true);
            $table->string('primary_label', 160)->nullable();
            $table->text('primary_url')->nullable();
            $table->string('primary_target', 20)->default('_self');

            $table->boolean('show_secondary_button')->default(false);
            $table->string('secondary_label', 160)->nullable();
            $table->text('secondary_url')->nullable();
            $table->string('secondary_target', 20)->default('_self');

            $table->string('content_position', 20)->default('left');
            $table->string('text_alignment', 20)->default('left');
            $table->string('text_theme', 20)->default('light');
            $table->string('overlay_color', 7)->default('#0D2545');
            $table->unsignedTinyInteger('overlay_opacity')->default(72);

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['is_active', 'starts_at', 'ends_at', 'sort_order'],
                'home_slide_visibility_sort_idx'
            );
            $table->index(['sort_order', 'id'], 'home_slide_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_slides');
    }
};
