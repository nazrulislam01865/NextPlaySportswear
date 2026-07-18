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
            ->where('key', 'shop_by_sport')
            ->update(['sort_order' => 25]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('homepage_sections')) {
            return;
        }

        DB::table('homepage_sections')
            ->where('key', 'shop_by_sport')
            ->update(['sort_order' => 110]);
    }
};
