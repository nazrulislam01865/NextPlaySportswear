<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefront_branding_settings', function (Blueprint $table): void {
            $table->text('footer_logo_path')->nullable()->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('storefront_branding_settings', function (Blueprint $table): void {
            $table->dropColumn('footer_logo_path');
        });
    }
};
