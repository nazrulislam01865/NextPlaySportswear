<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bulk_quote_requests', function (Blueprint $table): void {
            $table->foreignId('user_id')
                ->nullable()
                ->after('reference')
                ->constrained('users')
                ->nullOnDelete();
            $table->json('customer_account')->nullable()->after('phone');

            $table->string('flowtrack_sync_status', 30)->default('pending')->index()->after('status');
            $table->unsignedInteger('flowtrack_sync_attempts')->default(0)->after('flowtrack_sync_status');
            $table->unsignedBigInteger('flowtrack_inquiry_id')->nullable()->index()->after('flowtrack_sync_attempts');
            $table->string('flowtrack_inquiry_number', 80)->nullable()->index()->after('flowtrack_inquiry_id');
            $table->timestamp('flowtrack_last_attempt_at')->nullable()->after('flowtrack_inquiry_number');
            $table->timestamp('flowtrack_synced_at')->nullable()->after('flowtrack_last_attempt_at');
            $table->text('flowtrack_sync_error')->nullable()->after('flowtrack_synced_at');
            $table->json('flowtrack_response')->nullable()->after('flowtrack_sync_error');
        });
    }

    public function down(): void
    {
        Schema::table('bulk_quote_requests', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropColumn([
                'user_id',
                'customer_account',
                'flowtrack_sync_status',
                'flowtrack_sync_attempts',
                'flowtrack_inquiry_id',
                'flowtrack_inquiry_number',
                'flowtrack_last_attempt_at',
                'flowtrack_synced_at',
                'flowtrack_sync_error',
                'flowtrack_response',
            ]);
        });
    }
};
