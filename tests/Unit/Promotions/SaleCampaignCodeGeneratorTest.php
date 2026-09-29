<?php

namespace Tests\Unit\Promotions;

use App\Models\SaleCampaign;
use App\Services\Promotions\SaleCampaignCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleCampaignCodeGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_prefixes_follow_campaign_name_rules(): void
    {
        $generator = new SaleCampaignCodeGenerator();

        $this->assertSame('WD', $generator->prefixFor('Winter Deals'));
        $this->assertSame('SCS', $generator->prefixFor('Summer Clearance Sale'));
        $this->assertSame('BTSM', $generator->prefixFor('Back To School Mega Sale'));
        $this->assertSame('CL', $generator->prefixFor('Clearance'));
    }

    public function test_generator_retries_collisions_and_uses_uppercase_alphanumeric_suffix(): void
    {
        $this->campaign('Winter Deals', 'WD-12AB');

        $generator = new class extends SaleCampaignCodeGenerator {
            private array $suffixes = ['12AB', '34CD'];
            protected function randomSuffix(): string
            {
                return array_shift($this->suffixes) ?? 'ZZZZ';
            }
        };

        $this->assertSame('WD-34CD', $generator->generate('Winter Deals'));
        $this->assertMatchesRegularExpression('/^WD-[A-Z0-9]{4}$/', $generator->generate('Winter Deals'));
    }

    private function campaign(string $name, string $code): SaleCampaign
    {
        return SaleCampaign::query()->create([
            'name' => $name,
            'internal_code' => $code,
            'status' => 'draft',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'starts_at' => now(),
            'ends_at' => now()->addDay(),
            'timezone' => 'UTC',
            'applies_to' => 'all',
            'show_sale_badge' => true,
            'show_sale_page' => true,
            'priority' => 1,
        ]);
    }
}
