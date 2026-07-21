<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_view_sessions') || Schema::hasColumn('product_view_sessions', 'viewed_detail_at')) {
            return;
        }

        Schema::table('product_view_sessions', function (Blueprint $table): void {
            $table->timestamp('viewed_detail_at')->nullable()->index()->after('last_seen_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_view_sessions') || ! Schema::hasColumn('product_view_sessions', 'viewed_detail_at')) {
            return;
        }

        Schema::table('product_view_sessions', function (Blueprint $table): void {
            $table->dropIndex(['viewed_detail_at']);
            $table->dropColumn('viewed_detail_at');
        });
    }
};
