<?php

namespace App\Console\Commands;

use App\Services\FlowTrackBrandingPublisher;
use Illuminate\Console\Command;

final class PushFlowTrackBranding extends Command
{
    protected $signature = 'flowtrack:push-branding';
    protected $description = 'Send current NextPlay logo, title and design palette to the integrated operations portal';

    public function handle(FlowTrackBrandingPublisher $publisher): int
    {
        if ($publisher->publish()) {
            $this->info('NextPlay branding accepted by the operations portal.');
            return self::SUCCESS;
        }
        $this->error('Branding delivery failed or the integration is disabled. Check the integration URL and token.');
        return self::FAILURE;
    }
}
