<?php

namespace Tests\Feature\Promotions;

use App\Models\SaleCampaign;
use App\Services\Promotions\SaleCampaignService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleCampaignScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_sale_campaign_service_uses_runtime_schedule_authority(): void
    {
        $now = CarbonImmutable::parse('2026-09-29 12:00:00', 'UTC');
        CarbonImmutable::setTestNow($now);

        $this->createCampaign([
            'name' => 'Active',
            'starts_at' => $now->subHour(),
            'ends_at' => $now->addHour(),
        ]);
        $this->createCampaign([
            'name' => 'Future',
            'starts_at' => $now->addMinute(),
            'ends_at' => $now->addHour(),
        ]);
        $this->createCampaign([
            'name' => 'Paused',
            'starts_at' => $now->subHour(),
            'ends_at' => $now->addHour(),
            'repeat_weekdays' => true,
            'weekdays' => ['Mon'],
        ]);

        $names = collect(app(SaleCampaignService::class)->salePageCampaigns())->pluck('name')->all();

        $this->assertSame(['Active'], $names);
    }

    private function createCampaign(array $overrides): SaleCampaign
    {
        return SaleCampaign::query()->create(array_merge([
            'name' => 'Campaign',
            'status' => 'live',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'timezone' => 'UTC',
            'repeat_weekdays' => false,
            'weekdays' => [],
            'applies_to' => 'all',
            'show_sale_badge' => true,
            'show_sale_page' => true,
            'priority' => 1,
        ], $overrides));
    }
}
