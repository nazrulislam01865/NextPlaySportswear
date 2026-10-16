<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('storefront_branding_settings', 'favicon_path')) {
            Schema::table('storefront_branding_settings', function (Blueprint $table): void {
                $table->text('favicon_path')->nullable()->after('logo_path');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('storefront_branding_settings', 'favicon_path')) {
            Schema::table('storefront_branding_settings', function (Blueprint $table): void {
                $table->dropColumn('favicon_path');
            });
        }
    }
};
