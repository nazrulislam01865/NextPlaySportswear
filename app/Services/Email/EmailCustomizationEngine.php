<?php

namespace App\Services\Email;

use App\Data\EmailMessage;
use App\Models\EmailGlobalBranding;
use App\Models\EmailTemplate;

class EmailCustomizationEngine
{
    /**
     * Standard predefined sample data contexts for testing & previewing templates.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function sampleOrders(): array
    {
        return [
            'np-12345' => [
                'order_key' => 'np-12345',
                'customer_name' => 'Jordan Smith',
                'customer_email' => 'jordan.smith@example.com',
                'order_number' => '#NP-12345',
                'order_date' => 'Oct 18, 2026',
                'items_count' => 2,
                'order_total' => '$129.98',
                'previous_estimate' => 'Tue, Dec 24, 2026',
                'updated_estimate' => 'Fri, Dec 27, 2026',
                'holiday_reason' => 'Christmas Day carrier operations running on a modified schedule.',
                'tracking_number' => '1Z999AA1234567890',
                'shipping_method' => 'Standard Ground Shipping',
                'carrier' => 'UPS Ground',
                'delivery_status' => 'Updated',
            ],
            'np-67890' => [
                'order_key' => 'np-67890',
                'customer_name' => 'Alex Morgan',
                'customer_email' => 'alex.morgan@example.com',
                'order_number' => '#NP-67890',
                'order_date' => 'Nov 02, 2026',
                'items_count' => 4,
                'order_total' => '$249.50',
                'previous_estimate' => 'Thu, Nov 05, 2026',
                'updated_estimate' => 'Sat, Nov 07, 2026',
                'holiday_reason' => 'Inclement regional winter storm causing localized transport delays.',
                'tracking_number' => '1Z888BB9876543210',
                'shipping_method' => 'Express Air Delivery',
                'carrier' => 'FedEx Express',
                'delivery_status' => 'Updated',
            ],
            'np-99211' => [
                'order_key' => 'np-99211',
                'customer_name' => 'Taylor Hayes',
                'customer_email' => 'taylor.hayes@example.com',
                'order_number' => '#NP-99211',
                'order_date' => 'Nov 15, 2026',
                'items_count' => 1,
                'order_total' => '$89.00',
                'previous_estimate' => 'Wed, Nov 18, 2026',
                'updated_estimate' => 'Fri, Nov 20, 2026',
                'holiday_reason' => 'Unprecedented holiday order volume processing queue.',
                'tracking_number' => '1Z777CC1122334455',
                'shipping_method' => 'Priority Courier',
                'carrier' => 'DHL Express',
                'delivery_status' => 'Updated',
            ],
        ];
    }

    /**
     * Get sample data context for a given order key, with sensible fallbacks.
     *
     * @return array<string, mixed>
     */
    public static function sampleContext(?string $orderKey = 'np-12345'): array
    {
        $samples = self::sampleOrders();
        $key = strtolower(trim((string) $orderKey));

        return $samples[$key] ?? $samples['np-12345'];
    }

    /**
     * Replace template variable placeholders like {{customer_name}} with real context values.
     */
    public function resolveVariables(?string $text, array $context = []): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $branding = EmailGlobalBranding::current();

        $variables = array_merge([
            'customer_name' => $context['customer_name'] ?? 'Valued Customer',
            'order_number' => $context['order_number'] ?? '#NP-00000',
            'order_date' => $context['order_date'] ?? now()->format('M d, Y'),
            'order_total' => $context['order_total'] ?? '$0.00',
            'items_count' => $context['items_count'] ?? 1,
            'previous_estimate' => $context['previous_estimate'] ?? now()->addDays(3)->format('D, M d, Y'),
            'updated_estimate' => $context['updated_estimate'] ?? now()->addDays(6)->format('D, M d, Y'),
            'holiday_reason' => $context['holiday_reason'] ?? 'Holiday carrier closure.',
            'tracking_number' => $context['tracking_number'] ?? '1Z9999999999999999',
            'shipping_method' => $context['shipping_method'] ?? 'Standard Shipping',
            'carrier' => $context['carrier'] ?? 'Carrier',
            'support_email' => $branding->support_email ?? 'support@nextplay.com',
            'support_phone' => $branding->support_phone ?? '+1 (888) 123-4567',
            'storefront_url' => url('/'),
        ], $context);

        $resolved = $text;

        foreach ($variables as $key => $value) {
            $val = is_scalar($value) ? (string) $value : '';
            $resolved = str_replace([
                '{{' . $key . '}}',
                '{{ ' . $key . ' }}',
                '@{{' . $key . '}}',
                '@{{ ' . $key . ' }}',
            ], $val, $resolved);
        }

        return $resolved;
    }

    /**
     * Build an EmailMessage instance ready for CentralEmailService dispatch.
     */
    public function buildEmailMessage(
        EmailTemplate $template,
        EmailGlobalBranding $branding,
        array $context,
        string $recipientEmail,
        ?string $recipientName = null
    ): EmailMessage {
        $subject = $this->resolveVariables($template->subject, $context);
        $heading = $this->resolveVariables($template->heading, $context);
        $intro = $this->resolveVariables($template->intro_message, $context);
        $ctaLabel = $this->resolveVariables($template->cta_label ?? 'View Details', $context);

        $actionUrl = match ($template->cta_url_type) {
            'order_details', 'Order Details Page' => \Illuminate\Support\Facades\Route::has('orders.details')
                ? route('orders.details', ['orderNumber' => $context['order_number'] ?? 'NP-12345'])
                : (\Illuminate\Support\Facades\Route::has('orders.dashboard') ? route('orders.dashboard') : url('/orders')),
            'storefront', 'Storefront Homepage' => url('/'),
            'account', 'Account Dashboard' => \Illuminate\Support\Facades\Route::has('dashboard') ? route('dashboard') : url('/account'),
            default => $template->cta_custom_url ?: url('/'),
        };

        $details = [];
        $visibility = $template->visibility_settings ?? [];

        $blocks = collect($template->blocks ?? []);
        $isBlockEnabled = function (string $id, bool $default = true) use ($blocks, $visibility): bool {
            $block = $blocks->firstWhere('id', $id);
            if ($block !== null) {
                return (bool) ($block['enabled'] ?? false);
            }
            return (bool) ($visibility['show_' . $id] ?? $default);
        };

        if ($isBlockEnabled('order_number', true) && ! empty($visibility['show_order_number'] ?? true)) {
            $details['Order Number'] = $context['order_number'] ?? '#NP-12345';
        }

        if (! empty($context['order_date'])) {
            $details['Order Date'] = $context['order_date'];
        }

        if (! empty($context['items_count'])) {
            $details['Items'] = (string) $context['items_count'];
        }

        if (! empty($context['order_total'])) {
            $details['Total'] = (string) $context['order_total'];
        }

        if ($isBlockEnabled('delivery_card', true)) {
            if (! empty($context['previous_estimate']) && ($visibility['show_previous_estimate'] ?? true)) {
                $details['Previous Estimate'] = $context['previous_estimate'];
            }

            if (! empty($context['updated_estimate']) && ($visibility['show_updated_estimate'] ?? true)) {
                $details['Estimated Delivery'] = $context['updated_estimate'];
            }
        }

        if (($isBlockEnabled('holiday_notice', true) || $isBlockEnabled('holiday_reason', true)) && (! empty($visibility['show_holiday_reason'] ?? true) || ! empty($visibility['show_holiday_notice'] ?? true)) && ! empty($context['holiday_reason'])) {
            $details['Reason'] = $context['holiday_reason'];
        }

        if (! empty($context['shipping_method'])) {
            $details['Shipping Method'] = $context['shipping_method'];
        }

        if (! empty($context['carrier'])) {
            $details['Carrier'] = $context['carrier'];
        }

        if (! empty($context['tracking_number'])) {
            $details['Tracking Number'] = $context['tracking_number'];
        }

        $outro = [];
        if ($isBlockEnabled('support_footer', true) && ! empty($visibility['show_support_contact'] ?? true)) {
            $supportLine = 'Need help? Contact our support team at ' . ($branding->support_email ?? 'support@nextplay.com');
            if (! empty($branding->support_phone)) {
                $supportLine .= ' or ' . $branding->support_phone;
            }
            $outro[] = $supportLine . '.';
        }

        if ($isBlockEnabled('support_footer', true) && ! empty($visibility['show_footer_note'] ?? true) && ! empty($branding->footer_text)) {
            $outro[] = $branding->footer_text;
        }

        $showCta = $isBlockEnabled('cta_button', true) && ! empty($visibility['show_cta_button'] ?? true);

        return new EmailMessage(
            key: 'template.' . $template->key,
            recipients: [
                [
                    'email' => $recipientEmail,
                    'name' => $recipientName ?: ($context['customer_name'] ?? 'Customer'),
                ],
            ],
            subject: $subject,
            heading: $heading,
            introLines: array_filter([$intro]),
            details: $details,
            actionText: $showCta ? $ctaLabel : null,
            actionUrl: $showCta ? $actionUrl : null,
            outroLines: $outro,
            replyTo: $branding->support_email ?: null,
            replyToName: 'NextPlay Support',
            metadata: [
                'template_key' => $template->key,
                'version' => $template->active_version,
            ],
        );
    }

    /**
     * Find a published template matching a template key or event trigger alias.
     */
    public function getPublishedTemplate(string $keyOrEvent): ?EmailTemplate
    {
        $normalized = strtolower(trim($keyOrEvent));

        $aliases = [
            'order.customer-placed' => 'order-confirmation',
            'order placed' => 'order-confirmation',
            'customer.welcome' => 'welcome-email',
            'customer verified' => 'welcome-email',
            'welcome' => 'welcome-email',
            'customer.password-reset' => 'password-reset',
            'password reset' => 'password-reset',
            'password reset requested' => 'password-reset',
            'shipment.customer-status-updated' => 'shipment-update',
            'order shipped' => 'shipment-update',
            'delivery estimate updated' => 'delivery-estimate-updated',
            'shipment.delivery-estimate' => 'delivery-estimate-updated',
        ];

        $targetKey = $aliases[$normalized] ?? $normalized;

        return EmailTemplate::query()
            ->where('status', 'published')
            ->where(function ($query) use ($targetKey, $keyOrEvent) {
                $query->where('key', $targetKey)
                    ->orWhere('key', $keyOrEvent)
                    ->orWhere('trigger_event', $keyOrEvent);
            })
            ->first();
    }

    /**
     * Build an EmailMessage from a published template, or return null if none is published.
     */
    public function buildFromPublishedTemplate(
        string $keyOrEvent,
        array $context,
        string $recipientEmail,
        ?string $recipientName = null
    ): ?EmailMessage {
        $template = $this->getPublishedTemplate($keyOrEvent);
        if (! $template) {
            return null;
        }

        $branding = EmailGlobalBranding::current();

        return $this->buildEmailMessage($template, $branding, $context, $recipientEmail, $recipientName);
    }

    /**
     * Dispatch transactional email through CentralEmailService if a published template exists.
     */
    public function dispatchTransactional(
        string $keyOrEvent,
        array $context,
        string $recipientEmail,
        ?string $recipientName = null,
        bool $sync = false
    ): bool {
        $message = $this->buildFromPublishedTemplate($keyOrEvent, $context, $recipientEmail, $recipientName);
        if (! $message) {
            return false;
        }

        /** @var CentralEmailService $emailService */
        $emailService = app(CentralEmailService::class);

        if ($sync) {
            try {
                $emailService->sendNow($message);
                return true;
            } catch (\Throwable $e) {
                return $emailService->safelyQueue($message);
            }
        }

        return $emailService->safelyQueue($message);
    }
}

