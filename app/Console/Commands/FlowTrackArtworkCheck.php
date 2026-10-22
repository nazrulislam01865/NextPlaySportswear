<?php

namespace App\Console\Commands;

use App\Models\ArtworkProof;
use App\Models\Order;
use App\Services\Artwork\ArtworkProofService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/** Read-only diagnostic for an order; does not create or alter any proof. */
final class FlowTrackArtworkCheck extends Command
{
    protected $signature = 'flowtrack:artwork-check {order : NextPlay order number or database ID}';

    protected $description = 'Check whether FlowTracker proofs exist and are visible to the NextPlay customer.';

    public function handle(ArtworkProofService $proofs): int
    {
        $value = trim((string) $this->argument('order'));
        $order = Order::query()->where('order_number', $value)
            ->when(ctype_digit($value), fn ($q) => $q->orWhereKey((int) $value))->first();
        if (! $order) {
            $this->error('NPS order not found: '.$value);
            return self::FAILURE;
        }
        if (! Schema::hasColumn('artwork_proofs', 'flowtrack_job_id')) {
            $this->error('Missing artwork migration. Run php artisan migrate in NextPlay, then retry.');
            return self::FAILURE;
        }
        $total = ArtworkProof::query()->where('order_id', $order->id)->count();
        $flowtrack = ArtworkProof::query()->where('order_id', $order->id)->whereNotNull('flowtrack_job_id')->count();
        $visible = $order->user_id ? $proofs->currentForCustomer((int) $order->user_id)
            ->where('artwork_proofs.order_id', $order->id)->count() : 0;
        $this->table(['Check', 'Value'], [
            ['Order number', $order->order_number],
            ['NPS order ID', $order->id],
            ['Customer user ID', $order->user_id ?: 'MISSING'],
            ['Linked FlowTracker job', $order->flowtrack_job_id ?: 'MISSING'],
            ['Total proof records', $total],
            ['Proofs received from FlowTracker', $flowtrack],
            ['Proofs visible in customer list', $visible],
        ]);
        $rows = [];
        foreach (ArtworkProof::query()->where('order_id', $order->id)->orderBy('id')->get() as $proof) {
            $rows[] = [
                $proof->id, $proof->order_item_id, $proof->flowtrack_document_id ?: '-',
                $proof->flowtrack_slot ?: '-', $proof->version, $proof->status,
                Storage::disk('local')->exists((string) $proof->file_path) ? 'yes' : 'MISSING',
            ];
        }
        if ($rows) $this->table(['Proof', 'Order item', 'FlowTrack file', 'Slot', 'Version', 'Status', 'Private file exists'], $rows);
        if ($flowtrack === 0) $this->warn('No FlowTracker proof has reached this NPS order. Check the FlowTracker delivery command/output.');
        elseif ($visible === 0) $this->warn('Proof records exist but are hidden from this customer. Verify the current proof scope and order ownership.');
        else $this->info('Artwork is present in the customer-visible query. Sign in as the customer user shown above and refresh Artwork Approvals.');
        return self::SUCCESS;
    }
}
