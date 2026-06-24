<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('jersey_customization_options')
            && ! Schema::hasColumn('jersey_customization_options', 'description')
        ) {
            Schema::table('jersey_customization_options', function (Blueprint $table): void {
                $table->text('description')->nullable()->after('color_hex');
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('jersey_customization_options')
            && Schema::hasColumn('jersey_customization_options', 'description')
        ) {
            Schema::table('jersey_customization_options', function (Blueprint $table): void {
                $table->dropColumn('description');
            });
        }
    }
};
