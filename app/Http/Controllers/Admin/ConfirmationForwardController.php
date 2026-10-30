<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BulkQuoteRequest;
use App\Models\Order;
use App\Services\Email\ConfirmationForwarder;
use App\Services\Email\ForwardingSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

final class ConfirmationForwardController extends Controller
{
    public function edit(Request $request, ForwardingSettings $settings): View
    {
        abort_unless($request->user('admin')?->canAdmin('email_customization.manage'), 403);
        $mailerName = (string) config('transactional_email.mailer', config('mail.default', 'log'));
        $mailer = (array) config('mail.mailers.'.$mailerName, []);
        $key = (string) ($mailer['api_key'] ?? '');
        $providerWarning = ($mailer['transport'] ?? '') === 'e2a' && ($key === '' || str_ends_with($key, '>'))
            ? 'E2A API key is missing or appears malformed. Correct E2A_API_KEY in your local .env and run php artisan config:clear before sending.'
            : null;

        return view('admin.email-customization.forwarding', [
            'settings' => $settings->get(),
            'providerWarning' => $providerWarning,
        ]);
    }

    public function update(Request $request, ForwardingSettings $settings): RedirectResponse
    {
        abort_unless($request->user('admin')?->canAdmin('email_customization.manage'), 403);
        $data = $request->validate([
            'recipients' => ['nullable', 'string', 'max:1300'],
            'message' => ['required', 'string', 'max:1000'],
        ]);
        try {
            if (filled($data['recipients'] ?? null)) {
                $settings->recipients($data['recipients']);
            }
            $settings->save(trim($data['recipients'] ?? ''), trim($data['message']));
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['recipients' => $exception->getMessage()]);
        }
        return back()->with('status', filled($data['recipients'] ?? null)
            ? 'Saved. New orders and bulk quote requests will automatically be forwarded to these recipients. Existing requests can still be forwarded manually.'
            : 'Saved. Automatic forwarding is disabled because no recipient addresses are set.');
    }

    public function order(Request $request, Order $order, ConfirmationForwarder $forwarder): RedirectResponse
    {
        abort_unless($request->user('admin')?->canAdmin('orders.manage'), 403);
        return $this->forward($request, fn (string $to, string $note) => $forwarder->order($order, $to, $note));
    }

    public function quote(Request $request, BulkQuoteRequest $bulkQuote, ConfirmationForwarder $forwarder): RedirectResponse
    {
        abort_unless($request->user('admin')?->canAdmin('orders.manage'), 403);
        return $this->forward($request, fn (string $to, string $note) => $forwarder->quote($bulkQuote, $to, $note));
    }

    private function forward(Request $request, callable $send): RedirectResponse
    {
        $validated = $request->validate([
            'recipients' => ['required', 'string', 'max:1300'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);
        try {
            $send(trim($validated['recipients']), trim($validated['message'] ?? ''));
        } catch (Throwable $exception) {
            report($exception);
            return back()->withInput()->withErrors(['forward' => 'Forwarding failed: '.$exception->getMessage()]);
        }
        return back()->with('status', 'Confirmation email accepted by the mail provider with request details and attachments.');
    }
}
