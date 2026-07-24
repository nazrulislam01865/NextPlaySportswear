<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'last_update_summary')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->text('last_update_summary')->nullable()->after('updated_by');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'last_update_summary')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->dropColumn('last_update_summary');
            });
        }
    }
};
