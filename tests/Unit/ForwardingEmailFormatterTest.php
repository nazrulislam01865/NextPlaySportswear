<?php

namespace Tests\Unit;

use App\Services\Email\ForwardingEmailFormatter;
use PHPUnit\Framework\TestCase;

class ForwardingEmailFormatterTest extends TestCase
{
    public function test_nested_shipping_payment_and_customization_values_are_readable_without_json(): void
    {
        $formatter = new ForwardingEmailFormatter();
        $section = $formatter->section('Delivery', [
            'shipping_address' => [
                'first_name' => 'Morgan',
                'last_name' => 'Sample',
                'delivery_instruction' => null,
                'address_line_1' => '12 Main Street',
                'is_remote_area' => false,
            ],
            'shipping_method' => json_encode([
                'title' => 'Standard Shipping',
                'estimate_days' => 12,
                'per_product' => [['sku' => 'TEST-100', 'quantity' => 3]],
            ], JSON_THROW_ON_ERROR),
            'postal_code' => '01234',
        ]);

        $text = $formatter->sectionsText([$section]);
        self::assertStringContainsString('Shipping Address:', $text);
        self::assertStringContainsString('First Name: Morgan', $text);
        self::assertStringContainsString('Is Remote Area: No', $text);
        self::assertStringContainsString('Title: Standard Shipping', $text);
        self::assertStringContainsString('SKU: TEST-100', $text);
        self::assertStringContainsString('Postal Code: 01234', $text);
        self::assertStringNotContainsString('{"', $text);
        self::assertStringNotContainsString('Delivery Instruction:', $text);
        self::assertSame('12 Main Street', $section['rows'][3]['value']);
    }

    public function test_non_json_messages_and_zero_values_are_preserved(): void
    {
        $formatter = new ForwardingEmailFormatter();
        $rows = $formatter->section('Notes', [
            'message' => "First line\nSecond line",
            'discount' => 0,
            'extra' => [],
            'enabled' => true,
        ])['rows'];

        self::assertCount(3, $rows);
        self::assertSame("First line\nSecond line", $rows[0]['value']);
        self::assertSame('0', $rows[1]['value']);
        self::assertSame('Yes', $rows[2]['value']);
    }
}
