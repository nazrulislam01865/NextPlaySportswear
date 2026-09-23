<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('homepage_sections') || Schema::hasColumn('homepage_sections', 'settings')) {
            return;
        }

        Schema::table('homepage_sections', function (Blueprint $table): void {
            $table->json('settings')->nullable()->after('items');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('homepage_sections') || ! Schema::hasColumn('homepage_sections', 'settings')) {
            return;
        }

        Schema::table('homepage_sections', function (Blueprint $table): void {
            $table->dropColumn('settings');
        });
    }
};
