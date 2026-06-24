<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_option_groups', function (Blueprint $table): void {
            $table->boolean('use_as_filter')->default(false)->after('show_in_summary');
            $table->foreignId('catalog_attribute_id')
                ->nullable()
                ->after('use_as_filter')
                ->constrained('attributes')
                ->nullOnDelete();

            $table->index(
                ['product_id', 'use_as_filter', 'catalog_attribute_id'],
                'pog_product_filter_attribute_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('product_option_groups', function (Blueprint $table): void {
            $table->dropIndex('pog_product_filter_attribute_idx');
            $table->dropConstrainedForeignId('catalog_attribute_id');
            $table->dropColumn('use_as_filter');
        });
    }
};
