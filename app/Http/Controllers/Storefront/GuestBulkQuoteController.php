<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\BulkQuoteFile;
use App\Models\BulkQuoteRequest;
use App\Services\BulkQuote\BulkQuoteGuestService;
use App\Services\Email\TransactionalEmailManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GuestBulkQuoteController extends Controller
{
    public function __construct(private readonly BulkQuoteGuestService $guest, private readonly TransactionalEmailManager $emails) {}

    public function track(): View
    {
        return view('storefront.bulk-quote.guest.track', ['quote' => null, 'files' => collect(), 'messages' => collect()]);
    }

    public function lookup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:32'],
            'email' => ['required', 'email:rfc', 'max:190'],
        ]);
        $quote = BulkQuoteRequest::query()->where('reference', trim($data['reference']))
            ->where(function ($query): void {
                $query->whereNull('user_id');
                if ($customerId = Auth::guard('web')->id()) {
                    $query->orWhere('user_id', $customerId);
                }
            })->first();
        if (! $quote || ! hash_equals(strtolower($quote->email), strtolower(trim($data['email'])))) {
            throw ValidationException::withMessages(['reference' => 'The quote number and email do not match a guest quotation. Please check both details.']);
        }
        $request->session()->regenerate();
        $request->session()->put('guest_bulk_quotes.'.$quote->id, true);
        return redirect()->route('quote.guest.show', $quote->reference);
    }

    private function authorized(Request $request, string $reference): BulkQuoteRequest
    {
        $quote = BulkQuoteRequest::query()->where('reference', $reference)->firstOrFail();
        if ($quote->user_id !== null) {
            // Once attached to an account, only that customer can open the quote.
            // Old guest tracking tokens and sessions no longer grant access.
            $customer = Auth::guard('web')->user();
            abort_unless($customer?->isCustomer() && $customer->is_active && (int) $customer->id === (int) $quote->user_id, 404);
            return $quote;
        }
        $token = $request->query('token');
        if (is_string($token) && $this->guest->canAccess($quote, $token)) {
            if ($request->session()->get('guest_bulk_quotes.'.$quote->id) !== true) {
                $request->session()->regenerate();
            }
            $request->session()->put('guest_bulk_quotes.'.$quote->id, true);
        }
        abort_unless($request->session()->get('guest_bulk_quotes.'.$quote->id) === true, 404);
        return $quote;
    }

    private function forPost(Request $request, string $reference): BulkQuoteRequest
    {
        // POST requires an established session. Capability URLs cannot be used for state changes directly.
        return $this->authorized($request, $reference);
    }

    public function success(Request $request, string $reference): View
    {
        $quote = $this->authorized($request, $reference);
        $token = $request->query('token');
        return $this->render('success', $quote, ['copyUrl' => is_string($token) && $this->guest->canAccess($quote, $token)
            ? route('quote.guest.show', ['reference' => $reference, 'token' => $token]) : null]);
    }

    public function show(Request $request, string $reference): View
    {
        $quote = $this->authorized($request, $reference);
        return $this->render('track', $quote);
    }

    public function review(Request $request, string $reference): View
    {
        $quote = $this->authorized($request, $reference);
        abort_unless($quote->quotation_version > 0 && $quote->quotation_sent_at, 404);
        return $this->render('review', $quote);
    }

    public function changes(Request $request, string $reference): View
    {
        $quote = $this->authorized($request, $reference);
        abort_unless($quote->quotation_version > 0 && $quote->quotation_sent_at && $quote->guest_decision !== 'changes_requested', 404);
        return $this->render('changes', $quote);
    }

    private function render(string $view, BulkQuoteRequest $quote, array $extra = []): View
    {
        if ($view === 'success') {
            return view('storefront.bulk-quote.guest.success', array_merge(['quote' => $quote], $extra));
        }
        return view('storefront.bulk-quote.guest.'.$view, array_merge([
            'quote' => $quote,
            'files' => ! \App\Support\FlowTrackQuoteCustomerProgress::published($quote)
                && ! \App\Support\FlowTrackQuoteCustomerProgress::verifiedOffer($quote) ? collect() : (\App\Support\FlowTrackQuoteCustomerProgress::verifiedOffer($quote)
                ? $quote->quoteFiles()->where('category', 'flowtrack_offer')->oldest()->get()
                : $quote->quoteFiles()->where('category', 'quotation')->where('version', $quote->quotation_version)->oldest()->get()),
            'messages' => $quote->messages()->with('files')->latest('id')->limit(100)->get()->sortBy('id')->values(),
        ], $extra));
    }

    public function sendChanges(Request $request, string $reference): RedirectResponse
    {
        $quote = $this->forPost($request, $reference);
        abort_unless($quote->quotation_sent_at && $quote->quotation_version && $quote->guest_decision !== 'changes_requested', 404);
        $data = $request->validate([
            'change_types' => ['required', 'array', 'min:1', 'max:6'],
            'change_types.*' => [Rule::in(['pricing', 'delivery', 'products', 'customization', 'payment', 'other'])],
            'body' => ['required', 'string', 'min:5', 'max:1000'],
            'files' => ['nullable', 'array', 'max:4'],
            'files.*' => ['file', 'mimes:pdf,xlsx,xls,png,jpg,jpeg', 'max:10240'],
        ]);
        $this->storeMessage($quote, trim($data['body']), 'change_request', $data['change_types'], $request->file('files', []));
        $this->emails->bulkQuoteGuestFeedback($quote, 'Change request', trim($data['body']));
        return redirect()->route('quote.guest.show', $quote->reference)->with('status', 'Your change request has been sent to NextPlay.');
    }

    public function sendMessage(Request $request, string $reference): RedirectResponse
    {
        $quote = $this->forPost($request, $reference);
        abort_unless(($quote->quotation_sent_at || $quote->guest_decision === 'changes_requested') && $quote->quotation_version, 404);
        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:3000'],
            'files' => ['nullable', 'array', 'max:4'],
            'files.*' => ['file', 'mimes:pdf,xlsx,xls,png,jpg,jpeg', 'max:10240'],
        ]);
        $this->storeMessage($quote, trim($data['body']), 'message', [], $request->file('files', []));
        $this->emails->bulkQuoteGuestFeedback($quote, 'New message', trim($data['body']));
        return back()->with('status', 'Your message has been sent.');
    }

    private function storeMessage(BulkQuoteRequest $quote, string $body, string $kind, array $changeTypes, array $uploads): void
    {
        $this->guest->storeCustomerMessage($quote, $body, $kind, $changeTypes, $uploads);
    }

    public function decision(Request $request, string $reference): RedirectResponse
    {
        $quote = $this->forPost($request, $reference);
        $data = $request->validate([
            'decision' => ['required', Rule::in(['accept_only', 'create_order'])],
        ]);

        $customer = Auth::guard('web')->user();
        if ($data['decision'] === 'create_order' && ! $customer?->isCustomer()) {
            // Reuse the existing safe post-registration/login redirect handling.
            // The customer reviews the quote again after signing in, then places
            // the order deliberately (never create an order from a GET request).
            return redirect()->route('register', ['redirect' => route('quote.guest.review', $quote->reference)])
                ->with('status', 'Create a NextPlay customer account, or sign in with your quote email, to create an order.');
        }
        if ($data['decision'] === 'create_order' && config('security.email_verification.enabled', false) && ! $customer->hasVerifiedEmail()) {
            $request->session()->put('url.intended', route('quote.guest.review', $quote->reference));
            return redirect()->route('verification.notice')
                ->with('status', 'Verify your customer email before converting this quotation to an order.');
        }

        // Capture the previous decision so replayed POSTs do not send repeat
        // admin notifications for an order or acceptance already recorded.
        $wasAccepted = in_array($quote->status, [BulkQuoteRequest::STATUS_ACCEPTED, BulkQuoteRequest::STATUS_CONVERTED], true);
        $previousOrderId = $quote->converted_order_id;

        try {
            $quote = $this->guest->accept($quote, $data['decision'], $data['decision'] === 'create_order' ? $customer : null);
        } catch (ValidationException $exception) {
            // Invalid, expired or withdrawn quotes must retain the exact validation feedback.
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->route('quote.guest.review', $quote->reference)
                ->withErrors(['quote' => 'We could not complete your confirmation. Your request was not changed. Please try again or contact NextPlay Sales.']);
        }

        // Sending an internal notification must not undo a committed quote/order decision.
        // Idempotent browser retries should not send a second notification.
        if (! $wasAccepted || $previousOrderId !== $quote->converted_order_id) {
            try {
                $this->emails->bulkQuoteGuestFeedback(
                    $quote,
                    'Quote confirmed',
                    $data['decision'] === 'create_order'
                        ? 'The guest confirmed and created an order.'
                        : 'The guest confirmed the quotation without creating an order.'
                );
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        $order = $quote->converted_order_id ? $quote->convertedOrder : null;
        $message = $order
            ? 'Your quotation has been confirmed and order '.$order->order_number.' has been created. Our team will contact you about your invoice and payment.'
            : 'Your quotation has been confirmed. No order was created. You can create an order from this quotation later.';

        // Return to the review page so the customer sees the confirmed state immediately.
        return redirect()->route('quote.guest.review', $quote->reference)->with('status', $message);
    }

    public function file(Request $request, string $reference, BulkQuoteFile $file): StreamedResponse
    {
        $quote = $this->authorized($request, $reference);
        abort_unless($file->bulk_quote_request_id === $quote->id &&
            ($file->category === 'quotation' && $file->version === $quote->quotation_version && \App\Support\FlowTrackQuoteCustomerProgress::published($quote) ||
            $file->category === 'flowtrack_offer' && \App\Support\FlowTrackQuoteCustomerProgress::verifiedOffer($quote) ||
            $file->category === 'feedback' && $file->bulk_quote_message_id !== null), 404);
        return $this->download($file, $request->boolean('view'));
    }

    public static function download(BulkQuoteFile $file, bool $inline = false): StreamedResponse
    {
        $disk = Storage::disk('local');
        abort_unless($disk->exists($file->path), 404, 'This file is unavailable. Please contact NextPlay support.');
        $name = str_replace(["\r", "\n", '"', '\\'], '', basename($file->original_name));
        // Untrusted office documents should not be rendered inline by the browser.
        $isInline = $inline && in_array($file->mime_type, ['application/pdf', 'image/png', 'image/jpeg'], true);
        return $disk->response($file->path, $name, [
            'Content-Type' => $file->mime_type,
            'Content-Disposition' => ($isInline ? 'inline' : 'attachment').'; filename="'.$name.'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }
}
