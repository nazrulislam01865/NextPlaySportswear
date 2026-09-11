<?php

namespace App\Console\Commands;

use App\Services\Integrations\FlowTrack\FlowTrackClient;
use Illuminate\Console\Command;
use Throwable;

final class FlowTrackHealth extends Command
{
    protected $signature = 'flowtrack:health';

    protected $description = 'Check whether the configured FlowTrack order receiver is reachable and ready.';

    public function handle(FlowTrackClient $client): int
    {
        try {
            $response = $client->health();
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info((string) ($response['message'] ?? 'FlowTrack is ready.'));
        $this->line('Database ready: '.((bool) ($response['database_ready'] ?? false) ? 'yes' : 'no'));
        $this->line('Workflow ready: '.((bool) ($response['workflow_ready'] ?? false) ? 'yes' : 'no'));
        $this->line('Complete product capture: '.((bool) ($response['complete_product_capture_ready'] ?? false) ? 'yes' : 'no'));
        $this->line('Artwork transfer ready: '.((bool) ($response['artwork_transfer_ready'] ?? false) ? 'yes' : 'no'));
        $this->line('Receiver user ID: '.(string) ($response['receiver_user_id'] ?? 'n/a'));

        return self::SUCCESS;
    }
}
