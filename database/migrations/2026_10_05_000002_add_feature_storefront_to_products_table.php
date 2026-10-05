<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('products', 'feature_storefront')) {
            return;
        }

        Schema::table('products', function (Blueprint $table): void {
            $table->json('feature_storefront')->nullable()->after('feature_icons');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('products', 'feature_storefront')) {
            return;
        }

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('feature_storefront');
        });
    }
};
