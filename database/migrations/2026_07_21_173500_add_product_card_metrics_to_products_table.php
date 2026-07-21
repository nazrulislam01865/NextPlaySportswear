<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'rating_average')) {
                $table->decimal('rating_average', 3, 2)->nullable()->after('cost_price');
            }

            if (! Schema::hasColumn('products', 'reviews_count')) {
                $table->unsignedInteger('reviews_count')->nullable()->after('rating_average');
            }

            if (! Schema::hasColumn('products', 'recent_viewers_count')) {
                $table->unsignedInteger('recent_viewers_count')->nullable()->after('reviews_count');
            }

            if (! Schema::hasColumn('products', 'favorites_count')) {
                $table->unsignedInteger('favorites_count')->nullable()->after('recent_viewers_count');
            }

            if (! Schema::hasColumn('products', 'recent_orders_count')) {
                $table->unsignedInteger('recent_orders_count')->nullable()->after('favorites_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            foreach (['recent_orders_count', 'favorites_count', 'recent_viewers_count', 'reviews_count', 'rating_average'] as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
