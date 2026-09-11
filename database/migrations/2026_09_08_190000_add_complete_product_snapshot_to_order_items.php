<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_items')) {
            return;
        }

        Schema::table('order_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('order_items', 'product_snapshot_schema_version')) {
                $table->unsignedSmallInteger('product_snapshot_schema_version')->nullable()->after('customization');
            }
            if (! Schema::hasColumn('order_items', 'product_snapshot')) {
                $table->json('product_snapshot')->nullable()->after('product_snapshot_schema_version');
            }
            if (! Schema::hasColumn('order_items', 'resolved_customization')) {
                $table->json('resolved_customization')->nullable()->after('product_snapshot');
            }
            if (! Schema::hasColumn('order_items', 'product_snapshot_captured_at')) {
                $table->timestamp('product_snapshot_captured_at')->nullable()->after('resolved_customization');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('order_items')) {
            return;
        }

        $columns = collect([
            'product_snapshot_schema_version',
            'product_snapshot',
            'resolved_customization',
            'product_snapshot_captured_at',
        ])->filter(fn (string $column): bool => Schema::hasColumn('order_items', $column))->all();

        if ($columns !== []) {
            Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }
};
