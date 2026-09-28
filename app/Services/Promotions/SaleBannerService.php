<?php

namespace App\Services\Promotions;

use App\Models\SaleBanner;
use App\Support\PublicMedia;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class SaleBannerService
{
    private ?bool $tableAvailable = null;

    public function firstForPlacement(string $placement): ?array
    {
        return $this->forPlacement($placement)->first();
    }

    /** @return Collection<int, array<string, mixed>> */
    public function forPlacement(string $placement): Collection
    {
        if (! in_array($placement, SaleBanner::PLACEMENTS, true) || ! $this->tableAvailable()) {
            return collect();
        }

        $nowUtc = CarbonImmutable::now('UTC');

        return SaleBanner::query()
            ->where('is_active', true)
            ->whereNotNull('desktop_image_path')
            ->whereJsonContains('placements', $placement)
            ->with('campaign')
            ->orderBy('priority')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (SaleBanner $banner): bool => $this->isActiveNow($banner, $nowUtc))
            ->map(fn (SaleBanner $banner): array => $this->payload($banner))
            ->values();
    }

    public function status(SaleBanner $banner, ?CarbonImmutable $nowUtc = null): string
    {
        $nowUtc ??= CarbonImmutable::now('UTC');

        if (! $banner->is_active) {
            return 'Draft';
        }

        if ($banner->inherit_campaign_schedule) {
            if (! $banner->campaign || $banner->campaign->status !== 'live') {
                return 'Draft';
            }

            $startsAt = $banner->campaign->starts_at;
            $endsAt = $banner->campaign->ends_at;
            if ($startsAt && $nowUtc->lt($startsAt->copy()->timezone('UTC'))) {
                return 'Scheduled';
            }
            if ($endsAt && $nowUtc->gt($endsAt->copy()->timezone('UTC'))) {
                return 'Ended';
            }

            if ($banner->campaign->repeat_weekdays) {
                $days = (array) $banner->campaign->weekdays;
                try {
                    $day = $nowUtc->setTimezone($banner->campaign->timezone ?: 'UTC')->format('D');
                } catch (\Throwable) {
                    $day = $nowUtc->format('D');
                }
                if (! in_array($day, $days, true)) {
                    return 'Scheduled';
                }
            }

            return 'Active';
        }

        if ($banner->starts_at && $nowUtc->lt($banner->starts_at->copy()->timezone('UTC'))) {
            return 'Scheduled';
        }
        if ($banner->ends_at && $nowUtc->gt($banner->ends_at->copy()->timezone('UTC'))) {
            return 'Ended';
        }

        return 'Active';
    }

    public function isActiveNow(SaleBanner $banner, ?CarbonImmutable $nowUtc = null): bool
    {
        return $this->status($banner, $nowUtc) === 'Active';
    }

    private function tableAvailable(): bool
    {
        return $this->tableAvailable ??= Schema::hasTable('sale_banners');
    }

    /** @return array<string, mixed> */
    private function payload(SaleBanner $banner): array
    {
        return [
            'id' => (int) $banner->id,
            'name' => (string) $banner->name,
            'desktop_image_url' => PublicMedia::storedPathUrl((string) $banner->desktop_image_path),
            'mobile_image_url' => filled($banner->mobile_image_path)
                ? PublicMedia::storedPathUrl((string) $banner->mobile_image_path)
                : null,
            'alt_text' => (string) $banner->alt_text,
            'heading' => (string) $banner->heading,
            'cta_label' => (string) $banner->cta_label,
            'destination_link' => (string) $banner->destination_link,
            'campaign_id' => $banner->sale_campaign_id ? (int) $banner->sale_campaign_id : null,
        ];
    }
}
