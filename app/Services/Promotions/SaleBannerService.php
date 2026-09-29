<?php

namespace App\Services\Promotions;

use App\Models\SaleBanner;
use App\Models\SaleCampaign;
use App\Support\PublicMedia;
use App\Support\PublicUrl;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class SaleBannerService
{
    private ?bool $tableAvailable = null;

    public function __construct(private readonly PromotionScheduleService $schedule)
    {
    }

    public function firstForPlacement(string $placement): ?array
    {
        return $this->forPlacement($placement)->first();
    }

    /** @return Collection<int, array<string, mixed>> */
    public function forPlacement(string $placement): Collection
    {
        return $this->activeModelsForPlacement($placement)
            ->map(fn (SaleBanner $banner): array => $this->payload($banner))
            ->filter(fn (array $payload): bool => filled($payload['desktop_image_url'] ?? null))
            ->values();
    }

    /**
     * Resolve exactly one payload for a page-level placement.
     *
     * Precedence for each Campaign candidate is linked Banner -> Campaign direct media.
     * Only after all candidates are exhausted may an unlinked/global Banner be used.
     *
     * @param  array<int, array<string, mixed>>  $campaignCandidates
     * @return array<string, mixed>|null
     */
    public function resolvePageSlot(array $campaignCandidates, string $placement): ?array
    {
        if (! in_array($placement, SaleBanner::PLACEMENTS, true)) {
            return null;
        }

        $banners = $this->activeModelsForPlacement($placement);

        foreach ($campaignCandidates as $candidate) {
            if (! is_array($candidate)) {
                continue;
            }

            $campaignId = (int) ($candidate['id'] ?? 0);
            if ($campaignId <= 0) {
                continue;
            }

            $linked = $banners->first(
                fn (SaleBanner $banner): bool => (int) $banner->sale_campaign_id === $campaignId
            );
            if ($linked instanceof SaleBanner) {
                return $this->payload($linked);
            }

            $placements = array_values((array) ($candidate['placements'] ?? []));
            if (! in_array($placement, $placements, true)) {
                continue;
            }

            $direct = $this->normalizeExternalPayload(
                is_array($candidate['campaign_banner'] ?? null) ? $candidate['campaign_banner'] : null
            );
            if ($direct !== null) {
                return $direct;
            }
        }

        $global = $banners->first(fn (SaleBanner $banner): bool => $banner->sale_campaign_id === null);

        return $global instanceof SaleBanner ? $this->payload($global) : null;
    }

    /**
     * Compatibility resolver retained for callers that resolve one Campaign explicitly.
     *
     * @param  array<string, mixed>|null  $campaignFallback
     * @return array<string, mixed>|null
     */
    public function resolveForCampaignPlacement(
        SaleCampaign|int|null $campaign,
        string $placement,
        ?array $campaignFallback = null
    ): ?array {
        $banners = $this->activeModelsForPlacement($placement);
        $campaignId = $campaign instanceof SaleCampaign ? (int) $campaign->id : (is_int($campaign) ? $campaign : null);

        if ($campaignId !== null && $campaignId > 0) {
            $linked = $banners->first(fn (SaleBanner $banner): bool => (int) $banner->sale_campaign_id === $campaignId);
            if ($linked instanceof SaleBanner) {
                return $this->payload($linked);
            }
        }

        $fallback = $this->normalizeExternalPayload($campaignFallback);
        if ($fallback !== null) {
            return $fallback;
        }

        $global = $banners->first(fn (SaleBanner $banner): bool => $banner->sale_campaign_id === null);

        return $global instanceof SaleBanner ? $this->payload($global) : null;
    }

    public function status(SaleBanner $banner, ?CarbonImmutable $nowUtc = null): string
    {
        return $this->schedule->bannerStatus($banner, $nowUtc);
    }

    public function isActiveNow(SaleBanner $banner, ?CarbonImmutable $nowUtc = null): bool
    {
        return $this->schedule->bannerIsActive($banner, $nowUtc);
    }

    private function tableAvailable(): bool
    {
        return $this->tableAvailable ??= Schema::hasTable('sale_banners');
    }

    /** @return Collection<int, SaleBanner> */
    private function activeModelsForPlacement(string $placement): Collection
    {
        if (! in_array($placement, SaleBanner::PLACEMENTS, true) || ! $this->tableAvailable()) {
            return collect();
        }

        return SaleBanner::query()
            ->where('is_active', true)
            ->whereNotNull('desktop_image_path')
            ->whereJsonContains('placements', $placement)
            ->with('campaign')
            ->orderBy('priority')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (SaleBanner $banner): bool => $this->schedule->bannerIsActive($banner))
            ->values();
    }

    /** @return array<string, mixed> */
    private function payload(SaleBanner $banner): array
    {
        $destination = trim((string) $banner->destination_link);
        if (! PublicUrl::isAllowed($destination)) {
            $destination = '/sale';
        }

        return [
            'id' => (int) $banner->id,
            'name' => (string) $banner->name,
            'desktop_image_url' => filled($banner->desktop_image_path)
                ? PublicMedia::storedPathUrl((string) $banner->desktop_image_path)
                : null,
            'mobile_image_url' => filled($banner->mobile_image_path)
                ? PublicMedia::storedPathUrl((string) $banner->mobile_image_path)
                : null,
            'alt_text' => (string) $banner->alt_text,
            'heading' => (string) $banner->heading,
            'cta_label' => (string) $banner->cta_label,
            'destination_link' => $destination,
            'campaign_id' => $banner->sale_campaign_id ? (int) $banner->sale_campaign_id : null,
            'source' => 'banner',
        ];
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array<string, mixed>|null
     */
    private function normalizeExternalPayload(?array $payload): ?array
    {
        if (! is_array($payload)) {
            return null;
        }

        $desktop = trim((string) ($payload['desktop_image_url'] ?? ''));
        if ($desktop === '') {
            return null;
        }

        $destination = trim((string) ($payload['destination_link'] ?? '/sale'));
        if (! PublicUrl::isAllowed($destination)) {
            $destination = '/sale';
        }

        $payload['desktop_image_url'] = $desktop;
        $payload['mobile_image_url'] = filled($payload['mobile_image_url'] ?? null)
            ? trim((string) $payload['mobile_image_url'])
            : null;
        $payload['destination_link'] = $destination;

        return $payload;
    }
}
