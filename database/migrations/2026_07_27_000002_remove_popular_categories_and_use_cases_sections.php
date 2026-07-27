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
            ->whereIn('key', ['popular_categories', 'use_cases'])
            ->delete();
    }

    public function down(): void
    {
        // These homepage sections were intentionally retired from both the
        // storefront and backend management.
    }
};
