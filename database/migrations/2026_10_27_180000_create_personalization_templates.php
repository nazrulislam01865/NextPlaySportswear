<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('personalization_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('name', 160);
            $table->string('section_title', 180);
            $table->text('description')->nullable();
            $table->string('shared_entry_label', 180)->nullable();
            $table->boolean('shared_entry_enabled')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_published')->default(false);
            $table->json('fields')->nullable();
            $table->timestamps();
            $table->index(['category_id', 'is_published', 'is_active'], 'pt_category_state_idx');
            $table->index('name');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->foreignId('personalization_template_id')->nullable()->after('jersey_roster_title')
                ->constrained('personalization_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->dropConstrainedForeignId('personalization_template_id'));
        Schema::dropIfExists('personalization_templates');
    }
};
