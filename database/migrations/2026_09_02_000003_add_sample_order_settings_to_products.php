<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        $addSampleAvailable = ! Schema::hasColumn('products', 'sample_available');
        $addSampleCharge = ! Schema::hasColumn('products', 'sample_charge');

        if (! $addSampleAvailable && ! $addSampleCharge) {
            return;
        }

        Schema::table('products', function (Blueprint $table) use ($addSampleAvailable, $addSampleCharge): void {
            if ($addSampleAvailable) {
                $table->boolean('sample_available')->default(false)->after('jersey_roster_fields');
            }

            if ($addSampleCharge) {
                $table->decimal('sample_charge', 12, 2)->default(0)->after('sample_available');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        $columns = collect(['sample_available', 'sample_charge'])
            ->filter(fn (string $column): bool => Schema::hasColumn('products', $column))
            ->values()
            ->all();

        if ($columns !== []) {
            Schema::table('products', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }
};
