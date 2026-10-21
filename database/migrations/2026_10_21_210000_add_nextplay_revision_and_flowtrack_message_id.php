<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bulk_quote_requests', function (Blueprint $table): void {
            $table->unsignedBigInteger('last_change_request_id')->default(0);
        });
        Schema::table('bulk_quote_messages', function (Blueprint $table): void {
            $table->unsignedBigInteger('flowtrack_message_id')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('bulk_quote_messages', fn (Blueprint $table) => $table->dropColumn('flowtrack_message_id'));
        Schema::table('bulk_quote_requests', fn (Blueprint $table) => $table->dropColumn('last_change_request_id'));
    }
};
