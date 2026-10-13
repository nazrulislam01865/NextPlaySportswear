<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'key',
    'name',
    'description',
    'trigger_event',
    'icon',
    'status',
    'active_version',
    'draft_version',
    'subject',
    'preheader_text',
    'heading',
    'intro_message',
    'cta_label',
    'cta_url_type',
    'cta_custom_url',
    'visibility_settings',
    'blocks',
    'sample_data',
    'published_at',
    'published_by',
    'updated_by',
])]
class EmailTemplate extends Model
{
    protected function casts(): array
    {
        return [
            'visibility_settings' => 'array',
            'blocks' => 'array',
            'sample_data' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(EmailTemplateVersion::class)->orderByDesc('id');
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public const CORE_KEYS = [
        'order-confirmation',
        'delivery-estimate-updated',
        'shipment-update',
        'password-reset',
        'referral-reward',
        'welcome-email',
    ];

    public function isPublished(): bool
    {
        return strtolower((string) $this->status) === 'published';
    }

    public function isCore(): bool
    {
        return in_array($this->key, self::CORE_KEYS, true);
    }

    public function isCustom(): bool
    {
        return ! $this->isCore();
    }

    public function isCopy(): bool
    {
        return ! $this->isCore();
    }

    public function canBeDeleted(): bool
    {
        return $this->isCopy();
    }

    public static function standardEvents(): array
    {
        return [
            'Order Placed',
            'Delivery Estimate Updated',
            'Order Shipped',
            'Password Reset Requested',
            'Referral Reward Earned',
            'Account Created',
            'Order Cancelled',
            'Order Refunded',
            'Return Request Approved',
            'Bulk Quote Received',
            'Contact Inquiry Received',
        ];
    }

    public static function starterPresets(): array
    {
        return [
            'blank' => [
                'name' => 'Blank Canvas',
                'description' => 'A clean, minimal structure with essential header, content, and footer blocks.',
                'icon' => 'mail',
                'trigger_event' => 'Custom Event',
                'subject' => 'Important notification from NextPlay Sportswear',
                'preheader_text' => 'Please review the information below.',
                'heading' => 'Hello {{customer_name}}',
                'intro_message' => 'We are reaching out with an update regarding your NextPlay customer account.',
                'cta_label' => 'Visit NextPlay',
                'cta_url_type' => 'custom',
                'cta_custom_url' => '/',
                'visibility_settings' => [
                    'show_logo' => true,
                    'show_greeting' => true,
                    'show_cta_button' => true,
                    'show_support_contact' => true,
                    'show_social_links' => true,
                    'show_footer_note' => true,
                ],
                'blocks' => [
                    ['id' => 'greeting', 'name' => 'Greeting', 'enabled' => true, 'variables' => ['customer_name']],
                    ['id' => 'cta_button', 'name' => 'CTA Button', 'enabled' => true, 'variables' => []],
                    ['id' => 'support_footer', 'name' => 'Support Footer', 'enabled' => true, 'variables' => ['support_email']],
                ],
                'sample_data' => [
                    'customer_name' => 'Jordan Smith',
                    'customer_email' => 'jordan.smith@example.com',
                    'support_email' => 'support@nextplay.com',
                ],
            ],
            'order' => [
                'name' => 'Order Notification',
                'description' => 'Pre-configured layout with order summary, total amount, and customer greeting.',
                'icon' => 'cart',
                'trigger_event' => 'Order Placed',
                'subject' => 'Your NextPlay order confirmation #{{order_number}}',
                'preheader_text' => 'Thank you for your order! Here are your confirmation details.',
                'heading' => 'Order Confirmed!',
                'intro_message' => "Thanks for your order, {{customer_name}}! We've received your order and our production team is getting it ready.",
                'cta_label' => 'View Order Details',
                'cta_url_type' => 'order_details',
                'visibility_settings' => [
                    'show_logo' => true,
                    'show_greeting' => true,
                    'show_order_number' => true,
                    'show_cta_button' => true,
                    'show_support_contact' => true,
                    'show_social_links' => true,
                    'show_footer_note' => true,
                ],
                'blocks' => [
                    ['id' => 'greeting', 'name' => 'Greeting', 'enabled' => true, 'variables' => ['customer_name']],
                    ['id' => 'order_summary', 'name' => 'Order Summary', 'enabled' => true, 'variables' => ['order_number', 'order_total']],
                    ['id' => 'cta_button', 'name' => 'CTA Button', 'enabled' => true, 'variables' => []],
                    ['id' => 'support_footer', 'name' => 'Support Footer', 'enabled' => true, 'variables' => ['support_email']],
                ],
                'sample_data' => [
                    'customer_name' => 'Jordan Smith',
                    'order_number' => '#NP-12345',
                    'order_date' => 'Oct 18, 2026',
                    'order_total' => '$129.98',
                    'items_count' => '2 items',
                ],
            ],
            'shipment' => [
                'name' => 'Shipping & Tracking',
                'description' => 'Dispatched notifications with tracking card, carrier info, and quick tracking link.',
                'icon' => 'box',
                'trigger_event' => 'Order Shipped',
                'subject' => 'Your NextPlay order #{{order_number}} is on the way!',
                'preheader_text' => 'Track your shipment and estimated arrival.',
                'heading' => 'Your Gear Has Shipped!',
                'intro_message' => 'Exciting news, {{customer_name}}! Your gear is on the way. Tracking details are available below.',
                'cta_label' => 'Track Package',
                'cta_url_type' => 'order_details',
                'visibility_settings' => [
                    'show_logo' => true,
                    'show_greeting' => true,
                    'show_order_number' => true,
                    'show_tracking_number' => true,
                    'show_cta_button' => true,
                    'show_support_contact' => true,
                    'show_social_links' => true,
                    'show_footer_note' => true,
                ],
                'blocks' => [
                    ['id' => 'greeting', 'name' => 'Greeting', 'enabled' => true, 'variables' => ['customer_name']],
                    ['id' => 'tracking_card', 'name' => 'Carrier Tracking Card', 'enabled' => true, 'variables' => ['tracking_number', 'carrier']],
                    ['id' => 'order_summary', 'name' => 'Order Summary', 'enabled' => true, 'variables' => ['order_number']],
                    ['id' => 'cta_button', 'name' => 'CTA Button', 'enabled' => true, 'variables' => []],
                    ['id' => 'support_footer', 'name' => 'Support Footer', 'enabled' => true, 'variables' => ['support_email']],
                ],
                'sample_data' => [
                    'customer_name' => 'Jordan Smith',
                    'order_number' => '#NP-12345',
                    'carrier' => 'UPS Ground',
                    'tracking_number' => '1Z999AA1234567890',
                    'estimated_delivery' => 'Oct 24, 2026',
                ],
            ],
            'delivery' => [
                'name' => 'Delivery Estimate Update',
                'description' => 'Delivery schedule adjustments with previous vs updated dates and holiday delay notices.',
                'icon' => 'truck',
                'trigger_event' => 'Delivery Estimate Updated',
                'subject' => 'Your delivery estimate has been updated',
                'preheader_text' => "Here's your new estimated delivery date.",
                'heading' => 'Your delivery estimate has been updated',
                'intro_message' => "We wanted to let you know that your order's estimated delivery date has changed. You can find the updated details below.",
                'cta_label' => 'View Order Details',
                'cta_url_type' => 'order_details',
                'visibility_settings' => [
                    'show_logo' => true,
                    'show_greeting' => true,
                    'show_previous_estimate' => false,
                    'show_updated_estimate' => true,
                    'show_holiday_reason' => true,
                    'show_delivery_card' => true,
                    'show_order_number' => true,
                    'show_cta_button' => true,
                    'show_support_contact' => true,
                    'show_social_links' => false,
                    'show_footer_note' => true,
                ],
                'blocks' => [
                    ['id' => 'greeting', 'name' => 'Greeting', 'enabled' => true, 'variables' => ['customer_name']],
                    ['id' => 'delivery_card', 'name' => 'Delivery Estimate Card', 'enabled' => true, 'variables' => ['previous_estimate', 'updated_estimate']],
                    ['id' => 'holiday_reason', 'name' => 'Holiday Reason Notice', 'enabled' => true, 'variables' => ['holiday_reason']],
                    ['id' => 'order_summary', 'name' => 'Order Summary', 'enabled' => true, 'variables' => ['order_number']],
                    ['id' => 'cta_button', 'name' => 'CTA Button', 'enabled' => true, 'variables' => []],
                    ['id' => 'support_footer', 'name' => 'Support Footer', 'enabled' => true, 'variables' => ['support_email']],
                ],
                'sample_data' => [
                    'customer_name' => 'Jordan Smith',
                    'order_number' => '#NP-12345',
                    'previous_estimate' => 'Tue, Dec 24, 2026',
                    'updated_estimate' => 'Fri, Dec 27, 2026',
                    'shipping_method' => 'Standard Shipping',
                    'holiday_reason' => 'Carrier holiday volume schedule adjustment.',
                ],
            ],
            'security' => [
                'name' => 'Security & Password',
                'description' => 'Transactional security alerts with security notice, expiry warnings, and direct CTA link.',
                'icon' => 'lock',
                'trigger_event' => 'Password Reset Requested',
                'subject' => 'Reset your NextPlay account password',
                'preheader_text' => 'Use this secure link to choose a new password.',
                'heading' => 'Reset Password Request',
                'intro_message' => 'We received a request to reset your password. If you made this request, click the button below to choose a new password.',
                'cta_label' => 'Reset Password',
                'cta_url_type' => 'custom',
                'visibility_settings' => [
                    'show_logo' => true,
                    'show_greeting' => true,
                    'show_cta_button' => true,
                    'show_support_contact' => true,
                    'show_social_links' => false,
                    'show_footer_note' => true,
                ],
                'blocks' => [
                    ['id' => 'greeting', 'name' => 'Greeting', 'enabled' => true, 'variables' => ['customer_name']],
                    ['id' => 'cta_button', 'name' => 'CTA Button', 'enabled' => true, 'variables' => []],
                    ['id' => 'security_notice', 'name' => 'Security Notice', 'enabled' => true, 'variables' => []],
                    ['id' => 'support_footer', 'name' => 'Support Footer', 'enabled' => true, 'variables' => ['support_email']],
                ],
                'sample_data' => [
                    'customer_name' => 'Jordan Smith',
                    'reset_link' => 'https://nextplay.com/reset-password/sample-token',
                ],
            ],
            'reward' => [
                'name' => 'Rewards & Promotions',
                'description' => 'Promotional credit or referral award notices with reward details and store link.',
                'icon' => 'gift',
                'trigger_event' => 'Referral Reward Earned',
                'subject' => 'You earned a {{reward_amount}} NextPlay reward!',
                'preheader_text' => 'Your store credit has been applied to your account.',
                'heading' => 'Congratulations {{customer_name}}!',
                'intro_message' => 'Thanks for sharing NextPlay Sportswear! Your referral reward has been credited.',
                'cta_label' => 'Shop with Reward',
                'cta_url_type' => 'custom',
                'visibility_settings' => [
                    'show_logo' => true,
                    'show_greeting' => true,
                    'show_cta_button' => true,
                    'show_support_contact' => true,
                    'show_social_links' => true,
                    'show_footer_note' => true,
                ],
                'blocks' => [
                    ['id' => 'greeting', 'name' => 'Greeting', 'enabled' => true, 'variables' => ['customer_name']],
                    ['id' => 'reward_card', 'name' => 'Reward Details Card', 'enabled' => true, 'variables' => ['reward_amount']],
                    ['id' => 'cta_button', 'name' => 'CTA Button', 'enabled' => true, 'variables' => []],
                    ['id' => 'support_footer', 'name' => 'Support Footer', 'enabled' => true, 'variables' => ['support_email']],
                ],
                'sample_data' => [
                    'customer_name' => 'Jordan Smith',
                    'reward_amount' => '$25.00 Store Credit',
                ],
            ],
            'welcome' => [
                'name' => 'Welcome & Onboarding',
                'description' => 'Friendly onboarding greeting to engage newly registered or verified customers.',
                'icon' => 'mail',
                'trigger_event' => 'Account Created',
                'subject' => 'Welcome to NextPlay Sportswear, {{customer_name}}!',
                'preheader_text' => 'Your account is ready. Explore custom gear and team jerseys.',
                'heading' => 'Welcome to NextPlay!',
                'intro_message' => 'Thanks for joining NextPlay Sportswear! We are thrilled to have you as part of our athletic community.',
                'cta_label' => 'Explore Gear',
                'cta_url_type' => 'custom',
                'visibility_settings' => [
                    'show_logo' => true,
                    'show_greeting' => true,
                    'show_cta_button' => true,
                    'show_support_contact' => true,
                    'show_social_links' => true,
                    'show_footer_note' => true,
                ],
                'blocks' => [
                    ['id' => 'greeting', 'name' => 'Greeting', 'enabled' => true, 'variables' => ['customer_name']],
                    ['id' => 'cta_button', 'name' => 'CTA Button', 'enabled' => true, 'variables' => []],
                    ['id' => 'support_footer', 'name' => 'Support Footer', 'enabled' => true, 'variables' => ['support_email']],
                ],
                'sample_data' => [
                    'customer_name' => 'Jordan Smith',
                    'customer_email' => 'jordan.smith@example.com',
                ],
            ],
        ];
    }

    public function nextVersion(): string
    {
        $current = (string) ($this->active_version ?? 'v1.0');
        if (preg_match('/^v?(\d+)\.(\d+)$/', $current, $matches)) {
            $major = (int) $matches[1];
            $minor = (int) $matches[2] + 1;
            return "v{$major}.{$minor}";
        }

        return 'v1.1';
    }

    public function createVersionSnapshot(string $version, ?int $userId = null): EmailTemplateVersion
    {
        return $this->versions()->create([
            'version' => $version,
            'status' => 'published',
            'subject' => $this->subject,
            'preheader_text' => $this->preheader_text,
            'heading' => $this->heading,
            'intro_message' => $this->intro_message,
            'cta_label' => $this->cta_label,
            'cta_url_type' => $this->cta_url_type,
            'cta_custom_url' => $this->cta_custom_url,
            'visibility_settings' => $this->visibility_settings,
            'blocks' => $this->blocks,
            'created_by' => $userId,
        ]);
    }
}
