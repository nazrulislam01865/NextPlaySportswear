<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'fulfillment_html')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->longText('fulfillment_html')->nullable()->after('customization_artwork_html');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'fulfillment_html')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->dropColumn('fulfillment_html');
            });
        }
    }
};
