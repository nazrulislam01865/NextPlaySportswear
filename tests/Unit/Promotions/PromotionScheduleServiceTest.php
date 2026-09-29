<?php

namespace Tests\Unit\Promotions;

use App\Models\SaleBanner;
use App\Models\SaleCampaign;
use App\Services\Promotions\PromotionScheduleService;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class PromotionScheduleServiceTest extends TestCase
{
    private PromotionScheduleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PromotionScheduleService();
    }

    public function test_campaign_statuses_and_boundaries_are_computed_consistently(): void
    {
        $now = CarbonImmutable::parse('2026-09-29 12:00:00', 'UTC');

        $this->assertSame('Draft', $this->service->campaignStatus($this->campaign(['status' => 'draft']), $now));
        $this->assertSame('Scheduled', $this->service->campaignStatus($this->campaign(['starts_at' => $now->addSecond()]), $now));
        $this->assertSame('Ended', $this->service->campaignStatus($this->campaign(['ends_at' => $now->subSecond()]), $now));
        $this->assertSame('Active', $this->service->campaignStatus($this->campaign(['starts_at' => $now, 'ends_at' => $now]), $now));
    }

    public function test_campaign_weekdays_use_selected_timezone_and_invalid_timezone_falls_back_to_utc(): void
    {
        $now = CarbonImmutable::parse('2026-09-27 20:30:00', 'UTC'); // Monday in Dhaka, Sunday UTC.

        $dhaka = $this->campaign([
            'timezone' => 'Asia/Dhaka',
            'repeat_weekdays' => true,
            'weekdays' => ['Mon'],
            'starts_at' => $now->subDay(),
            'ends_at' => $now->addDay(),
        ]);
        $this->assertSame('Active', $this->service->campaignStatus($dhaka, $now));

        $invalid = $this->campaign([
            'timezone' => 'Not/A-Timezone',
            'repeat_weekdays' => true,
            'weekdays' => ['Sun'],
            'starts_at' => $now->subDay(),
            'ends_at' => $now->addDay(),
        ]);
        $this->assertSame('Active', $this->service->campaignStatus($invalid, $now));

        $invalid->weekdays = [];
        $this->assertSame('Paused today', $this->service->campaignStatus($invalid, $now));
    }

    public function test_banner_status_supports_independent_and_inherited_schedules(): void
    {
        $now = CarbonImmutable::parse('2026-09-29 12:00:00', 'UTC');

        $banner = new SaleBanner();
        $banner->forceFill([
            'is_active' => true,
            'inherit_campaign_schedule' => false,
            'starts_at' => $now->addHour(),
            'ends_at' => $now->addHours(2),
        ]);
        $this->assertSame('Scheduled', $this->service->bannerStatus($banner, $now));

        $banner->starts_at = $now;
        $banner->ends_at = $now;
        $this->assertSame('Active', $this->service->bannerStatus($banner, $now));

        $banner->starts_at = $now->subHours(2);
        $banner->ends_at = $now->subSecond();
        $this->assertSame('Ended', $this->service->bannerStatus($banner, $now));

        $banner->is_active = false;
        $this->assertSame('Draft', $this->service->bannerStatus($banner, $now));
        $banner->is_active = true;

        $campaign = $this->campaign([
            'repeat_weekdays' => true,
            'weekdays' => ['Mon'],
            'timezone' => 'UTC',
            'starts_at' => $now->subDay(),
            'ends_at' => $now->addDay(),
        ]); // 2026-09-29 is Tuesday.
        $banner->inherit_campaign_schedule = true;
        $banner->setRelation('campaign', $campaign);
        $this->assertSame('Paused today', $this->service->bannerStatus($banner, $now));

        $banner->unsetRelation('campaign');
        $banner->setRelation('campaign', null);
        $this->assertSame('Draft', $this->service->bannerStatus($banner, $now));
    }

    private function campaign(array $overrides = []): SaleCampaign
    {
        $campaign = new SaleCampaign();
        $campaign->forceFill(array_merge([
            'status' => 'live',
            'starts_at' => CarbonImmutable::parse('2026-09-01 00:00:00', 'UTC'),
            'ends_at' => CarbonImmutable::parse('2026-10-31 23:59:59', 'UTC'),
            'timezone' => 'UTC',
            'repeat_weekdays' => false,
            'weekdays' => [],
        ], $overrides));

        return $campaign;
    }
}
