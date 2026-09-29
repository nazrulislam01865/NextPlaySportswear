<?php

namespace App\Services\Promotions;

use App\Models\Product;
use App\Models\SaleCampaign;
use App\Support\PromotionBannerPlacement;
use App\Support\PublicMedia;
use App\Support\PublicUrl;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SaleCampaignService
{
    public function __construct(private readonly PromotionScheduleService $schedule)
    {
    }

    /** @var Collection<int, array<string, mixed>>|null */
    private ?Collection $activeCampaigns = null;

    /** @var array<int, int>|null */
    private ?array $salePageProductIds = null;

    /** @var array<int, array{campaign: array<string, mixed>, product_ids: array<int, int>}>|null */
    private ?array $salePageCampaignGroups = null;

    /**
     * Apply the currently eligible sale campaign to an arbitrary product price.
     *
     * The product array only needs an id and, for category campaigns, category_ids.
     * The returned context also reports whether the product belongs on the Sale page.
     *
     * @param  array<string, mixed>  $product
     * @return array<string, mixed>
     */
    public function contextForProduct(array $product, float $originalPrice): array
    {
        $eligible = $this->eligibleCampaignsForProduct($product);
        $best = null;

        foreach ($eligible as $campaign) {
            $pricing = $this->pricingForCampaign($campaign, $originalPrice);

            if ($pricing['discount_amount'] <= 0) {
                continue;
            }

            if ($best === null
                || $pricing['discount_amount'] > $best['discount_amount']
                || (
                    abs($pricing['discount_amount'] - $best['discount_amount']) < 0.00001
                    && (int) $campaign['priority'] > (int) $best['priority']
                )) {
                $best = array_merge($campaign, $pricing);
            }
        }

        return [
            'best' => $best,
            'eligible_campaigns' => $eligible->map(fn (array $campaign): array => $this->publicCampaignPayload($campaign))->values()->all(),
            'sale_page_eligible' => $eligible->contains(fn (array $campaign): bool => (bool) $campaign['show_sale_page']),
        ];
    }

    /**
     * @param  array<string, mixed>  $product
     * @return array<string, mixed>|null
     */
    public function pricingForProduct(array $product, float $originalPrice): ?array
    {
        return $this->contextForProduct($product, $originalPrice)['best'] ?? null;
    }

    /**
     * Campaigns that are active now and are allowed to appear on the Sale page.
     *
     * @return array<int, array<string, mixed>>
     */
    public function salePageCampaigns(): array
    {
        return $this->activeCampaigns()
            ->filter(fn (array $campaign): bool => (bool) $campaign['show_sale_page'])
            ->map(fn (array $campaign): array => $this->publicCampaignPayload($campaign))
            ->values()
            ->all();
    }

    public function hasActiveSalePageCampaign(): bool
    {
        return $this->activeCampaigns()
            ->contains(fn (array $campaign): bool => (bool) $campaign['show_sale_page']);
    }

    /**
     * Ordered active Campaign candidates for one shared storefront banner slot.
     *
     * @return array<int, array<string, mixed>>
     */
    public function bannerCandidates(string $placement, ?int $categoryId = null): array
    {
        if (! in_array($placement, PromotionBannerPlacement::ALL, true)) {
            return [];
        }

        $campaigns = $this->activeCampaigns();

        if (in_array($placement, [PromotionBannerPlacement::SALE_TOP, PromotionBannerPlacement::SALE_MIDDLE], true)) {
            $campaigns = $campaigns->filter(fn (array $campaign): bool => (bool) $campaign['show_sale_page']);
        } elseif ($placement === PromotionBannerPlacement::CATEGORY_TOP) {
            if ($categoryId === null || $categoryId <= 0) {
                return [];
            }

            $campaigns = $campaigns->filter(
                fn (array $campaign): bool => $this->campaignAppliesToCategory($campaign, $categoryId)
            );
        }

        return $campaigns
            ->map(fn (array $campaign): array => $this->publicCampaignPayload($campaign))
            ->values()
            ->all();
    }

    /** @return array<int, int> */
    public function salePageProductIds(): array
    {
        if ($this->salePageProductIds !== null) {
            return $this->salePageProductIds;
        }

        $ids = collect($this->salePageCampaignGroups())
            ->flatMap(fn (array $group): array => $group['product_ids']);

        return $this->salePageProductIds = $ids
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Return each currently-active Sale-page campaign with only the products that
     * belong to that campaign. Keeping this grouping here prevents the Sale page
     * from mixing banners for one campaign with products from another campaign.
     *
     * @return array<int, array{campaign: array<string, mixed>, product_ids: array<int, int>}>
     */
    public function salePageCampaignGroups(): array
    {
        if ($this->salePageCampaignGroups !== null) {
            return $this->salePageCampaignGroups;
        }

        if (! Schema::hasTable('products')) {
            return $this->salePageCampaignGroups = [];
        }

        return $this->salePageCampaignGroups = $this->activeCampaigns()
            ->filter(fn (array $campaign): bool => (bool) $campaign['show_sale_page'])
            ->map(function (array $campaign): array {
                return [
                    'campaign' => $this->publicCampaignPayload($campaign),
                    'product_ids' => $this->productIdsForCampaign($campaign),
                ];
            })
            ->filter(fn (array $group): bool => $group['product_ids'] !== [])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $campaign
     * @return array<int, int>
     */
    private function productIdsForCampaign(array $campaign): array
    {
        $query = Product::query()->published()->select('products.id');

        if ($campaign['applies_to'] === 'products') {
            $productIds = array_values(array_filter((array) $campaign['product_ids']));
            if ($productIds === []) {
                return [];
            }

            $query->whereIn('products.id', $productIds);
        } elseif (in_array($campaign['applies_to'], ['parent_categories', 'subcategories', 'product_categories'], true)) {
            $categoryIds = array_values(array_filter((array) $campaign['expanded_category_ids']));
            if ($categoryIds === []) {
                return [];
            }

            $query->where(function ($builder) use ($categoryIds): void {
                $builder->whereIn('products.category_id', $categoryIds)
                    ->orWhereIn('products.subcategory_id', $categoryIds);

                if (Schema::hasTable('category_product')) {
                    $builder->orWhereHas('categories', fn ($categoryQuery) => $categoryQuery
                        ->whereIn('categories.id', $categoryIds));
                }
            });
        } elseif ($campaign['applies_to'] !== 'all') {
            return [];
        }

        $excluded = array_values(array_filter((array) $campaign['excluded_product_ids']));
        if ($excluded !== []) {
            $query->whereNotIn('products.id', $excluded);
        }

        return $query->pluck('products.id')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $product
     * @return Collection<int, array<string, mixed>>
     */
    private function eligibleCampaignsForProduct(array $product): Collection
    {
        $productId = (int) ($product['id'] ?? 0);
        if ($productId <= 0) {
            return collect();
        }

        $categoryIds = collect((array) ($product['category_ids'] ?? []))
            ->push($product['category_id'] ?? null)
            ->push($product['subcategory_id'] ?? null)
            ->filter(fn ($id): bool => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        return $this->activeCampaigns()->filter(function (array $campaign) use ($productId, $categoryIds): bool {
            if (in_array($productId, $campaign['excluded_product_ids'], true)) {
                return false;
            }

            return match ($campaign['applies_to']) {
                'all' => true,
                'products' => in_array($productId, $campaign['product_ids'], true),
                'parent_categories', 'subcategories', 'product_categories' => array_intersect(
                    $categoryIds,
                    $campaign['expanded_category_ids']
                ) !== [],
                default => false,
            };
        })->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function activeCampaigns(): Collection
    {
        if ($this->activeCampaigns instanceof Collection) {
            return $this->activeCampaigns;
        }

        if (! Schema::hasTable('sale_campaigns')) {
            return $this->activeCampaigns = collect();
        }

        $campaigns = SaleCampaign::query()
            ->where('status', 'live')
            ->with([
                'categories:id',
                'products:id',
                'excludedProducts:id',
            ])
            ->orderByDesc('priority')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (SaleCampaign $campaign): bool => $this->schedule->campaignIsActive($campaign))
            ->values();

        $targetCategoryIds = $campaigns
            ->flatMap(fn (SaleCampaign $campaign) => $campaign->categories->pluck('id'))
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $descendantsByAncestor = collect();
        if ($targetCategoryIds->isNotEmpty() && Schema::hasTable('category_closure')) {
            $descendantsByAncestor = DB::table('category_closure')
                ->whereIn('ancestor_id', $targetCategoryIds->all())
                ->get(['ancestor_id', 'descendant_id'])
                ->groupBy(fn ($row): int => (int) $row->ancestor_id)
                ->map(fn (Collection $rows): array => $rows
                    ->pluck('descendant_id')
                    ->map(fn ($id): int => (int) $id)
                    ->unique()
                    ->values()
                    ->all());
        }

        return $this->activeCampaigns = $campaigns->map(function (SaleCampaign $campaign) use ($descendantsByAncestor): array {
            $categoryIds = $campaign->categories->pluck('id')->map(fn ($id): int => (int) $id)->values();
            $expandedCategoryIds = $categoryIds
                ->flatMap(function (int $categoryId) use ($descendantsByAncestor): array {
                    return array_values(array_unique(array_merge(
                        [$categoryId],
                        (array) $descendantsByAncestor->get($categoryId, [])
                    )));
                })
                ->unique()
                ->values()
                ->all();

            return [
                'id' => (int) $campaign->id,
                'name' => (string) $campaign->name,
                'discount_type' => (string) $campaign->discount_type,
                'discount_value' => (float) $campaign->discount_value,
                'maximum_discount' => $campaign->maximum_discount !== null ? (float) $campaign->maximum_discount : null,
                'applies_to' => (string) $campaign->applies_to,
                'show_sale_badge' => (bool) $campaign->show_sale_badge,
                'show_sale_page' => (bool) $campaign->show_sale_page,
                'priority' => (int) $campaign->priority,
                'product_ids' => $campaign->products->pluck('id')->map(fn ($id): int => (int) $id)->values()->all(),
                'excluded_product_ids' => $campaign->excludedProducts->pluck('id')->map(fn ($id): int => (int) $id)->values()->all(),
                'expanded_category_ids' => $expandedCategoryIds,
                'banner_image_url' => filled($campaign->banner_image_path)
                    ? PublicMedia::storedPathUrl((string) $campaign->banner_image_path)
                    : null,
                'banner_mobile_image_url' => filled($campaign->banner_mobile_image_path)
                    ? PublicMedia::storedPathUrl((string) $campaign->banner_mobile_image_path)
                    : null,
                'banner_placements' => array_values(array_filter(
                    (array) $campaign->banner_placements,
                    fn ($placement): bool => in_array($placement, PromotionBannerPlacement::ALL, true)
                )),
                'banner_heading' => trim((string) $campaign->banner_heading),
                'banner_alt_text' => trim((string) $campaign->banner_alt_text),
                'banner_cta_label' => trim((string) $campaign->banner_cta_label),
                'banner_destination_link' => trim((string) $campaign->banner_destination_link),
                'starts_at' => $campaign->starts_at?->copy()->timezone('UTC')->toIso8601String(),
                'ends_at' => $campaign->ends_at?->copy()->timezone('UTC')->toIso8601String(),
                'timezone' => (string) $campaign->timezone,
            ];
        })->values();
    }

    /** @param array<string, mixed> $campaign */
    private function campaignAppliesToCategory(array $campaign, int $categoryId): bool
    {
        if ($campaign['applies_to'] === 'all') {
            return true;
        }

        if (in_array($campaign['applies_to'], ['parent_categories', 'subcategories', 'product_categories'], true)) {
            return in_array($categoryId, (array) $campaign['expanded_category_ids'], true);
        }

        if ($campaign['applies_to'] !== 'products') {
            return false;
        }

        $productIds = array_values(array_diff(
            (array) $campaign['product_ids'],
            (array) $campaign['excluded_product_ids']
        ));
        if ($productIds === [] || ! Schema::hasTable('products')) {
            return false;
        }

        $categoryIds = [$categoryId];
        if (Schema::hasTable('category_closure')) {
            $categoryIds = DB::table('category_closure')
                ->where('ancestor_id', $categoryId)
                ->pluck('descendant_id')
                ->push($categoryId)
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        return Product::query()
            ->whereIn('products.id', $productIds)
            ->where(function ($query) use ($categoryIds): void {
                $query->whereIn('products.category_id', $categoryIds)
                    ->orWhereIn('products.subcategory_id', $categoryIds);

                if (Schema::hasTable('category_product')) {
                    $query->orWhereHas('categories', fn ($categoryQuery) => $categoryQuery
                        ->whereIn('categories.id', $categoryIds));
                }
            })
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $campaign
     * @return array{sale_price: float, discount_amount: float, discount_percentage: int}
     */
    private function pricingForCampaign(array $campaign, float $originalPrice): array
    {
        $originalPrice = max(0, round($originalPrice, 2));
        $discountValue = max(0, (float) $campaign['discount_value']);

        $discount = $campaign['discount_type'] === 'fixed'
            ? $discountValue
            : $originalPrice * ($discountValue / 100);

        $maximumDiscount = $campaign['maximum_discount'];
        if (is_numeric($maximumDiscount) && (float) $maximumDiscount > 0) {
            $discount = min($discount, (float) $maximumDiscount);
        }

        $discount = min($originalPrice, max(0, $discount));
        $salePrice = max(0, $originalPrice - $discount);
        $percentage = $originalPrice > 0
            ? max(1, min(100, (int) round(($discount / $originalPrice) * 100)))
            : 0;

        return [
            'sale_price' => round($salePrice, 2),
            'discount_amount' => round($discount, 2),
            'discount_percentage' => $percentage,
        ];
    }

    /**
     * Normalize the image uploaded directly on a campaign into the exact payload
     * consumed by the shared storefront sale-banner component.
     *
     * @param  array<string, mixed>  $campaign
     * @return array<string, mixed>|null
     */
    private function campaignBannerPayload(array $campaign): ?array
    {
        $desktopImage = trim((string) ($campaign['banner_image_url'] ?? ''));
        if ($desktopImage === '') {
            return null;
        }

        $campaignName = trim((string) ($campaign['name'] ?? ''));
        $heading = trim((string) ($campaign['banner_heading'] ?? '')) ?: $campaignName;
        $altText = trim((string) ($campaign['banner_alt_text'] ?? ''))
            ?: ($campaignName !== '' ? $campaignName.' banner' : 'Sale campaign banner');
        $ctaLabel = trim((string) ($campaign['banner_cta_label'] ?? '')) ?: 'Shop Sale';
        $destination = trim((string) ($campaign['banner_destination_link'] ?? '')) ?: '/sale';

        if (! PublicUrl::isAllowed($destination)) {
            $destination = '/sale';
        }

        return [
            'id' => (int) ($campaign['id'] ?? 0),
            'name' => $campaignName !== '' ? $campaignName.' campaign banner' : 'Campaign banner',
            'desktop_image_url' => $desktopImage,
            'mobile_image_url' => filled($campaign['banner_mobile_image_url'] ?? null)
                ? trim((string) $campaign['banner_mobile_image_url'])
                : null,
            'placements' => array_values((array) ($campaign['banner_placements'] ?? [])),
            'alt_text' => $altText,
            'heading' => $heading,
            'cta_label' => $ctaLabel,
            'destination_link' => $destination,
            'campaign_id' => (int) ($campaign['id'] ?? 0),
            'source' => 'campaign',
        ];
    }

    /**
     * @param  array<string, mixed>  $campaign
     * @return array<string, mixed>
     */
    private function publicCampaignPayload(array $campaign): array
    {
        return [
            'id' => (int) $campaign['id'],
            'name' => (string) $campaign['name'],
            'discount_type' => (string) $campaign['discount_type'],
            'discount_value' => (float) $campaign['discount_value'],
            'maximum_discount' => $campaign['maximum_discount'],
            'show_sale_badge' => (bool) $campaign['show_sale_badge'],
            'show_sale_page' => (bool) $campaign['show_sale_page'],
            'priority' => (int) $campaign['priority'],
            'banner_image_url' => $campaign['banner_image_url'],
            'desktop_image_url' => $campaign['banner_image_url'],
            'mobile_image_url' => $campaign['banner_mobile_image_url'] ?? null,
            'placements' => array_values((array) ($campaign['banner_placements'] ?? [])),
            'campaign_banner' => $this->campaignBannerPayload($campaign),
            'starts_at' => $campaign['starts_at'],
            'ends_at' => $campaign['ends_at'],
            'timezone' => (string) $campaign['timezone'],
        ];
    }
}
