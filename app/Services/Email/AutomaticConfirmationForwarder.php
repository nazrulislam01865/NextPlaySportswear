<?php

namespace App\Services\Email;

use App\Models\BulkQuoteRequest;
use App\Models\ContactMessage;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Automatically forwards newly submitted requests using the existing private settings. */
final class AutomaticConfirmationForwarder
{
    public function __construct(private readonly ForwardingSettings $settings) {}

    public function orderCreated(Order $order): void
    {
        $this->schedule($order);
    }

    public function quoteCreated(BulkQuoteRequest $quote): void
    {
        $this->schedule($quote);
    }

    public function contactCreated(ContactMessage $contact): void
    {
        $this->schedule($contact);
    }

    private function schedule(Order|BulkQuoteRequest|ContactMessage $record): void
    {
        $type = match (true) {
            $record instanceof Order => 'order',
            $record instanceof BulkQuoteRequest => 'quote',
            default => 'contact',
        };
        $id = $record->getKey();
        try {
            $defaults = $this->settings->get();
            $recipients = trim((string) ($defaults['recipients'] ?? ''));
            if ($recipients === '') {
                return; // No forwarding addresses configured: forwarding is disabled.
            }

            $note = trim((string) ($defaults['message'] ?? ''));
            $send = static function () use ($type, $id, $record, $recipients, $note): void {
                try {
                    $forwarder = app(ConfirmationForwarder::class);
                    if ($type === 'order') {
                        $forwarder->order($record, $recipients, $note, automatic: true);
                    } elseif ($type === 'quote') {
                        $forwarder->quote($record, $recipients, $note, automatic: true);
                    } else {
                        $forwarder->contact($record, $recipients, $note, automatic: true);
                    }
                } catch (Throwable $exception) {
                    Log::error('Automatic confirmation forwarding failed.', [
                        'type' => $type,
                        'record_id' => $id,
                        'exception' => $exception::class,
                        'error' => $exception->getMessage(),
                    ]);
                    report($exception);
                }
            };

            // Send after the response, without creating a queue job or relying on
            // a worker. Artisan/CLI calls have no HTTP response to terminate.
            if (app()->runningInConsole()) {
                $send();
            } else {
                app()->terminating($send);
            }
        } catch (Throwable $exception) {
            // The submission has already been saved; email must not undo it.
            Log::error('Automatic confirmation forwarding could not be scheduled.', [
                'type' => $type,
                'record_id' => $id,
                'exception' => $exception::class,
                'error' => $exception->getMessage(),
            ]);
            report($exception);
        }
    }
}
