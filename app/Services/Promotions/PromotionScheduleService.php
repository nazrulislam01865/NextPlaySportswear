<?php

namespace App\Services\Promotions;

use App\Models\SaleBanner;
use App\Models\SaleCampaign;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use DateTimeZone;

class PromotionScheduleService
{
    public function campaignStatus(SaleCampaign $campaign, ?CarbonImmutable $nowUtc = null): string
    {
        if ((string) $campaign->status !== 'live') {
            return 'Draft';
        }

        $nowUtc = ($nowUtc ?? CarbonImmutable::now('UTC'))->utc();
        $startsAt = $this->asUtc($campaign->starts_at);
        $endsAt = $this->asUtc($campaign->ends_at);

        if ($startsAt && $nowUtc->lt($startsAt)) {
            return 'Scheduled';
        }

        if ($endsAt && $nowUtc->gt($endsAt)) {
            return 'Ended';
        }

        if (! (bool) $campaign->repeat_weekdays) {
            return 'Active';
        }

        $weekdays = collect((array) $campaign->weekdays)
            ->map(fn ($day): string => trim((string) $day))
            ->filter(fn (string $day): bool => in_array($day, ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], true))
            ->unique()
            ->values()
            ->all();

        if ($weekdays === []) {
            return 'Paused today';
        }

        $localDay = $nowUtc->setTimezone($this->normalizeTimezone($campaign->timezone))->format('D');

        return in_array($localDay, $weekdays, true) ? 'Active' : 'Paused today';
    }

    public function campaignIsActive(SaleCampaign $campaign, ?CarbonImmutable $nowUtc = null): bool
    {
        return $this->campaignStatus($campaign, $nowUtc) === 'Active';
    }

    public function bannerStatus(SaleBanner $banner, ?CarbonImmutable $nowUtc = null): string
    {
        if (! (bool) $banner->is_active) {
            return 'Draft';
        }

        $nowUtc = ($nowUtc ?? CarbonImmutable::now('UTC'))->utc();

        if ((bool) $banner->inherit_campaign_schedule) {
            $campaign = $banner->campaign;

            return $campaign instanceof SaleCampaign
                ? $this->campaignStatus($campaign, $nowUtc)
                : 'Draft';
        }

        $startsAt = $this->asUtc($banner->starts_at);
        $endsAt = $this->asUtc($banner->ends_at);

        if ($startsAt && $nowUtc->lt($startsAt)) {
            return 'Scheduled';
        }

        if ($endsAt && $nowUtc->gt($endsAt)) {
            return 'Ended';
        }

        return 'Active';
    }

    public function bannerIsActive(SaleBanner $banner, ?CarbonImmutable $nowUtc = null): bool
    {
        return $this->bannerStatus($banner, $nowUtc) === 'Active';
    }

    public function normalizeTimezone(?string $timezone): string
    {
        $timezone = trim((string) $timezone);
        if ($timezone === '') {
            return 'UTC';
        }

        try {
            new DateTimeZone($timezone);

            return $timezone;
        } catch (\Throwable) {
            return 'UTC';
        }
    }

    private function asUtc(mixed $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if ($value instanceof DateTimeInterface) {
                return CarbonImmutable::instance($value)->utc();
            }

            return CarbonImmutable::parse((string) $value, 'UTC')->utc();
        } catch (\Throwable) {
            return null;
        }
    }
}
