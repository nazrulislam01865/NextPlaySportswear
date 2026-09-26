<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'badge_label')) {
            return;
        }

        DB::table('products')
            ->whereRaw('LOWER(TRIM(badge_label)) = ?', ['customizable'])
            ->update(['badge_label' => null]);
    }

    public function down(): void
    {
        // Intentionally irreversible: the previous value cannot be distinguished
        // from a manually entered badge after it has been cleared.
    }
};
