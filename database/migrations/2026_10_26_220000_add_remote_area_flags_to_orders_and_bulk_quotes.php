<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // NULL means a legacy record has not been classified yet; false means checked.
        Schema::table('orders', function (Blueprint $table): void {
            $table->boolean('is_remote_area')->nullable();
        });
        Schema::table('bulk_quote_requests', function (Blueprint $table): void {
            $table->boolean('is_remote_area')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bulk_quote_requests', function (Blueprint $table): void {
            $table->dropColumn('is_remote_area');
        });
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('is_remote_area');
        });
    }
};
