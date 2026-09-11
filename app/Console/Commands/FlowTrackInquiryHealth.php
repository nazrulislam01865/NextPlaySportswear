<?php

namespace App\Console\Commands;

use App\Services\Integrations\FlowTrack\FlowTrackClient;
use Illuminate\Console\Command;
use Throwable;

final class FlowTrackInquiryHealth extends Command
{
    protected $signature = 'flowtrack:inquiry-health';

    protected $description = 'Check whether FlowTrack is ready to receive NextPlay bulk quotes as Inquiries.';

    public function handle(FlowTrackClient $client): int
    {
        try {
            $response = $client->inquiryHealth();
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info((string) ($response['message'] ?? 'FlowTrack Inquiry receiver is ready.'));
        $this->line('Database ready: '.((bool) ($response['database_ready'] ?? false) ? 'yes' : 'no'));
        $this->line('Workflow ready: '.((bool) ($response['workflow_ready'] ?? false) ? 'yes' : 'no'));
        $this->line('Attachment transfer ready: '.((bool) ($response['attachment_transfer_ready'] ?? false) ? 'yes' : 'no'));
        $this->line('Receiver user ID: '.(string) ($response['receiver_user_id'] ?? 'n/a'));

        return self::SUCCESS;
    }
}
