<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('flowtrack_sync_status', 30)->default('pending')->index()->after('completed_at');
            $table->unsignedInteger('flowtrack_sync_attempts')->default(0)->after('flowtrack_sync_status');
            $table->unsignedBigInteger('flowtrack_order_id')->nullable()->index()->after('flowtrack_sync_attempts');
            $table->string('flowtrack_order_number', 80)->nullable()->index()->after('flowtrack_order_id');
            $table->string('flowtrack_job_id', 120)->nullable()->index()->after('flowtrack_order_number');
            $table->timestamp('flowtrack_last_attempt_at')->nullable()->after('flowtrack_job_id');
            $table->timestamp('flowtrack_synced_at')->nullable()->after('flowtrack_last_attempt_at');
            $table->text('flowtrack_sync_error')->nullable()->after('flowtrack_synced_at');
            $table->json('flowtrack_response')->nullable()->after('flowtrack_sync_error');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'flowtrack_sync_status',
                'flowtrack_sync_attempts',
                'flowtrack_order_id',
                'flowtrack_order_number',
                'flowtrack_job_id',
                'flowtrack_last_attempt_at',
                'flowtrack_synced_at',
                'flowtrack_sync_error',
                'flowtrack_response',
            ]);
        });
    }
};
