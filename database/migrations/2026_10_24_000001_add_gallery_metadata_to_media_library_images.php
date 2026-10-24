<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_library_images', function (Blueprint $table): void {
            $table->string('original_filename', 255)->nullable();
            $table->string('category', 32)->nullable()->index();
            $table->string('caption', 255)->nullable();
            $table->text('description')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('media_library_images', function (Blueprint $table): void {
            $table->dropIndex(['category']);
            $table->dropColumn(['original_filename', 'category', 'caption', 'description']);
        });
    }
};
