<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** Server-shell-only, read-only audit lookup. No admin API or mutation action. */
final class InspectPaymentAudit extends Command
{
    protected $signature = 'payments:audit {--order= : Internal numeric order ID} {--limit=50}';

    protected $description = 'Inspect the append-only financial transition timeline without sensitive payment data.';

    public function handle(): int
    {
        $limit = max(1, min(200, (int) $this->option('limit')));
        $order = trim((string) $this->option('order'));
        if ($order !== '' && (! ctype_digit($order) || (int) $order < 1)) {
            $this->error('Order must be a positive internal numeric ID.');
            return self::INVALID;
        }

        $query = DB::table('payment_audit_entries')
            ->select(['id', 'event_type', 'order_id', 'order_payment_id', 'order_refund_id',
                'old_status', 'new_status', 'amount_minor', 'currency', 'livemode', 'occurred_at'])
            ->orderByDesc('id')->limit($limit);
        if ($order !== '') {
            $query->where('order_id', (int) $order);
        }
        $records = $query->get();
        $this->table(['ID', 'Event', 'Order', 'Payment', 'Refund', 'Before', 'After', 'Minor units', 'Currency', 'Mode', 'UTC'],
            $records->map(static fn ($record) => [
                $record->id, $record->event_type, $record->order_id,
                $record->order_payment_id, $record->order_refund_id,
                $record->old_status, $record->new_status, $record->amount_minor,
                $record->currency, $record->livemode === null ? '-' : ($record->livemode ? 'live' : 'test'),
                $record->occurred_at,
            ])->all());
        return self::SUCCESS;
    }
}
