<?php

namespace Tests\Unit;

use App\Services\Catalog\ProductChangeSummaryService;
use PHPUnit\Framework\TestCase;

class ProductChangeSummaryDetailsTest extends TestCase
{
    public function test_it_describes_a_price_change_with_old_and_new_values(): void
    {
        $service = new ProductChangeSummaryService();

        $before = [
            'Pricing' => [
                'fields' => [
                    'currency' => 'USD',
                    'base_price' => '25.00',
                ],
            ],
        ];

        $after = [
            'Pricing' => [
                'fields' => [
                    'currency' => 'USD',
                    'base_price' => '30.00',
                ],
            ],
        ];

        $this->assertContains(
            'Updated price from USD 25.00 to USD 30.00',
            $service->changeDetails($before, $after)
        );
    }

    public function test_it_summarizes_large_content_and_relation_changes_without_dumping_content(): void
    {
        $service = new ProductChangeSummaryService();

        $before = [
            'Product content' => [
                'fields' => ['description_html' => '<p>Old product description</p>'],
            ],
            'Images' => [['path' => 'old.jpg']],
        ];

        $after = [
            'Product content' => [
                'fields' => ['description_html' => '<p>New product description</p>'],
            ],
            'Images' => [['path' => 'new.jpg']],
        ];

        $details = $service->changeDetails($before, $after);

        $this->assertContains('Updated description', $details);
        $this->assertContains('Updated product images', $details);
        $this->assertStringNotContainsString('<p>', implode(' ', $details));
    }
}
