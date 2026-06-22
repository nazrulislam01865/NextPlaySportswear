<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
                $table->string('name', 160);
                $table->string('slug', 180)->unique();
                $table->string('display_type', 30)->default('collection');
                $table->string('eyebrow', 160)->nullable();
                $table->string('short_title', 160)->nullable();
                $table->text('description');
                $table->text('best_for')->nullable();
                $table->string('image_url', 2048);
                $table->string('image_alt', 255);
                $table->string('cta_label', 160)->default('View Category');
                $table->string('meta_title', 255)->nullable();
                $table->text('meta_description')->nullable();
                $table->json('match_rules')->nullable();
                $table->json('highlights')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['display_type', 'is_active', 'sort_order']);
                $table->index(['parent_id', 'is_active']);
            });
        }

        if (! Schema::hasTable('category_tags')) {
            Schema::create('category_tags', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 100);
                $table->string('slug', 120)->unique();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['is_active', 'sort_order']);
            });
        }

        if (! Schema::hasTable('category_category_tag')) {
            Schema::create('category_category_tag', function (Blueprint $table): void {
                $table->foreignId('category_id')->constrained()->cascadeOnDelete();
                $table->foreignId('category_tag_id')->constrained()->cascadeOnDelete();
                $table->primary(['category_id', 'category_tag_id']);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('category_category_tag');
        Schema::dropIfExists('category_tags');
        Schema::dropIfExists('categories');
    }
};
