<?php

namespace Tests\Feature\Mail;

use App\Data\EmailMessage;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\User;
use App\Services\Email\TransactionalEmailManager;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryEstimateUpdatedEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_standalone_delivery_estimate_updated_blade_view_renders_design_elements(): void
    {
        $rendered = view('emails.order.delivery-estimate-updated', [
            'subject' => 'Delivery estimate updated for your order',
            'heading' => 'Delivery estimate updated for your order',
            'customerName' => 'John',
            'introText' => 'Your estimated delivery date has been updated because Christmas Day is a non-delivery date for your destination.',
            'previousEstimate' => '18 Dec - 25 Dec 2026',
            'updatedEstimate' => '18 Dec - 28 Dec 2026',
            'holidayReason' => 'Christmas Day',
            'holidayDate' => '25 Dec 2026',
            'holidayNotice' => 'Christmas Day · 25 Dec 2026',
            'actionUrl' => 'https://nextplaysportswear.test/orders/NP-12345',
            'actionText' => 'View Order',
            'orderNumber' => 'NP-12345',
        ])->render();

        $this->assertStringContainsString('NEXT', $rendered);
        $this->assertStringContainsString('PLAY', $rendered);
        $this->assertStringContainsString('#CF5D38', $rendered);
        $this->assertStringContainsString('Delivery estimate updated for your order', $rendered);
        $this->assertStringContainsString('Hi John,', $rendered);
        $this->assertStringContainsString('Previous estimate', $rendered);
        $this->assertStringContainsString('18 Dec - 25 Dec 2026', $rendered);
        $this->assertStringContainsString('Updated estimate', $rendered);
        $this->assertStringContainsString('18 Dec - 28 Dec 2026', $rendered);
        $this->assertStringContainsString('Holiday affecting delivery', $rendered);
        $this->assertStringContainsString('Christmas Day · 25 Dec 2026', $rendered);
        $this->assertStringContainsString('View Order', $rendered);
        $this->assertStringContainsString('Order #NP-12345', $rendered);
    }

    public function test_transactional_email_view_renders_two_column_estimate_and_holiday_banner(): void
    {
        $message = new EmailMessage(
            key: 'shipment.delivery-estimate-updated',
            recipients: [['email' => 'customer@example.com', 'name' => 'John Doe']],
            subject: 'Delivery estimate updated for your order – Order #NP-12345',
            heading: 'Delivery estimate updated for your order',
            introLines: [
                'Hi John,',
                'Your estimated delivery date has been updated because Christmas Day is a non-delivery date for your destination.',
            ],
            details: [
                'Order number' => '#NP-12345',
                'Previous estimate' => '18 Dec - 25 Dec 2026',
                'Updated estimate' => '18 Dec - 28 Dec 2026',
                'Holiday affecting delivery' => 'Christmas Day · 25 Dec 2026',
            ],
            actionText: 'View Order',
            actionUrl: 'https://nextplaysportswear.test/orders/NP-12345',
            outroLines: ['Thank you for shopping with NextPlay Sportswear.'],
        );

        $html = view('emails.transactional', ['emailMessage' => $message])->render();

        $this->assertStringContainsString('Delivery estimate updated for your order', $html);
        $this->assertStringContainsString('Previous estimate', $html);
        $this->assertStringContainsString('18 Dec - 25 Dec 2026', $html);
        $this->assertStringContainsString('Updated estimate', $html);
        $this->assertStringContainsString('18 Dec - 28 Dec 2026', $html);
        $this->assertStringContainsString('Holiday affecting delivery', $html);
        $this->assertStringContainsString('Christmas Day · 25 Dec 2026', $html);
        $this->assertStringContainsString('View Order', $html);
        $this->assertStringContainsString('#CF5D38', $html);
        $this->assertStringContainsString('Order #NP-12345', $html);
    }
}
