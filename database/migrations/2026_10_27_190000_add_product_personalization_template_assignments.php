<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('product_personalization_template')) {
            Schema::create('product_personalization_template', function (Blueprint $table): void {
                $table->foreignId('product_id');
                $table->foreignId('personalization_template_id');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->primary(['product_id', 'personalization_template_id'], 'product_personalization_pk');
                $table->index(['personalization_template_id', 'product_id'], 'product_personalization_template_idx');
                $table->foreign('product_id', 'ppt_product_fk')->references('id')->on('products')->cascadeOnDelete();
                $table->foreign('personalization_template_id', 'ppt_template_fk')
                    ->references('id')->on('personalization_templates')->cascadeOnDelete();
            });
        } else {
            // A failed MySQL CREATE TABLE migration may leave the table and the first FK in place.
            // Add only missing constraints; never drop existing assignments.
            $foreignKeyColumns = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', 'product_personalization_template')
                ->whereNotNull('REFERENCED_TABLE_NAME')
                ->pluck('COLUMN_NAME')
                ->all();

            if (! in_array('product_id', $foreignKeyColumns, true)) {
                Schema::table('product_personalization_template', function (Blueprint $table): void {
                    $table->foreign('product_id', 'ppt_product_fk')
                        ->references('id')->on('products')->cascadeOnDelete();
                });
            }

            if (! in_array('personalization_template_id', $foreignKeyColumns, true)) {
                Schema::table('product_personalization_template', function (Blueprint $table): void {
                    $table->foreign('personalization_template_id', 'ppt_template_fk')
                        ->references('id')->on('personalization_templates')->cascadeOnDelete();
                });
            }
        }

        // Preserve existing single-template assignments; also safe to retry after a partial failure.
        DB::table('products')->whereNotNull('personalization_template_id')->orderBy('id')
            ->chunkById(500, function ($products): void {
                DB::table('product_personalization_template')->insertOrIgnore(
                    $products->map(fn ($product) => [
                        'product_id' => $product->id,
                        'personalization_template_id' => $product->personalization_template_id,
                        'sort_order' => 0,
                    ])->all()
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_personalization_template');
    }
};
