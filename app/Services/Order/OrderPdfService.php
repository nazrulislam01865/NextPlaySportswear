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
        }

        $lines = array_merge($lines, [
            '',
            'Subtotal: $'.number_format((float) $order->subtotal, 2),
            'Customization: $'.number_format((float) $order->customization_total, 2),
            'Discount: -$'.number_format((float) $order->discount_total, 2),
            'Shipping: $'.number_format((float) $order->shipping_total, 2),
            'Tax: $'.number_format((float) $order->tax_total, 2),
            'TOTAL: $'.number_format((float) $order->grand_total, 2).' '.$order->currency,
            '',
            'Payment status: '.$order->paymentStatusLabel(),
            'Order status: '.$order->statusLabel(),
        ]);

        return $this->document($lines, 'Invoice '.$order->order_number);
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
