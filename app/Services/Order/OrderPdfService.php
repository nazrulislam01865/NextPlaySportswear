<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\OrderCreditNote;

class OrderPdfService
{
    public function invoice(Order $order): string
    {
        $order->loadMissing('items');
        $lines = [
            config('storefront.name', 'NextPlay Sportswear'),
            'INVOICE '.$order->order_number,
            'Issued: '.($order->placed_at?->format('M d, Y') ?? now()->format('M d, Y')),
            'Customer: '.$order->customer_name,
            'Email: '.$order->customer_email,
            '',
            'ITEMS',
        ];

        foreach ($order->items as $item) {
            $lines[] = sprintf('%s  x%d  $%s', $item->product_name, $item->quantity, number_format((float) $item->line_total, 2));

            $customization = (array) $item->customization;
            $fulfillment = (array) ($customization['fulfillment'] ?? []);
            $production = is_array($fulfillment['production'] ?? null) ? $fulfillment['production'] : null;
            $shipping = is_array($fulfillment['shipping'] ?? null) ? $fulfillment['shipping'] : null;

            if ($production !== null) {
                $lines[] = sprintf(
                    '  Production charge: %s',
                    (string) ($production['display_amount'] ?? 'Included')
                );
            }

            if ($shipping !== null) {
                $lines[] = sprintf(
                    '  Shipping: %s (%s)',
                    (string) ($shipping['label'] ?? 'Selected shipping'),
                    (string) ($shipping['display_amount'] ?? 'Included')
                );
            }
        }

        $lines = array_merge($lines, [
            '',
            'Subtotal: $'.number_format((float) $order->subtotal, 2),
            'Customization: $'.number_format($order->displayCustomizationTotal(), 2),
            'Discount: -$'.number_format((float) $order->discount_total, 2),
            'Shipping charges: $'.number_format($order->displayShippingTotal(), 2),
            'Tax: $'.number_format((float) $order->tax_total, 2),
            'TOTAL: $'.number_format((float) $order->grand_total, 2).' '.$order->currency,
            '',
            'Payment status: '.$order->paymentStatusLabel(),
            'Order status: '.$order->statusLabel(),
        ]);

        return $this->document($lines, 'Invoice '.$order->order_number);
    }

    /** Order summary attached when an accepted quotation is converted to an order. */
    public function orderDetails(Order $order, \App\Models\BulkQuoteRequest $quote): string
    {
        $order->loadMissing('items');
        $shipping = (array) $order->shipping_address;
        $address = (array) ($shipping['address'] ?? []);
        $details = (array) $quote->quotation_details;
        $productInfo = (array) $quote->product_information;
        $lines = [
            strtoupper((string) config('storefront.name', 'NextPlay Sportswear')),
            'ORDER DETAILS',
            'Order number: '.$order->order_number,
            'Quotation reference: '.$quote->reference,
            'Created: '.($order->placed_at?->format('M d, Y') ?? now()->format('M d, Y')),
            '',
            'CUSTOMER',
            'Name: '.$order->customer_name,
            'Email: '.$order->customer_email,
            'Phone: '.($order->customer_phone ?: 'Not supplied'),
            'Company: '.($quote->organization ?: 'Not supplied'),
            '',
            'DELIVERY',
            'Address: '.($address['address_line_1'] ?? $quote->shipping_address ?? ''),
            'Region: '.($address['state'] ?? $quote->state_province ?? ''),
            'Country: '.($address['country'] ?? $quote->country ?? ''),
            'Postal code: '.($address['postal_code'] ?? $quote->postal_code ?? ''),
            'Shipping method: '.($order->shipping_method['name'] ?? $quote->preferredShippingMethodLabel()),
            'Needed by: '.($quote->needed_by?->format('M d, Y') ?? 'Not specified'),
            '',
            'PRODUCTS',
        ];
        foreach ($order->items as $item) {
            $lines[] = $item->product_name;
            $lines[] = 'Quantity: '.$item->quantity.' | Unit price: '.number_format((float) $item->unit_price, 2).' '.$order->currency;
            $lines[] = 'Line total: '.number_format((float) $item->line_total, 2).' '.$order->currency;
        }
        foreach ((array) ($productInfo['options'] ?? []) as $option) {
            if (is_array($option) && ! empty($option['label']) && isset($option['value'])) {
                $lines[] = $option['label'].': '.(is_array($option['value']) ? implode(', ', $option['value']) : $option['value']);
            }
        }
        foreach ((array) ($productInfo['size_groups'] ?? []) as $group) {
            if (! is_array($group)) continue;
            $parts = [];
            foreach ((array) ($group['sizes'] ?? []) as $size) {
                if (is_array($size) && isset($size['size'], $size['quantity'])) $parts[] = $size['size'].' x '.$size['quantity'];
            }
            if ($parts) $lines[] = ($group['label'] ?? 'Sizes').': '.implode(', ', $parts);
        }
        $lines = array_merge($lines, [
            'Size: '.($quote->sizes_needed ?: 'Not specified'),
            'Artwork instructions: '.($quote->artwork_details ?: 'None'),
            'Customer notes: '.($quote->additional_notes ?: 'None'),
            'Quotation notes: '.($details['quotation_notes'] ?? 'None'),
            '',
            'PRICING',
            'Subtotal: '.number_format((float) $order->subtotal, 2).' '.$order->currency,
            'Customization: '.number_format((float) $order->customization_total, 2).' '.$order->currency,
            'Shipping: '.number_format((float) $order->shipping_total, 2).' '.$order->currency,
            'Total: '.number_format((float) $order->grand_total, 2).' '.$order->currency,
            'Payment status: '.$order->paymentStatusLabel(),
            'Order status: '.$order->statusLabel(),
            'Payment terms: '.($details['payment_terms'] ?? 'See quotation'),
            '',
            'TRACKING',
            'Track your order: '.route('orders.track'),
            'Use order number '.$order->order_number.' and the email above to verify your order.',
        ]);
        return $this->pagedDocument($lines, 'Order '.$order->order_number);
    }

    /** Paginated PDF for potentially long descriptions, with no external converter. */
    private function pagedDocument(array $lines, string $title): string
    {
        $wrapped = [];
        foreach ($lines as $line) {
            $safe = preg_replace('/[^\x20-\x7E]/', '-', (string) $line) ?? '';
            foreach (explode("\n", wordwrap($safe, 94, "\n", true)) as $part) {
                $wrapped[] = $part;
            }
        }
        $pages = array_chunk($wrapped, 46);
        $pages = $pages ?: [[]];
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $kids = [];
        foreach ($pages as $linesForPage) {
            $pageId = count($objects) + 1;
            $contentId = $pageId + 1;
            $kids[] = $pageId.' 0 R';
            $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 3 0 R >> >> /Contents '.$contentId.' 0 R >>';
            $stream = "BT\n/F1 10 Tf\n48 748 Td\n";
            foreach ($linesForPage as $index => $line) {
                if ($index > 0) $stream .= "0 -15 Td\n";
                $stream .= '('.$this->escape($line).") Tj\n";
            }
            $stream .= 'ET';
            $objects[] = '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream";
        }
        $objects[1] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.count($pages).' >>';
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n{$object}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= 'xref'."\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) $pdf .= sprintf('%010d 00000 n ', $offsets[$i])."\n";
        $pdf .= 'trailer << /Size '.(count($objects) + 1).' /Root 1 0 R >>'."\nstartxref\n{$xref}\n%%EOF";
        return $pdf;
    }

    public function creditNote(OrderCreditNote $note): string
    {
        $note->loadMissing(['order','refund']);
        return $this->document([
            config('storefront.name', 'NextPlay Sportswear'),
            'CREDIT NOTE '.$note->credit_note_number,
            'Issued: '.$note->issued_at->format('M d, Y'),
            'Order: '.$note->order->order_number,
            'Customer: '.$note->order->customer_name,
            'Email: '.$note->order->customer_email,
            '',
            'Credit amount: $'.number_format((float) $note->amount, 2).' '.$note->currency,
            'Reason: '.($note->reason ?: 'Approved return or refund adjustment'),
            'Refund reference: '.($note->refund?->refund_number ?? 'Not applicable'),
            '',
            'This credit note documents the approved adjustment to the referenced order.',
        ], 'Credit Note '.$note->credit_note_number);
    }

    private function document(array $lines, string $title): string
    {
        $content = "BT\n/F1 12 Tf\n50 760 Td\n";
        foreach ($lines as $index => $line) {
            if ($index > 0) $content .= "0 -19 Td\n";
            $content .= '('.$this->escape((string) $line).") Tj\n";
        }
        $content .= "ET";

        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>';
        $objects[] = '<< /Length '.strlen($content)." >>\nstream\n{$content}\nendstream";
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[] = '<< /Title ('.$this->escape($title).') /Producer (NextPlay Commerce) >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n{$object}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        $pdf .= 'trailer << /Size '.(count($objects) + 1).' /Root 1 0 R /Info 6 0 R >>'."\nstartxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    private function escape(string $value): string
    {
        $value = preg_replace('/[^\x20-\x7E]/', '?', $value) ?? '';
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
    }
}
