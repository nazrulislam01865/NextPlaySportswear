<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_sections', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 80)->unique();
            $table->string('name', 160);
            $table->string('eyebrow', 160)->nullable();
            $table->string('title', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('primary_label', 160)->nullable();
            $table->text('primary_url')->nullable();
            $table->string('secondary_label', 160)->nullable();
            $table->text('secondary_url')->nullable();
            $table->text('image_path')->nullable();
            $table->text('image_url')->nullable();
            $table->string('image_alt', 255)->nullable();
            $table->json('items')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'sort_order'], 'homepage_sections_visibility_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_sections');
    }
};
