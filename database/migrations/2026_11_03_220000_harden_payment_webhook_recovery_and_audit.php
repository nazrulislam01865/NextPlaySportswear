<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL DDL is not transactionally rolled back. If an earlier run
        // stopped while creating the audit table, preserve the columns and
        // indexes already installed rather than trying to add them again.
        $addWebhookAttempts = ! Schema::hasColumn('payment_webhook_events', 'attempt_count');
        $addLastDispatched = ! Schema::hasColumn('payment_webhook_events', 'last_dispatched_at');
        $addLastAttempted = ! Schema::hasColumn('payment_webhook_events', 'last_attempted_at');
        $addWebhookIndex = ! Schema::hasIndex('payment_webhook_events', 'payment_webhook_recovery_idx');

        if ($addWebhookAttempts || $addLastDispatched || $addLastAttempted || $addWebhookIndex) {
            Schema::table('payment_webhook_events', function (Blueprint $table) use (
                $addWebhookAttempts, $addLastDispatched, $addLastAttempted, $addWebhookIndex
            ): void {
                if ($addWebhookAttempts) {
                    $table->unsignedInteger('attempt_count')->default(0);
                }
                if ($addLastDispatched) {
                    $table->timestamp('last_dispatched_at')->nullable();
                }
                if ($addLastAttempted) {
                    $table->timestamp('last_attempted_at')->nullable();
                }
                if ($addWebhookIndex) {
                    $table->index(['status', 'last_dispatched_at'], 'payment_webhook_recovery_idx');
                }
            });
        }

        $addLastReconciled = ! Schema::hasColumn('order_payments', 'last_reconciled_at');
        $addReconciliationFailures = ! Schema::hasColumn('order_payments', 'reconciliation_failures');
        $addPaymentIndex = ! Schema::hasIndex('order_payments', 'order_payment_reconciliation_due_idx');

        if ($addLastReconciled || $addReconciliationFailures || $addPaymentIndex) {
            Schema::table('order_payments', function (Blueprint $table) use (
                $addLastReconciled, $addReconciliationFailures, $addPaymentIndex
            ): void {
                if ($addLastReconciled) {
                    $table->timestamp('last_reconciled_at')->nullable();
                }
                if ($addReconciliationFailures) {
                    $table->unsignedInteger('reconciliation_failures')->default(0);
                }
                if ($addPaymentIndex) {
                    $table->index(['status', 'last_reconciled_at'], 'order_payment_reconciliation_due_idx');
                }
            });
        }

        if (! Schema::hasTable('payment_audit_entries')) {
            Schema::create('payment_audit_entries', function (Blueprint $table): void {
                $table->id();
                $table->string('event_key', 190)->unique();
                $table->string('event_type', 60);
                $table->string('source', 40);
                $table->boolean('livemode')->nullable();
                $table->foreignId('order_id')->nullable()->index();
                $table->foreignId('order_payment_id')->nullable()->index();
                $table->foreignId('order_refund_id')->nullable()->index();
                $table->string('provider_reference', 190)->nullable();
                $table->string('old_status', 40)->nullable();
                $table->string('new_status', 40)->nullable();
                $table->unsignedBigInteger('amount_minor')->nullable();
                $table->char('currency', 3)->nullable();
                // DATETIME avoids MySQL/MariaDB's implicit TIMESTAMP default
                // rules while requiring the verified transition to supply both
                // timestamps (as PaymentAuditRecorder already does).
                $table->dateTime('occurred_at');
                $table->dateTime('created_at');
                $table->index(['event_type', 'occurred_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_audit_entries');
        Schema::table('order_payments', function (Blueprint $table): void {
            $table->dropIndex('order_payment_reconciliation_due_idx');
            $table->dropColumn(['last_reconciled_at', 'reconciliation_failures']);
        });
        Schema::table('payment_webhook_events', function (Blueprint $table): void {
            $table->dropIndex('payment_webhook_recovery_idx');
            $table->dropColumn(['attempt_count', 'last_dispatched_at', 'last_attempted_at']);
        });
    }
};
