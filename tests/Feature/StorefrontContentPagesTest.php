<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontContentPagesTest extends TestCase
{
    use RefreshDatabase;

    /** @dataProvider contentPageProvider */
    public function test_content_pages_render_successfully(string $routeName, string $expectedText): void
    {
        $this->get(route($routeName))
            ->assertOk()
            ->assertSee($expectedText);
    }

    public static function contentPageProvider(): array
    {
        return [
            ['about', 'Built Around Teams'],
            ['contact', 'Send Us a Message'],
            ['faq', 'Common Questions'],
            ['how-to-order', 'Two Ways to Order'],
            ['size-guide', 'Size Charts'],
            ['artwork-guidelines', 'Preferred Files'],
            ['customization-guide', 'Common Customization Options'],
            ['bulk-ordering', 'When to Use the Bulk Ordering Path'],
            ['shipping', 'Delivery Has More Than One Stage'],
            ['returns', 'Common Eligibility Scenarios'],
            ['payment-information', 'Payment Can Differ by Purchase Path'],
            ['privacy', 'Privacy Policy'],
            ['terms', 'Terms and Conditions'],
            ['cookies', 'Cookie Policy'],
            ['accessibility', 'Accessibility Statement'],
        ];
    }

    public function test_contact_message_can_be_submitted(): void
    {
        $response = $this->post(route('contact.store'), [
            'name' => '  Jordan Smith  ',
            'email' => ' JORDAN@EXAMPLE.COM ',
            'phone' => '+1 555 123 4567',
            'topic' => 'product-question',
            'order_number' => ' np-2026-1001 ',
            'message' => 'I need help choosing sizes for a team jersey order.',
            'company' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas(ContactMessage::class, [
            'email' => 'jordan@example.com',
            'topic' => 'product-question',
            'order_number' => 'NP-2026-1001',
            'status' => 'new',
        ]);

        $message = ContactMessage::query()->firstOrFail();
        $this->assertSame('I need help choosing sizes for a team jersey order.', $message->message);
        $this->assertNotSame($message->message, $message->getRawOriginal('message'));
        $this->assertNotNull($message->ip_hash);
    }

    public function test_content_pages_include_security_and_seo_metadata(): void
    {
        $response = $this->get(route('faq'));

        $response
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertSee('<link rel="canonical"', false)
            ->assertSee('og:site_name', false)
            ->assertSee('FAQPage', false);
    }

    public function test_unknown_pages_use_the_themed_secure_404_response(): void
    {
        $this->get('/missing-nextplay-page')
            ->assertNotFound()
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertSee('We Couldn’t Find That Page');
    }

    public function test_contact_message_rejects_invalid_input_and_honeypot(): void
    {
        $this->from(route('contact'))
            ->post(route('contact.store'), [
                'name' => 'A',
                'email' => 'invalid-email',
                'topic' => 'unsupported-topic',
                'message' => 'short',
                'company' => 'spam-bot-value',
            ])
            ->assertRedirect(route('contact'))
            ->assertSessionHasErrors(['name', 'email', 'topic', 'message', 'company']);
    }
}
