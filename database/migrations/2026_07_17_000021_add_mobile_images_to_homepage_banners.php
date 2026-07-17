<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homepage_slides', function (Blueprint $table): void {
            if (! Schema::hasColumn('homepage_slides', 'mobile_image_path')) {
                $table->text('mobile_image_path')->nullable()->after('image_alt');
            }
            if (! Schema::hasColumn('homepage_slides', 'mobile_image_url')) {
                $table->text('mobile_image_url')->nullable()->after('mobile_image_path');
            }
            if (! Schema::hasColumn('homepage_slides', 'mobile_image_alt')) {
                $table->string('mobile_image_alt', 255)->nullable()->after('mobile_image_url');
            }
        });

        Schema::table('homepage_sections', function (Blueprint $table): void {
            if (! Schema::hasColumn('homepage_sections', 'mobile_image_path')) {
                $table->text('mobile_image_path')->nullable()->after('image_alt');
            }
            if (! Schema::hasColumn('homepage_sections', 'mobile_image_url')) {
                $table->text('mobile_image_url')->nullable()->after('mobile_image_path');
            }
            if (! Schema::hasColumn('homepage_sections', 'mobile_image_alt')) {
                $table->string('mobile_image_alt', 255)->nullable()->after('mobile_image_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('homepage_slides', function (Blueprint $table): void {
            foreach (['mobile_image_alt', 'mobile_image_url', 'mobile_image_path'] as $column) {
                if (Schema::hasColumn('homepage_slides', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('homepage_sections', function (Blueprint $table): void {
            foreach (['mobile_image_alt', 'mobile_image_url', 'mobile_image_path'] as $column) {
                if (Schema::hasColumn('homepage_sections', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
