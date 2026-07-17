<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('homepage_sections')) {
            DB::table('homepage_sections')->where('key', 'support')->delete();
        }
    }

    public function down(): void
    {
        // The support CTA section was intentionally retired.
    }
};
