<?php

namespace App\Services\Email;

use App\Contracts\EmailService;
use App\Data\EmailMessage;
use App\Models\BulkQuoteRequest;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Services\Order\OrderPdfService;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/** Forward new or existing orders, bulk quotes and contact messages. */
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
        private readonly ForwardingEmailFormatter $formatter,
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
        $products = [];
        foreach ($order->items as $index => $item) {
            $details['Product '.($index + 1)] = $item->product_name.' | SKU: '.$item->sku.' | Qty: '.$item->quantity
                .' | Unit: '.$item->unit_price.' | Total: '.$item->line_total;
            $details['Product '.($index + 1).' customization'] = $this->asText((array) $item->customization);
            $products['Product '.($index + 1)] = [
                'Product name' => (string) $item->product_name,
                'SKU' => (string) $item->sku,
                'Quantity' => (string) $item->quantity,
                'Unit price' => (string) $item->unit_price.' '.$order->currency,
                'Item total' => (string) $item->line_total.' '.$order->currency,
                'Customization' => (array) $item->customization,
            ];
        }
        $details['Subtotal'] = (string) $order->subtotal.' '.$order->currency;
        $details['Customization total'] = (string) $order->customization_total.' '.$order->currency;
        $details['Discount'] = (string) $order->discount_total.' '.$order->currency;
        $details['Shipping'] = (string) $order->shipping_total.' '.$order->currency;
        $details['Tax'] = (string) $order->tax_total.' '.$order->currency;
        $details['Grand total'] = (string) $order->grand_total.' '.$order->currency;
        $details['Customer note'] = (string) $order->customer_note;

        $sections = [
            $this->formatter->section('Order overview', [
                'Order number' => $details['Order number'],
                'Placed' => $details['Placed'],
                'Status' => $details['Status'],
                'Payment status' => $details['Payment status'],
            ]),
            $this->formatter->section('Customer & addresses', [
                'Customer name' => $details['Customer name'],
                'Customer email' => $details['Customer email'],
                'Customer phone' => $details['Customer phone'],
                'Shipping address' => $shipping,
                'Billing address' => $billing,
            ]),
            $this->formatter->section('Products & customization', $products),
            $this->formatter->section('Shipping & payment', [
                'Shipping method' => (array) $order->shipping_method,
                'Payment method' => (array) $order->payment_method,
                'Order information' => (array) $order->information,
            ]),
            $this->formatter->section('Order totals', [
                'Subtotal' => $details['Subtotal'],
                'Customization total' => $details['Customization total'],
                'Discount' => $details['Discount'],
                'Shipping' => $details['Shipping'],
                'Tax' => $details['Tax'],
                'Grand total' => $details['Grand total'],
            ]),
            $this->formatter->section('Customer notes', ['Note' => $details['Customer note']]),
        ];

        $files = [
            $this->dataFile('Order_'.$order->order_number.'_Details.txt', $this->summary($sections), 'text/plain'),
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
        $this->deliver($recipients, $note, 'Order '.$order->order_number, $details, route('admin.orders.show', $order), $files, $automatic, sections: $sections);
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
        $sections = [
            $this->formatter->section('Quote overview', [
                'Request reference' => $details['Request reference'],
                'Submitted' => $details['Submitted'],
                'Status' => $details['Status'],
            ]),
            $this->formatter->section('Customer details', [
                'Customer name' => $details['Customer name'],
                'Organization' => $details['Organization'],
                'Customer email' => $details['Customer email'],
                'Customer phone' => $details['Customer phone'],
            ]),
            $this->formatter->section('Products & requirements', [
                'Product' => $details['Product'],
                'Quantity' => $details['Quantity'],
                'Sizes needed' => $details['Sizes needed'],
                'Budget (submitted)' => $details['Budget (submitted)'],
                'Customization' => $details['Customization'],
                'Product information' => (array) $quote->product_information,
                'Artwork instructions' => $details['Artwork instructions'],
            ]),
            $this->formatter->section('Delivery requirements', [
                'Shipping address' => $details['Shipping address'],
                'Country' => $details['Country'],
                'State / Province' => $details['State / Province'],
                'Postal code' => $details['Postal code'],
                'Shipping method' => $details['Shipping method'],
                'Needed by' => $details['Needed by'],
                'Event date' => $details['Event date'],
            ]),
            $this->formatter->section('Quotation & notes', [
                'Quotation details' => (array) $quote->quotation_details,
                'Customer notes' => $details['Customer notes'],
            ]),
        ];
        $files = [$this->dataFile('Quote_'.$quote->reference.'_Details.txt', $this->summary($sections), 'text/plain')];
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
        $this->deliver($recipients, $note, 'Bulk quote '.$quote->reference, $details, route('admin.bulk-quotes.show', $quote), $files, $automatic, sections: $sections);
    }

    /** Forward the complete submitted Contact Us message, not a truncated preview. */
    public function contact(ContactMessage $contact, string $recipients, string $note, bool $automatic = false): void
    {
        $details = array_filter([
            'Contact message ID' => (string) $contact->getKey(),
            'Received' => (string) ($contact->created_at?->format('d M Y, g:i A') ?: ''),
            'Customer name' => (string) $contact->name,
            'Customer email' => (string) $contact->email,
            'Customer phone' => (string) $contact->phone,
            'Subject' => (string) $contact->topic,
            'Order / quote number' => (string) $contact->order_number,
            'Message' => (string) $contact->message,
            'Original attachment' => (string) $contact->attachment_name,
            'Download attachment (admin login required)' => filled($contact->attachment_path)
                ? route('admin.contact-page.attachment', $contact) : '',
        ], static fn (mixed $value): bool => filled($value));

        $sections = [
            $this->formatter->section('Enquiry overview', [
                'Contact message ID' => $details['Contact message ID'] ?? '',
                'Received' => $details['Received'] ?? '',
                'Subject' => $details['Subject'] ?? '',
                'Order / quote number' => $details['Order / quote number'] ?? '',
            ]),
            $this->formatter->section('Customer details', [
                'Customer name' => $details['Customer name'] ?? '',
                'Customer email' => $details['Customer email'] ?? '',
                'Customer phone' => $details['Customer phone'] ?? '',
            ]),
            $this->formatter->section('Customer message', ['Message' => $details['Message'] ?? '']),
            $this->formatter->section('Attached files', [
                'Original attachment' => $details['Original attachment'] ?? '',
                'Download attachment (admin login required)' => $details['Download attachment (admin login required)'] ?? '',
            ]),
        ];
        $files = [$this->dataFile('Contact_'.$contact->getKey().'_Details.txt', $this->summary($sections), 'text/plain')];

        if (filled($contact->attachment_path)) {
            $path = (string) $contact->attachment_path;
            // Contact uploads belong to this message and must remain private.
            if (! str_starts_with($path, 'contact-messages/'.$contact->getKey().'/')) {
                throw new RuntimeException('Contact attachment path is invalid.');
            }

            $mailer = (array) config('mail.mailers.'.config('transactional_email.mailer', config('mail.default', 'log')), []);
            $isE2a = ($mailer['transport'] ?? '') === 'e2a';
            // E2A has a composed-message limit, including base64 expansion.
            // Large valid contact uploads remain available through the protected admin link.
            $attachmentSize = (int) $contact->attachment_size;
            if (! $isE2a || $attachmentSize <= 6 * 1024 * 1024) {
                $this->addStored($files, 'local', $path,
                    (string) ($contact->attachment_name ?: 'contact-attachment'),
                    (string) ($contact->attachment_mime ?: 'application/octet-stream'), 'contact');
            }
        }

        $this->deliver($recipients, $note, 'Contact Us message #'.$contact->getKey(),
            $details, route('admin.contact-page.edit'), $files, $automatic,
            replyTo: (string) $contact->email, replyToName: (string) $contact->name, sections: $sections);
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
    private function deliver(string $recipients, string $note, string $subject, array $details, string $link, array $files, bool $automatic, ?string $replyTo = null, ?string $replyToName = null, array $sections = []): void
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
            if (! $automatic) {
                throw new RuntimeException($isE2a
                    ? 'E2A allows up to 10 attachments and about 9 MB of files per confirmation email. Nothing was sent.'
                    : 'All attachments cannot fit in a single email (limit: 14 MB / 30 files). Nothing was sent.');
            }
            // Never discard the automatic alert just because the files are too large.
            // The authenticated admin link provides access to the source documents.
            $details['Attachments'] = 'Attachment limits exceeded; open the request in NextPlay Admin to download files.';
            $sections[] = $this->formatter->section('Attachment notice', ['Files' => $details['Attachments']]);
            $files = [];
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
            replyTo: $replyTo,
            replyToName: $replyToName,
            attachments: array_map(static function (array $file): array { unset($file['_size']); return $file; }, $files),
            sections: $sections,
        );
        // Sending is immediate when invoked; automatic triggers run after the customer response.
        $this->emails->sendNow($message);
    }

    /** @return array<string,mixed> */
    private function dataFile(string $name, string $body, string $mime): array
    {
        return ['type' => 'data', 'name' => $name, 'data' => $body, 'mime' => $mime];
    }

    /**
     * @param array<int,array{title:string,rows:array<int,array{label:string,value:string,depth:int,group:bool}>}> $sections
     */
    private function summary(array $sections): string
    {
        return "NEXTPLAY SPORTSWEAR\r\nFORWARDED FOR CONFIRMATION\r\n\r\n"
            .$this->formatter->sectionsText($sections)."\r\n";
    }

    private function asText(array $value): string
    {
        return $this->formatter->asText($value);
    }
}
