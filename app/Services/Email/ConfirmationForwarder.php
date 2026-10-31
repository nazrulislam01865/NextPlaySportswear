<?php

namespace App\Services\Email;

use App\Contracts\EmailService;
use App\Data\EmailMessage;
use App\Models\BulkQuoteRequest;
use App\Models\Order;
use App\Services\Order\OrderPdfService;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/** Forward existing orders and bulk quotes, manually or on creation. */
final class ConfirmationForwarder
{
    private const MAX_BYTES = 14 * 1024 * 1024;
    private const MAX_FILES = 30;
    // E2A has a 10 MiB composed-message ceiling and a 10-attachment limit.
    // Keep room for the subject and both HTML/text versions of the details.
    private const E2A_MAX_BYTES = 9 * 1024 * 1024;
    private const E2A_MAX_FILES = 10;

    public function __construct(
        private readonly EmailService $emails,
        private readonly ForwardingSettings $settings,
        private readonly OrderPdfService $pdf,
    ) {}

    public function order(Order $order, string $recipients, string $note, bool $automatic = false): void
    {
        $order->loadMissing(['items', 'artworkProofs', 'downloads']);
        $shipping = (array) data_get($order->shipping_address, 'address', $order->shipping_address ?? []);
        $billing = (array) data_get($order->billing_address, 'address', $order->billing_address ?? []);
        $details = [
            'Order number' => (string) $order->order_number,
            'Customer name' => (string) $order->customer_name,
            'Customer email' => (string) $order->customer_email,
            'Customer phone' => (string) $order->customer_phone,
            'Placed' => (string) ($order->placed_at?->format('d M Y, g:i A') ?: ''),
            'Status' => (string) $order->statusLabel(),
            'Payment status' => (string) $order->paymentStatusLabel(),
            'Shipping address' => $this->asText($shipping),
            'Billing address' => $this->asText($billing),
            'Shipping method' => $this->asText((array) $order->shipping_method),
            'Payment method' => $this->asText((array) $order->payment_method),
            'Order information' => $this->asText((array) $order->information),
        ];
        foreach ($order->items as $index => $item) {
            $details['Product '.($index + 1)] = $item->product_name.' | SKU: '.$item->sku.' | Qty: '.$item->quantity
                .' | Unit: '.$item->unit_price.' | Total: '.$item->line_total;
            $details['Product '.($index + 1).' customization'] = $this->asText((array) $item->customization);
        }
        $details['Subtotal'] = (string) $order->subtotal.' '.$order->currency;
        $details['Customization total'] = (string) $order->customization_total.' '.$order->currency;
        $details['Discount'] = (string) $order->discount_total.' '.$order->currency;
        $details['Shipping'] = (string) $order->shipping_total.' '.$order->currency;
        $details['Tax'] = (string) $order->tax_total.' '.$order->currency;
        $details['Grand total'] = (string) $order->grand_total.' '.$order->currency;
        $details['Customer note'] = (string) $order->customer_note;

        $files = [
            $this->dataFile('Order_'.$order->order_number.'_Details.txt', $this->summary($details), 'text/plain'),
            $this->dataFile('Order_'.$order->order_number.'_Invoice.pdf', $this->pdf->invoice($order), 'application/pdf'),
        ];
        foreach ($order->items as $index => $item) {
            foreach ($item->artworkFiles() as $file) {
                $this->addStored($files, 'local', (string) $file['path'], (string) ($file['original_name'] ?? 'artwork'), (string) ($file['mime_type'] ?? ''), 'item'.($index+1));
            }
            foreach ((array) data_get($item->customization, 'personalization_files', []) as $file) {
                if (is_array($file) && filled($file['path'] ?? null)) {
                    $this->addStored($files, 'local', (string) $file['path'], (string) ($file['original_name'] ?? 'personalization'), (string) ($file['mime_type'] ?? ''), 'personalization'.($index+1));
                }
            }
        }
        foreach ($order->artworkProofs as $proof) {
            if (filled($proof->file_path)) {
                $this->addStored($files, 'local', (string) $proof->file_path, (string) ($proof->original_name ?: 'artwork-proof'), (string) $proof->mime_type, 'proof');
            }
            if (filled($proof->reference_path)) {
                $this->addStored($files, 'local', (string) $proof->reference_path, (string) ($proof->reference_name ?: 'revision-reference'), (string) $proof->reference_mime_type, 'reference');
            }
        }
        foreach ($order->downloads as $download) {
            if (filled($download->file_path)) {
                $this->addStored($files, 'local', (string) $download->file_path, (string) ($download->original_name ?: 'order-document'), (string) $download->mime_type, 'download');
            }
        }
        $this->deliver($recipients, $note, 'Order '.$order->order_number, $details, route('admin.orders.show', $order), $files, $automatic);
    }

    public function quote(BulkQuoteRequest $quote, string $recipients, string $note, bool $automatic = false): void
    {
        $details = [
            'Request reference' => (string) $quote->reference,
            'Submitted' => (string) ($quote->created_at?->format('d M Y, g:i A') ?: ''),
            'Status' => (string) $quote->status,
            'Customer name' => (string) $quote->full_name,
            'Organization' => (string) $quote->organization,
            'Customer email' => (string) $quote->email,
            'Customer phone' => (string) $quote->phone,
            'Product' => (string) $quote->product_type,
            'Quantity' => $quote->estimatedQuantityLabel(),
            'Sizes needed' => (string) $quote->sizes_needed,
            'Budget (submitted)' => (string) $quote->budget_range,
            'Customization' => implode(', ', $quote->customizationLabels()),
            'Product information' => $this->asText((array) $quote->product_information),
            'Artwork instructions' => (string) $quote->artwork_details,
            'Shipping address' => (string) $quote->shipping_address,
            'Country' => (string) $quote->country,
            'State / Province' => (string) $quote->state_province,
            'Postal code' => (string) $quote->postal_code,
            'Shipping method' => (string) $quote->preferredShippingMethodLabel(),
            'Needed by' => (string) ($quote->needed_by?->format('d M Y') ?: ''),
            'Event date' => (string) ($quote->event_date?->format('d M Y') ?: ''),
            'Customer notes' => (string) $quote->additional_notes,
            'Quotation details' => $this->asText((array) $quote->quotation_details),
        ];
        $files = [$this->dataFile('Quote_'.$quote->reference.'_Details.txt', $this->summary($details), 'text/plain')];
        $original = (array) $quote->attachment;
        if (filled($original['path'] ?? null)) {
            $disk = ($original['disk'] ?? '') === 'local' ? 'local' : 'public';
            $this->addStored($files, $disk, (string) $original['path'], (string) ($original['original_name'] ?? 'original-upload'), (string) ($original['mime_type'] ?? ''), 'request');
        }
        // Files for every item in the multi-product request, not only the first.
        foreach ((array) data_get($quote->product_information, 'selected_products', []) as $index => $item) {
            $file = is_array($item) ? ($item['artwork'] ?? null) : null;
            if (! is_array($file) || ! filled($file['path'] ?? null) || $file['path'] === ($original['path'] ?? null)) continue;
            $this->addStored($files, 'local', (string) $file['path'],
                (string) ($file['original_name'] ?? 'artwork'),
                (string) ($file['mime_type'] ?? ''), 'product'.($index + 1));
        }
        // One query for all quote, message, artwork and quotation files.
        foreach ($quote->quoteFiles()->get(['path', 'original_name', 'mime_type']) as $file) {
            $this->addStored($files, 'local', (string) $file->path, (string) $file->original_name, (string) $file->mime_type, 'quote');
        }
        $this->deliver($recipients, $note, 'Bulk quote '.$quote->reference, $details, route('admin.bulk-quotes.show', $quote), $files, $automatic);
    }

    /** @param array<int,array<string,mixed>> $files */
    private function addStored(array &$files, string $disk, string $path, string $name, string $mime, string $prefix): void
    {
        if (! in_array($disk, ['local', 'public'], true) || $path === '' || str_contains($path, '..')) {
            throw new RuntimeException('An attachment has an invalid storage path.');
        }
        $storage = Storage::disk($disk);
        if (! $storage->exists($path)) {
            throw new RuntimeException('Required attachment is missing: '.basename($name));
        }
        $size = $storage->size($path);
        if ($size > self::MAX_BYTES) {
            throw new RuntimeException('Attachment is too large for one confirmation email: '.basename($name));
        }
        // EmailMessage supports local storage references. Public uploads use in-memory data.
        $attachment = $disk === 'local'
            ? ['type' => 'storage', 'disk' => 'local', 'path' => $path]
            : ['type' => 'data', 'data' => $storage->get($path)];
        $files[] = $attachment + [
            'name' => $prefix.'-'.basename(str_replace('\\', '/', $name)),
            'mime' => $mime !== '' ? $mime : 'application/octet-stream',
            '_size' => $size,
        ];
    }

    /** @param array<int,array<string,mixed>> $files @param array<string,string> $details */
    private function deliver(string $recipients, string $note, string $subject, array $details, string $link, array $files, bool $automatic): void
    {
        $bytes = 0;
        foreach ($files as $file) {
            $bytes += (int) ($file['_size'] ?? strlen((string) ($file['data'] ?? '')));
        }
        $mailerName = (string) config('transactional_email.mailer', config('mail.default', 'log'));
        $mailer = (array) config('mail.mailers.'.$mailerName, []);
        $isE2a = ($mailer['transport'] ?? '') === 'e2a';
        $maxBytes = $isE2a ? self::E2A_MAX_BYTES : self::MAX_BYTES;
        $maxFiles = $isE2a ? self::E2A_MAX_FILES : self::MAX_FILES;
        if ($bytes > $maxBytes || count($files) > $maxFiles) {
            throw new RuntimeException($isE2a
                ? 'E2A allows up to 10 attachments and about 9 MB of files per confirmation email. Nothing was sent.'
                : 'All attachments cannot fit in a single email (limit: 14 MB / 30 files). Nothing was sent.');
        }
        if (in_array((string) ($mailer['transport'] ?? ''), ['log', 'array'], true)) {
            throw new RuntimeException('Forwarding requires a delivering email provider. Configure SMTP or E2A first.');
        }
        if (! $this->emails->enabled()) {
            throw new RuntimeException('Transactional email is disabled. Enable it before forwarding.');
        }
        $message = new EmailMessage(
            key: $automatic ? 'admin.confirmation-auto-forward' : 'admin.confirmation-forward',
            recipients: $this->settings->recipients($recipients),
            subject: 'Confirmation requested: '.$subject,
            heading: 'Please confirm '.$subject,
            introLines: [$note !== '' ? $note : 'Please review and confirm the attached details.'],
            details: $details,
            actionText: 'View in NextPlay Admin',
            actionUrl: $link,
            outroLines: [$automatic
                ? 'Automatically forwarded when the request was created. Reply to the sender with your confirmation.'
                : 'This forward was manually requested by a NextPlay administrator. Reply to the sender with your confirmation.'],
            attachments: array_map(static function (array $file): array { unset($file['_size']); return $file; }, $files),
        );
        // Synchronous send: delivery failures appear on this form, no background job or DB write.
        $this->emails->sendNow($message);
    }

    /** @return array<string,mixed> */
    private function dataFile(string $name, string $body, string $mime): array
    {
        return ['type' => 'data', 'name' => $name, 'data' => $body, 'mime' => $mime];
    }

    /** @param array<string,string> $details */
    private function summary(array $details): string
    {
        $lines = ['NEXTPLAY SPORTSWEAR', 'FORWARDED FOR CONFIRMATION', ''];
        foreach ($details as $label => $value) {
            $lines[] = $label.': '.$value;
        }
        return implode("\r\n", $lines)."\r\n";
    }

    private function asText(array $value): string
    {
        // Keep complete source details, including custom product options and sizes.
        $text = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        return $text !== false ? $text : '';
    }
}
