<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'customization_artwork_html')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->longText('customization_artwork_html')->nullable()->after('detail_information_html');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'customization_artwork_html')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->dropColumn('customization_artwork_html');
            });
        }
    }
};
