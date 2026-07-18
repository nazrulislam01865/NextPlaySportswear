<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            if (! Schema::hasColumn('categories', 'icon_path')) {
                $table->text('icon_path')->nullable()->after('icon');
            }

            if (! Schema::hasColumn('categories', 'icon_url')) {
                $table->text('icon_url')->nullable()->after('icon_path');
            }

            if (! Schema::hasColumn('categories', 'icon_alt')) {
                $table->string('icon_alt', 255)->nullable()->after('icon_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            foreach (['icon_alt', 'icon_url', 'icon_path'] as $column) {
                if (Schema::hasColumn('categories', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
