<?php

namespace App\Services\Promotions;

use App\Models\Product;
use App\Models\SaleCampaign;
use App\Support\PublicMedia;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SaleCampaignService
{
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

        $nowUtc = CarbonImmutable::now('UTC');

        $campaigns = SaleCampaign::query()
            ->where('status', 'live')
            ->where('starts_at', '<=', $nowUtc)
            ->where('ends_at', '>=', $nowUtc)
            ->with([
                'categories:id',
                'products:id',
                'excludedProducts:id',
            ])
            ->orderByDesc('priority')
            ->orderByDesc('id')
            ->get()
            ->filter(function (SaleCampaign $campaign) use ($nowUtc): bool {
                if (! $campaign->repeat_weekdays) {
                    return true;
                }

                $weekdays = collect((array) $campaign->weekdays)
                    ->map(fn ($day): string => trim((string) $day))
                    ->filter()
                    ->values()
                    ->all();

                if ($weekdays === []) {
                    return false;
                }

                try {
                    $localDay = $nowUtc->setTimezone($campaign->timezone ?: 'UTC')->format('D');
                } catch (\Throwable) {
                    $localDay = $nowUtc->format('D');
                }

                return in_array($localDay, $weekdays, true);
            })
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
                'starts_at' => $campaign->starts_at?->copy()->timezone('UTC')->toIso8601String(),
                'ends_at' => $campaign->ends_at?->copy()->timezone('UTC')->toIso8601String(),
                'timezone' => (string) $campaign->timezone,
            ];
        })->values();
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
            'starts_at' => $campaign['starts_at'],
            'ends_at' => $campaign['ends_at'],
            'timezone' => (string) $campaign['timezone'],
        ];
    }
}
