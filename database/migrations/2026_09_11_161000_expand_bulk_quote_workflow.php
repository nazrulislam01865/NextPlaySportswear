<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bulk_quote_requests', function (Blueprint $table): void {
            $table->foreignId('assigned_to')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->string('priority', 20)->default('normal')->index()->after('assigned_to');
            $table->decimal('quoted_amount', 12, 2)->nullable()->after('priority');
            $table->char('quote_currency', 3)->default('USD')->after('quoted_amount');
            $table->text('admin_note')->nullable()->after('quote_currency');
            $table->timestamp('last_contacted_at')->nullable()->after('admin_note');
            $table->timestamp('closed_at')->nullable()->after('last_contacted_at');
            $table->foreignId('closed_by')->nullable()->after('closed_at')->constrained('users')->nullOnDelete();

            $table->index(['status', 'priority', 'assigned_to'], 'bulk_quote_workflow_idx');
        });

        Schema::create('bulk_quote_activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bulk_quote_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40);
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['bulk_quote_request_id', 'occurred_at'], 'bulk_quote_activity_timeline_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_quote_activities');

        Schema::table('bulk_quote_requests', function (Blueprint $table): void {
            $table->dropIndex('bulk_quote_workflow_idx');
            $table->dropForeign(['assigned_to']);
            $table->dropForeign(['closed_by']);
            $table->dropColumn([
                'assigned_to',
                'priority',
                'quoted_amount',
                'quote_currency',
                'admin_note',
                'last_contacted_at',
                'closed_at',
                'closed_by',
            ]);
        });
    }
};
