<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('categories')
            ->whereNotNull('parent_id')
            ->where('is_featured', true)
            ->update([
                'is_featured' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Intentionally left blank. Restoring child featured flags would reintroduce
        // homepage selection states that are no longer supported.
    }
};
