<?php

namespace App\Services\Storefront;

use App\Models\StorefrontPageSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Editable copy for the public payment information document.
 * Uses the same single-row settings store and cache as the other policy pages.
 * Payment processing, gateways and checkout are intentionally not involved.
 */
class PaymentPageService
{
    public const PAGE_KEY = 'payment-information';
    public const CACHE_KEY = 'storefront.page.payment-information.v2';

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return [
            'hero' => [
                'title' => 'Payment Information',
                'description' => 'Payment methods and terms for your custom sportswear order.',
            ],
            'navigation' => [
                'home_label' => 'Home',
                'breadcrumb_label' => '',
                'sidebar_heading' => 'On this page',
            ],
            'sections' => [
                [
                    'nav_label' => 'Payment methods',
                    'title' => 'Payment methods',
                    'content' => 'Available payment methods are shown at checkout. For quotation orders, follow the payment instructions on your accepted quotation or invoice. Never send card details by email or chat.',
                ],
                [
                    'nav_label' => 'Prices and charges',
                    'title' => 'Prices and charges',
                    'content' => 'Review the currency, product costs, customisation, shipping, applicable taxes and any remote-area surcharge before paying. Your final total is shown at checkout or in your accepted quotation.',
                ],
                [
                    'nav_label' => 'Payment terms',
                    'title' => 'Payment terms',
                    'content' => 'Online orders follow the payment terms shown at checkout. For team and bulk orders, any deposit, balance and due dates will be stated in the accepted quotation or invoice. Changes to quantity, design or delivery may require a revised quotation.',
                ],
                [
                    'nav_label' => 'Artwork and production',
                    'title' => 'Artwork approval and production',
                    'content' => 'Payment does not replace artwork approval. Production begins after final artwork approval and the required payment has cleared. Production time depends on the product and quantity. Shipping time starts after production is complete; both are quoted in working days.',
                ],
                [
                    'nav_label' => 'Multiple products',
                    'title' => 'Multiple products and split shipments',
                    'content' => 'One payment can cover multiple products under one order. Products may have separate sub-orders, production schedules, delivery addresses and shipping charges. Review each shipment and the combined total before confirming.',
                ],
                [
                    'nav_label' => 'Payment status',
                    'title' => 'Understanding your payment status',
                    'content' => '',
                ],
                [
                    'nav_label' => 'Changes and refunds',
                    'title' => 'Order changes cancellations and refunds',
                    'content' => 'Contact us promptly if you need to change or cancel an order. Available options depend on the artwork and production stage. Please read our Returns & Refunds policy for details. Any approved refund will follow the applicable payment method and policy.',
                ],
                [
                    'nav_label' => 'Payment help',
                    'title' => 'Payment help and security',
                    'content' => 'If a payment fails or remains pending, check your billing details and payment status before trying again. Contact support with your order number and checkout email. Never share your full card number, security code or banking password.',
                ],
            ],
            'status_table' => [
                'columns' => ['Status', 'What it means'],
                'rows' => [
                    ['cells' => ['Paid', 'Payment confirmed; artwork approval may still be required.']],
                    ['cells' => ['Pending', 'Payment is processing or awaiting confirmation. Check before retrying.']],
                    ['cells' => ['Failed', 'Payment was unsuccessful. Review your details or use another available method.']],
                    ['cells' => ['Refunded', 'An approved refund has been issued; processing time depends on the payment provider.']],
                    ['cells' => ['Partially paid', 'A payment has been received; the remaining balance follows your agreed terms.']],
                ],
            ],
            'refund_link' => [
                'label' => 'Read Returns & Refunds →',
                'href' => '/returns-refunds-exchanges',
            ],
            'support' => [
                'title' => 'Need help with a payment?',
                'description' => 'Contact our support team if you have a payment question or need help with your order.',
                'primary_label' => 'Contact support',
                'primary_href' => '/contact-us',
                'secondary_label' => 'Track your order →',
                'secondary_href' => '/track-order',
            ],
            'seo' => [
                'meta_title' => 'Payment Information | NextPlay Sportswear',
                'meta_description' => 'Payment methods, charges, terms, artwork approval and payment status information for NextPlay Sportswear orders.',
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function settings(): array
    {
        return Cache::remember(self::CACHE_KEY, 3600, function (): array {
            $defaults = $this->defaults();
            $record = StorefrontPageSetting::query()->where('page_key', self::PAGE_KEY)->first();
            if (! $record) {
                return $defaults;
            }

            $content = is_array($record->content) ? $record->content : [];
            $seo = is_array($record->seo) ? $record->seo : [];
            $merged = array_replace_recursive($defaults, $content);
            // Replace, rather than recursively merge, table lists. Otherwise removing
            // a row or column would silently restore the old default entry.
            $merged['status_table'] = $this->normalizeStatusTable($content['status_table'] ?? $defaults['status_table']);
            $merged['seo'] = array_merge($defaults['seo'], $seo);

            return $merged;
        });
    }

    /**
     * Normalize legacy two-column settings without a data migration.
     * The old editor persisted status_heading/meaning_heading and fixed status/meaning rows.
     * New editor rows use a column-ordered cells array, so arbitrary columns are possible.
     *
     * @param array<string, mixed> $table
     * @return array{columns: list<string>, rows: list<array{cells: list<string>}>}
     */
    public function normalizeStatusTable(array $table): array
    {
        if (isset($table['columns']) && is_array($table['columns'])) {
            $columns = array_map(static fn ($heading): string => (string) $heading, array_values($table['columns']));
            $rows = [];
            foreach (($table['rows'] ?? []) as $row) {
                $rows[] = ['cells' => array_map(
                    static fn ($cell): string => (string) ($cell ?? ''),
                    array_values(is_array($row['cells'] ?? null) ? $row['cells'] : [])
                )];
            }

            return ['columns' => $columns, 'rows' => $rows];
        }

        return [
            'columns' => [
                (string) ($table['status_heading'] ?? 'Status'),
                (string) ($table['meaning_heading'] ?? 'What it means'),
            ],
            'rows' => array_map(static fn (array $row): array => [
                'cells' => [(string) ($row['status'] ?? ''), (string) ($row['meaning'] ?? '')],
            ], array_values(is_array($table['rows'] ?? null) ? $table['rows'] : [])),
        ];
    }

    /** @param array<string, mixed> $payload */
    public function update(array $payload, ?int $adminId = null): StorefrontPageSetting
    {
        $content = [
            'hero' => $payload['hero'],
            'navigation' => $payload['navigation'],
            'sections' => array_values($payload['sections']),
            'status_table' => $this->normalizeStatusTable($payload['status_table']),
            'refund_link' => $payload['refund_link'],
            'support' => $payload['support'],
        ];
        $seo = $payload['seo'] ?? [];

        $record = StorefrontPageSetting::updateOrCreate(
            ['page_key' => self::PAGE_KEY],
            [
                'title' => $content['hero']['title'],
                'content' => $content,
                'seo' => $seo,
                'updated_by' => $adminId,
            ]
        );
        Cache::forget(self::CACHE_KEY);

        return $record;
    }
}
