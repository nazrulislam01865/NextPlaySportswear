<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'production_methods_enabled')) {
                $table->boolean('production_methods_enabled')->default(false)->after('shipping_class');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (Schema::hasColumn('products', 'production_methods_enabled')) {
                $table->dropColumn('production_methods_enabled');
            }
        });
    }
};
