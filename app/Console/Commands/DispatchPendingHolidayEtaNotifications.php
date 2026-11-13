<?php

namespace App\Console\Commands;

use App\Jobs\Shipping\SendHolidayEtaNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** Re-dispatch persisted emails if a worker crashed between commit and queue publish. */
final class DispatchPendingHolidayEtaNotifications extends Command
{
    protected $signature = 'holidays:dispatch-notifications {--limit=200}';
    protected $description = 'Retry pending holiday ETA notification outbox entries';

    public function handle(): int
    {
        $limit = max(1, min(500, (int) $this->option('limit')));
        $keys = DB::table('holiday_eta_notifications')->whereNull('queued_at')
            ->where(function ($query): void {
                $query->whereNull('processing_at')->orWhere('processing_at', '<', now()->subMinutes(10));
            })->where(function ($query): void {
                $query->whereNull('last_dispatched_at')->orWhere('last_dispatched_at', '<', now()->subMinutes(10));
            })->orderBy('id')->limit($limit)->pluck('event_key');
        foreach ($keys as $key) {
            DB::table('holiday_eta_notifications')->where('event_key', $key)
                ->update(['last_dispatched_at' => now(), 'updated_at' => now()]);
            SendHolidayEtaNotification::dispatch($key)
                ->onQueue(config('nextplay_shipping.holiday_queue', 'default'));
        }
        $this->info('Dispatched '.$keys->count().' pending ETA notification(s).');
        return self::SUCCESS;
    }
}
