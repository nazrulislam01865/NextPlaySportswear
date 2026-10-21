<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bulk_quote_requests', function (Blueprint $table): void {
            $table->unsignedBigInteger('flowtrack_source_inquiry_id')->nullable()->unique();
            $table->char('flowtrack_manifest_hash', 64)->nullable();
        });
        Schema::table('bulk_quote_files', function (Blueprint $table): void {
            $table->string('source_key', 100)->nullable();
            $table->unique(['bulk_quote_request_id', 'source_key'], 'bulk_quote_files_source_unique');
        });
    }
    public function down(): void
    {
        Schema::table('bulk_quote_files', function (Blueprint $table): void {
            $table->dropUnique('bulk_quote_files_source_unique');
            $table->dropColumn('source_key');
        });
        Schema::table('bulk_quote_requests', function (Blueprint $table): void {
            $table->dropUnique(['flowtrack_source_inquiry_id']);
            $table->dropColumn(['flowtrack_source_inquiry_id', 'flowtrack_manifest_hash']);
        });
    }
};
