<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_methods', function (Blueprint $table): void {
            $table->string('image_path', 2048)->nullable()->after('description');
            $table->string('image_url', 2048)->nullable()->after('image_path');
        });

        Schema::table('shipping_methods', function (Blueprint $table): void {
            $table->string('image_path', 2048)->nullable()->after('description');
            $table->string('image_url', 2048)->nullable()->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('production_methods', function (Blueprint $table): void {
            $table->dropColumn(['image_path', 'image_url']);
        });

        Schema::table('shipping_methods', function (Blueprint $table): void {
            $table->dropColumn(['image_path', 'image_url']);
        });
    }
};
