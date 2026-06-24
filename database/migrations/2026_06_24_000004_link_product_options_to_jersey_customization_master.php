<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_option_groups', function (Blueprint $table): void {
            $table->string('jersey_customization_type', 60)
                ->nullable()
                ->after('type')
                ->index('pog_jersey_type_idx');
        });

        Schema::table('product_option_values', function (Blueprint $table): void {
            $table->unsignedBigInteger('jersey_customization_option_id')
                ->nullable()
                ->after('product_option_group_id');

            $table->foreign(
                'jersey_customization_option_id',
                'pov_jersey_option_fk'
            )
                ->references('id')
                ->on('jersey_customization_options')
                ->nullOnDelete();

            $table->index(
                ['product_option_group_id', 'jersey_customization_option_id'],
                'pov_group_jersey_option_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('product_option_values', function (Blueprint $table): void {
            $table->dropForeign('pov_jersey_option_fk');
            $table->dropIndex('pov_group_jersey_option_idx');
            $table->dropColumn('jersey_customization_option_id');
        });

        Schema::table('product_option_groups', function (Blueprint $table): void {
            $table->dropIndex('pog_jersey_type_idx');
            $table->dropColumn('jersey_customization_type');
        });
    }
};
