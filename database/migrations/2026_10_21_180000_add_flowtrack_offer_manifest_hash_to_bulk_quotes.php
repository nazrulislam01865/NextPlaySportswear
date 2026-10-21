<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bulk_quote_requests', function (Blueprint $table): void {
            $table->char('flowtrack_offer_manifest_hash', 64)->nullable();
            $table->char('flowtrack_offer_ready_hash', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bulk_quote_requests', function (Blueprint $table): void {
            $table->dropColumn(['flowtrack_offer_manifest_hash', 'flowtrack_offer_ready_hash']);
        });
    }
};
