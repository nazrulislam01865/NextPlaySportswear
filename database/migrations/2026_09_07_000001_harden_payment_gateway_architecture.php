<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_payments', function (Blueprint $table): void {
            if (! Schema::hasColumn('order_payments', 'payment_method_id')) {
                $table->foreignId('payment_method_id')->nullable()->after('order_id')->constrained('payment_methods')->nullOnDelete();
            }
            if (! Schema::hasColumn('order_payments', 'gateway')) {
                $table->string('gateway', 80)->nullable()->after('payment_method_id')->index();
            }
            if (! Schema::hasColumn('order_payments', 'idempotency_key')) {
                $table->string('idempotency_key', 190)->nullable()->after('gateway')->unique();
            }
            if (! Schema::hasColumn('order_payments', 'provider_session_id')) {
                $table->string('provider_session_id', 255)->nullable()->after('provider_reference')->index();
            }
            if (! Schema::hasColumn('order_payments', 'provider_payment_id')) {
                $table->string('provider_payment_id', 255)->nullable()->after('provider_session_id')->index();
            }
            if (! Schema::hasColumn('order_payments', 'provider_customer_id')) {
                $table->string('provider_customer_id', 255)->nullable()->after('provider_payment_id')->index();
            }
            if (! Schema::hasColumn('order_payments', 'authorized_at')) {
                $table->timestamp('authorized_at')->nullable()->after('attempted_at');
            }
            if (! Schema::hasColumn('order_payments', 'failed_at')) {
                $table->timestamp('failed_at')->nullable()->after('paid_at');
            }
            if (! Schema::hasColumn('order_payments', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('failed_at');
            }
            if (! Schema::hasColumn('order_payments', 'refunded_amount')) {
                $table->decimal('refunded_amount', 12, 2)->default(0)->after('expires_at');
            }
        });

        if (! Schema::hasTable('payment_webhook_events')) {
            Schema::create('payment_webhook_events', function (Blueprint $table): void {
                $table->id();
                $table->string('provider', 80);
                $table->string('provider_event_id', 255);
                $table->string('event_type', 190);
                $table->foreignId('order_payment_id')->nullable()->constrained('order_payments')->nullOnDelete();
                $table->char('payload_hash', 64);
                $table->longText('payload');
                $table->string('status', 30)->default('received');
                $table->timestamp('received_at');
                $table->timestamp('processed_at')->nullable();
                $table->text('last_error')->nullable();
                $table->timestamps();

                $table->unique(['provider', 'provider_event_id']);
                $table->index(['provider', 'status', 'received_at']);
            });
        }

        if (! Schema::hasTable('customer_gateway_profiles')) {
            Schema::create('customer_gateway_profiles', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('provider', 80);
                $table->string('provider_customer_id', 255);
                $table->text('metadata')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'provider']);
                $table->unique(['provider', 'provider_customer_id']);
            });
        }

        // Only expose providers that are implemented. PayPal remains a future
        // adapter and saved cards stay disabled until provider vaulting is used.
        if (Schema::hasTable('payment_methods')) {
            DB::table('payment_methods')->where('provider', 'paypal')->update(['is_active' => false]);
            DB::table('payment_methods')->where('provider', 'stripe')->update([
                'allows_saved_methods' => false,
                'instructions' => 'You will be redirected to Stripe Checkout. Card number and CVV never pass through or get stored by this application.',
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_gateway_profiles');
        Schema::dropIfExists('payment_webhook_events');

        Schema::table('order_payments', function (Blueprint $table): void {
            $columns = [
                'payment_method_id', 'gateway', 'idempotency_key', 'provider_session_id',
                'provider_payment_id', 'provider_customer_id', 'authorized_at', 'failed_at',
                'expires_at', 'refunded_amount',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('order_payments', $column)) {
                    if ($column === 'payment_method_id') {
                        $table->dropConstrainedForeignId($column);
                    } else {
                        $table->dropColumn($column);
                    }
                }
            }
        });
    }
};
