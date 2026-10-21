<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bulk_quote_requests', function (Blueprint $table): void {
            $table->json('flowtrack_task_progress')->nullable();
            $table->unsignedTinyInteger('flowtrack_offer_file_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('bulk_quote_requests', function (Blueprint $table): void {
            $table->dropColumn(['flowtrack_task_progress', 'flowtrack_offer_file_count']);
        });
    }
};
