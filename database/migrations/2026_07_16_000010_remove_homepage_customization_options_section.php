<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('homepage_sections')) {
            return;
        }

        DB::table('homepage_sections')
            ->where('key', 'customization_options')
            ->delete();
    }

    public function down(): void
    {
        // This section was intentionally removed from homepage backend management.
    }
};
