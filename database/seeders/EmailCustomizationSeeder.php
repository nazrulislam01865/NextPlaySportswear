<?php

namespace Database\Seeders;

use App\Models\EmailGlobalBranding;
use App\Models\EmailTemplate;
use App\Models\EmailTemplateVersion;
use Illuminate\Database\Seeder;

class EmailCustomizationSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed or Update Global Branding Settings
        EmailGlobalBranding::query()->updateOrCreate(
            ['id' => 1],
            [
                'logo_path' => null,
                'header_bg_color' => '#0B2A4A',
                'button_color' => '#F15A2B',
                'font_family' => 'Inter',
                'footer_text' => "© 2026 NextPlay. All rights reserved.\nPlay More. Live Better.\nYou're receiving this email because you have an account with NextPlay.",
                'support_email' => 'support@nextplay.com',
                'support_phone' => '+1 (888) 123-4567',
                'social_links' => [
                    'facebook' => 'https://facebook.com/nextplay',
                    'instagram' => 'https://instagram.com/nextplay',
                    'twitter' => 'https://twitter.com/nextplay',
                    'youtube' => 'https://youtube.com/nextplay',
                ],
                'is_published' => true,
            ]
        );

        // 2. Standard Templates from specification
        $templates = [
            [
                'key' => 'order-confirmation',
                'name' => 'Order Confirmation',
                'description' => 'Sent after a successful order is placed.',
                'trigger_event' => 'Order Placed',
                'icon' => 'cart',
                'status' => 'published',
                'active_version' => 'v3.2',
                'draft_version' => null,
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
            [
                'key' => 'delivery-estimate-updated',
                'name' => 'Delivery Estimate Updated',
                'description' => 'Sent when the estimated delivery date changes.',
                'trigger_event' => 'Delivery Estimate Updated',
                'icon' => 'truck',
                'status' => 'published',
                'active_version' => 'v1.4',
                'draft_version' => 'v1.5',
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
                    'placed_date' => 'Oct 18, 2026',
                    'previous_estimate' => 'Tue, Dec 24, 2026',
                    'updated_estimate' => 'Fri, Dec 27, 2026',
                    'shipping_method' => 'Standard Shipping',
                    'tracking_number' => '1Z999AA1234567890',
                    'holiday_reason' => 'Christmas Day affects the original delivery date. Carrier operations are running on a modified schedule, which may cause delays.',
                ],
            ],
            [
                'key' => 'shipment-update',
                'name' => 'Shipment Update',
                'description' => 'Sent when an order ships.',
                'trigger_event' => 'Order Shipped',
                'icon' => 'box',
                'status' => 'published',
                'active_version' => 'v2.1',
                'draft_version' => null,
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
            [
                'key' => 'password-reset',
                'name' => 'Password Reset',
                'description' => 'Sent when a customer requests a password reset.',
                'trigger_event' => 'Password Reset Requested',
                'icon' => 'lock',
                'status' => 'published',
                'active_version' => 'v1.3',
                'draft_version' => null,
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
            [
                'key' => 'referral-reward',
                'name' => 'Referral Reward',
                'description' => 'Sent when a customer earns a referral reward.',
                'trigger_event' => 'Referral Reward Earned',
                'icon' => 'gift',
                'status' => 'draft',
                'active_version' => 'v1.0',
                'draft_version' => 'v1.0',
                'subject' => "You've earned a NextPlay reward credit!",
                'preheader_text' => 'Your friend completed their order. Here is your reward.',
                'heading' => 'You Earned a Reward!',
                'intro_message' => 'Great news, {{customer_name}}! Your referral successfully joined NextPlay and your reward credit is ready in your account.',
                'cta_label' => 'View Rewards',
                'cta_url_type' => 'account',
                'visibility_settings' => [
                    'show_logo' => true,
                    'show_greeting' => true,
                    'show_reward_amount' => true,
                    'show_cta_button' => true,
                    'show_support_contact' => true,
                    'show_social_links' => true,
                    'show_footer_note' => true,
                ],
                'blocks' => [
                    ['id' => 'greeting', 'name' => 'Greeting', 'enabled' => true, 'variables' => ['customer_name']],
                    ['id' => 'reward_card', 'name' => 'Reward Card', 'enabled' => true, 'variables' => ['reward_amount']],
                    ['id' => 'cta_button', 'name' => 'CTA Button', 'enabled' => true, 'variables' => []],
                    ['id' => 'support_footer', 'name' => 'Support Footer', 'enabled' => true, 'variables' => ['support_email']],
                ],
                'sample_data' => [
                    'customer_name' => 'Jordan Smith',
                    'reward_amount' => '$25.00 Store Credit',
                ],
            ],
            [
                'key' => 'welcome-email',
                'name' => 'Welcome Email',
                'description' => 'Sent to new customers after account creation.',
                'trigger_event' => 'Account Created',
                'icon' => 'user',
                'status' => 'published',
                'active_version' => 'v2.0',
                'draft_version' => null,
                'subject' => 'Welcome to NextPlay Sportswear!',
                'preheader_text' => 'Explore custom teamwear and performance sports gear.',
                'heading' => 'Welcome to the Team!',
                'intro_message' => "Welcome to NextPlay Sportswear, {{customer_name}}! We're thrilled to have you with us. Start customizing uniforms and team gear today.",
                'cta_label' => 'Explore Catalog',
                'cta_url_type' => 'storefront',
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
                ],
            ],
        ];

        foreach ($templates as $tmplData) {
            $template = EmailTemplate::query()->updateOrCreate(
                ['key' => $tmplData['key']],
                $tmplData
            );

            // Record initial version
            EmailTemplateVersion::query()->updateOrCreate(
                [
                    'email_template_id' => $template->id,
                    'version' => $template->active_version,
                ],
                [
                    'status' => $template->status,
                    'subject' => $template->subject,
                    'preheader_text' => $template->preheader_text,
                    'heading' => $template->heading,
                    'intro_message' => $template->intro_message,
                    'cta_label' => $template->cta_label,
                    'cta_url_type' => $template->cta_url_type,
                    'cta_custom_url' => $template->cta_custom_url,
                    'visibility_settings' => $template->visibility_settings,
                    'blocks' => $template->blocks,
                ]
            );
        }
    }
}
