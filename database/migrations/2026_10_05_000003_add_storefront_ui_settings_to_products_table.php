<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'storefront_ui_settings')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->json('storefront_ui_settings')->nullable()->after('artwork_upload_accepted_types');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'storefront_ui_settings')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->dropColumn('storefront_ui_settings');
            });
        }
    }
};
