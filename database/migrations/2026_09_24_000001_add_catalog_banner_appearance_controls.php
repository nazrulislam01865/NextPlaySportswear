<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('categories') && ! Schema::hasColumn('categories', 'banner_color')) {
            Schema::table('categories', function (Blueprint $table): void {
                $table->string('banner_color', 7)->nullable()->after('banner_alt');
            });
        }

        if (! Schema::hasTable('catalog_page_settings')) {
            Schema::create('catalog_page_settings', function (Blueprint $table): void {
                $table->id();
                $table->text('products_banner_path')->nullable();
                $table->string('products_banner_color', 7)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('catalog_page_settings')) {
            Schema::drop('catalog_page_settings');
        }

        if (Schema::hasTable('categories') && Schema::hasColumn('categories', 'banner_color')) {
            Schema::table('categories', function (Blueprint $table): void {
                $table->dropColumn('banner_color');
            });
        }
    }
};
