<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSaleCampaignRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\SaleCampaign;
use App\Models\TimeZone;
use App\Services\Promotions\PromotionScheduleService;
use App\Services\Promotions\SaleCampaignCodeGenerator;
use App\Support\PromotionBannerPlacement;
use App\Support\PublicMedia;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class SaleCampaignController extends Controller
{
    public function __construct(
        private readonly SaleCampaignCodeGenerator $codeGenerator,
        private readonly PromotionScheduleService $schedule,
    ) {
    }

    public function index(Request $request): View
    {
        $queryText = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));

        $campaigns = SaleCampaign::query()
            ->when($queryText !== '', function ($query) use ($queryText): void {
                $query->where(function ($search) use ($queryText): void {
                    $search->where('name', 'like', '%'.$queryText.'%')
                        ->orWhere('internal_code', 'like', '%'.$queryText.'%');
                });
            })
            ->when(in_array($status, ['draft', 'live'], true), fn ($query) => $query->where('status', $status))
            ->withCount(['products', 'categories', 'excludedProducts'])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $campaignStatuses = $campaigns->getCollection()->mapWithKeys(
            fn (SaleCampaign $campaign): array => [$campaign->id => $this->schedule->campaignStatus($campaign)]
        );
        $campaignDisplayTimezones = $campaigns->getCollection()->mapWithKeys(
            fn (SaleCampaign $campaign): array => [$campaign->id => $this->schedule->normalizeTimezone($campaign->timezone)]
        );

        return view('admin.promotions.sales.index', [
            'campaigns' => $campaigns,
            'filters' => ['q' => $queryText, 'status' => $status],
            'campaignStatuses' => $campaignStatuses,
            'campaignDisplayTimezones' => $campaignDisplayTimezones,
        ]);
    }

    public function create(): View
    {
        return $this->formView();
    }

    public function edit(SaleCampaign $saleCampaign): View
    {
        return $this->formView($saleCampaign);
    }

    public function options(Request $request): JsonResponse
    {
        $type = (string) $request->query('type', 'products');
        $queryText = trim((string) $request->query('q', ''));

        if (in_array($type, ['products', 'excluded_products'], true)) {
            $products = Product::query()
                ->published()
                ->with(['images', 'categories.ancestors'])
                ->when($queryText !== '', function ($query) use ($queryText): void {
                    $query->where(function ($search) use ($queryText): void {
                        $search->where('name', 'like', '%'.$queryText.'%')
                            ->orWhere('sku', 'like', '%'.$queryText.'%');
                    });
                })
                ->orderBy('name')
                ->limit(20)
                ->get()
                ->map(fn (Product $product): array => $this->productOption($product))
                ->values();

            return response()->json(['options' => $products]);
        }

        if (! in_array($type, ['parent_categories', 'subcategories', 'product_categories'], true)) {
            return response()->json(['options' => []]);
        }

        $categories = Category::query()
            ->active()
            ->with('ancestors')
            ->when($type === 'parent_categories', fn ($query) => $query->whereNull('parent_id'))
            ->when($type === 'subcategories', fn ($query) => $query->whereNotNull('parent_id')->whereHas('children'))
            ->when($type === 'product_categories', fn ($query) => $query->whereDoesntHave('children'))
            ->when($queryText !== '', fn ($query) => $query->where('name', 'like', '%'.$queryText.'%'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn (Category $category): array => $this->categoryOption($category))
            ->values();

        return response()->json(['options' => $categories]);
    }

    public function store(StoreSaleCampaignRequest $request): RedirectResponse
    {
        return $this->persist($request, new SaleCampaign(), true);
    }

    public function update(StoreSaleCampaignRequest $request, SaleCampaign $saleCampaign): RedirectResponse
    {
        return $this->persist($request, $saleCampaign, false);
    }

    private function formView(?SaleCampaign $campaign = null): View
    {
        if ($campaign) {
            $campaign->loadMissing(['categories:id', 'products:id', 'excludedProducts:id']);
        }

        $timeZones = TimeZone::query()
            ->active()
            ->ordered()
            ->get(['id', 'label', 'identifier']);

        $availableIdentifiers = $timeZones->pluck('identifier')->all();
        $preferredTimezone = (string) old('timezone', $campaign?->timezone ?: ($timeZones->first()?->identifier ?? ''));
        $timezone = in_array($preferredTimezone, $availableIdentifiers, true)
            ? $preferredTimezone
            : (string) ($timeZones->first()?->identifier ?? '');

        $now = CarbonImmutable::now($timezone !== '' ? $timezone : 'UTC');
        $campaignStart = $campaign?->starts_at?->copy()->timezone($timezone !== '' ? $timezone : 'UTC');
        $campaignEnd = $campaign?->ends_at?->copy()->timezone($timezone !== '' ? $timezone : 'UTC');
        $appliesTo = (string) old('applies_to', $campaign?->applies_to ?? 'subcategories');

        $defaultTargetIds = match ($appliesTo) {
            'products' => $campaign?->products->pluck('id')->map(fn ($id): int => (int) $id)->all() ?? [],
            'all' => [],
            default => $campaign?->categories->pluck('id')->map(fn ($id): int => (int) $id)->all() ?? [],
        };

        $targetIds = array_values(array_filter(array_map('intval', (array) old('target_ids', $defaultTargetIds))));
        $excludedIds = array_values(array_filter(array_map('intval', (array) old(
            'excluded_product_ids',
            $campaign?->excludedProducts->pluck('id')->map(fn ($id): int => (int) $id)->all() ?? []
        ))));
        $placementsWerePreviouslySubmitted = filter_var(
            old('banner_placements_present', false),
            FILTER_VALIDATE_BOOLEAN
        );
        $initialBannerPlacements = $placementsWerePreviouslySubmitted
            ? array_values((array) old('banner_placements', []))
            : array_values((array) ($campaign?->banner_placements ?? [PromotionBannerPlacement::SALE_TOP]));

        $initialCampaign = [
            'campaignName' => (string) old('campaign_name', $campaign?->name ?? ''),
            'internalCode' => (string) ($campaign?->internal_code ?? ''),
            'status' => (string) old('status', $campaign?->status ?? 'draft'),
            'discountType' => (string) old('discount_type', $campaign?->discount_type ?? 'percentage'),
            'discountValue' => (float) old('discount_value', $campaign?->discount_value ?? 15),
            'maximumDiscount' => old('maximum_discount', $campaign?->maximum_discount ?? ''),
            'startDate' => (string) old('start_date', $campaignStart?->format('Y-m-d') ?? $now->format('Y-m-d')),
            'startTime' => (string) old('start_time', $campaignStart?->format('H:i') ?? '00:00'),
            'endDate' => (string) old('end_date', $campaignEnd?->format('Y-m-d') ?? $now->addMonth()->format('Y-m-d')),
            'endTime' => (string) old('end_time', $campaignEnd?->format('H:i') ?? '23:59'),
            'timezone' => $timezone,
            'repeatWeekdays' => filter_var(old('repeat_weekdays', $campaign?->repeat_weekdays ?? false), FILTER_VALIDATE_BOOLEAN),
            'weekdays' => array_values((array) old('weekdays', $campaign?->weekdays ?? [])),
            'appliesTo' => $appliesTo,
            'selectedTargets' => $this->resolveTargetOptions($appliesTo, $targetIds),
            'excludedProducts' => $this->resolveProductOptions($excludedIds),
            'showSaleBadge' => filter_var(old('show_sale_badge', $campaign?->show_sale_badge ?? true), FILTER_VALIDATE_BOOLEAN),
            'showSalePage' => filter_var(old('show_sale_page', $campaign?->show_sale_page ?? true), FILTER_VALIDATE_BOOLEAN),
            'priority' => (int) old('priority', $campaign?->priority ?? 1),
            'optionsUrl' => route('admin.promotions.sales.options'),
            'bannerDesktopPreviewUrl' => $campaign && filled($campaign->banner_image_path)
                ? PublicMedia::storedPathUrl((string) $campaign->banner_image_path)
                : '',
            'bannerDesktopFileName' => $campaign && filled($campaign->banner_image_path)
                ? basename((string) $campaign->banner_image_path)
                : '',
            'bannerMobilePreviewUrl' => $campaign && filled($campaign->banner_mobile_image_path)
                ? PublicMedia::storedPathUrl((string) $campaign->banner_mobile_image_path)
                : '',
            'bannerMobileFileName' => $campaign && filled($campaign->banner_mobile_image_path)
                ? basename((string) $campaign->banner_mobile_image_path)
                : '',
            'bannerPlacements' => $initialBannerPlacements,
            'bannerHeading' => (string) old('banner_heading', $campaign?->banner_heading ?: ($campaign?->name ?? '')),
            'bannerAltText' => (string) old('banner_alt_text', $campaign?->banner_alt_text ?: ($campaign?->name ? $campaign->name.' banner' : '')),
            'bannerCtaLabel' => (string) old('banner_cta_label', $campaign?->banner_cta_label ?: 'Shop Sale'),
            'bannerDestinationLink' => (string) old('banner_destination_link', $campaign?->banner_destination_link ?: '/sale'),
        ];

        return view('admin.promotions.sales.create', [
            'initialCampaign' => $initialCampaign,
            'timeZones' => $timeZones,
            'editingCampaign' => $campaign,
        ]);
    }

    private function persist(StoreSaleCampaignRequest $request, SaleCampaign $campaign, bool $creating): RedirectResponse
    {
        $data = $request->validated();
        $timezone = (string) $data['timezone'];
        $startsAt = CarbonImmutable::createFromFormat(
            'Y-m-d H:i',
            $data['start_date'].' '.$data['start_time'],
            $timezone
        )->utc();
        $endsAt = CarbonImmutable::createFromFormat(
            'Y-m-d H:i',
            $data['end_date'].' '.$data['end_time'],
            $timezone
        )->utc();

        $status = match ($data['submit_action'] ?? null) {
            'publish' => 'live',
            'draft' => 'draft',
            default => $data['status'],
        };

        $userId = auth('admin')->id();
        $oldDesktopPath = $campaign->banner_image_path;
        $oldMobilePath = $campaign->banner_mobile_image_path;
        $storedPaths = [];
        $removeDesktop = (bool) ($data['remove_banner_image'] ?? false);
        $removeMobile = (bool) ($data['remove_banner_mobile_image'] ?? false);
        $placementsWereSubmitted = (bool) ($data['banner_placements_present'] ?? false);
        $bannerPlacements = $placementsWereSubmitted
            ? array_values($data['banner_placements'] ?? [])
            : array_values((array) ($campaign->banner_placements ?? []));

        if (! $placementsWereSubmitted && $creating && $request->hasFile('banner_image')) {
            $bannerPlacements = [PromotionBannerPlacement::SALE_TOP];
        }

        $internalCode = trim((string) $campaign->internal_code);
        if ($internalCode === '') {
            $internalCode = $this->codeGenerator->generate((string) $data['campaign_name']);
        }

        try {
            DB::transaction(function () use (
                $request,
                $data,
                $startsAt,
                $endsAt,
                $status,
                $userId,
                $campaign,
                $creating,
                $removeDesktop,
                $removeMobile,
                $bannerPlacements,
                $internalCode,
                &$storedPaths
            ): void {
                if ($creating) {
                    $campaign->created_by = $userId;
                }

                $campaign->fill([
                    'name' => $data['campaign_name'],
                    'internal_code' => $internalCode,
                    'status' => $status,
                    'discount_type' => $data['discount_type'],
                    'discount_value' => $data['discount_value'],
                    'maximum_discount' => $data['maximum_discount'] ?: null,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'timezone' => $data['timezone'],
                    'repeat_weekdays' => (bool) $data['repeat_weekdays'],
                    'weekdays' => array_values($data['weekdays'] ?? []),
                    'applies_to' => $data['applies_to'],
                    'show_sale_badge' => (bool) $data['show_sale_badge'],
                    'show_sale_page' => (bool) $data['show_sale_page'],
                    'priority' => (int) $data['priority'],
                    'homepage_slide_id' => null,
                    'banner_placements' => $bannerPlacements,
                    'banner_heading' => ($data['banner_heading'] ?? null) ?: $data['campaign_name'],
                    'banner_alt_text' => ($data['banner_alt_text'] ?? null) ?: $data['campaign_name'].' banner',
                    'banner_cta_label' => ($data['banner_cta_label'] ?? null) ?: 'Shop Sale',
                    'banner_destination_link' => ($data['banner_destination_link'] ?? null) ?: '/sale',
                    'updated_by' => $userId,
                ]);
                $campaign->save();

                if ($request->hasFile('banner_image')) {
                    $storedPaths['desktop'] = $request->file('banner_image')->store("promotions/sale-campaigns/{$campaign->id}", 'public');
                    $campaign->banner_image_path = $storedPaths['desktop'];
                } elseif ($removeDesktop) {
                    $campaign->banner_image_path = null;
                }

                if ($request->hasFile('banner_mobile_image')) {
                    $storedPaths['mobile'] = $request->file('banner_mobile_image')->store("promotions/sale-campaigns/{$campaign->id}", 'public');
                    $campaign->banner_mobile_image_path = $storedPaths['mobile'];
                } elseif ($removeMobile) {
                    $campaign->banner_mobile_image_path = null;
                }

                $campaign->save();

                $targetIds = array_values($data['target_ids'] ?? []);
                if ($data['applies_to'] === 'products') {
                    $campaign->products()->sync($targetIds);
                    $campaign->categories()->sync([]);
                } elseif ($data['applies_to'] === 'all') {
                    $campaign->products()->sync([]);
                    $campaign->categories()->sync([]);
                } else {
                    $campaign->categories()->sync($targetIds);
                    $campaign->products()->sync([]);
                }

                $campaign->excludedProducts()->sync(array_values($data['excluded_product_ids'] ?? []));
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete((string) $path);
            }

            throw $exception;
        }

        if ((isset($storedPaths['desktop']) || $removeDesktop) && filled($oldDesktopPath) && $oldDesktopPath !== ($storedPaths['desktop'] ?? null)) {
            Storage::disk('public')->delete((string) $oldDesktopPath);
        }
        if ((isset($storedPaths['mobile']) || $removeMobile) && filled($oldMobilePath) && $oldMobilePath !== ($storedPaths['mobile'] ?? null)) {
            Storage::disk('public')->delete((string) $oldMobilePath);
        }

        $message = $status === 'live'
            ? ($creating ? 'Sale campaign published successfully.' : 'Sale campaign updated and published successfully.')
            : ($creating ? 'Sale campaign saved as draft.' : 'Sale campaign updated as draft.');

        return redirect()
            ->route('admin.promotions.sales.index')
            ->with('status', $message.' Campaign #'.$campaign->id.'.');
    }

    private function resolveTargetOptions(string $appliesTo, array $ids): array
    {
        if ($ids === [] || $appliesTo === 'all') {
            return [];
        }

        if ($appliesTo === 'products') {
            return $this->resolveProductOptions($ids);
        }

        return Category::query()
            ->whereIn('id', $ids)
            ->with('ancestors')
            ->get()
            ->sortBy(fn (Category $category): int => array_search($category->id, $ids, true))
            ->map(fn (Category $category): array => $this->categoryOption($category))
            ->values()
            ->all();
    }

    private function resolveProductOptions(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return Product::query()
            ->whereIn('id', $ids)
            ->with(['images', 'categories.ancestors'])
            ->get()
            ->sortBy(fn (Product $product): int => array_search($product->id, $ids, true))
            ->map(fn (Product $product): array => $this->productOption($product))
            ->values()
            ->all();
    }

    private function categoryOption(Category $category): array
    {
        $path = $category->ancestors
            ->pluck('name')
            ->push($category->name)
            ->filter()
            ->implode(' > ');

        return [
            'id' => (int) $category->id,
            'name' => (string) $category->name,
            'path' => $path ?: (string) $category->name,
            'type' => 'category',
        ];
    }

    private function productOption(Product $product): array
    {
        $category = $product->categories->first();
        $categoryPath = $category
            ? $category->ancestors->pluck('name')->push($category->name)->filter()->implode(' > ')
            : '';

        $image = $product->images->firstWhere('is_primary', true) ?? $product->images->first();

        return [
            'id' => (int) $product->id,
            'name' => (string) $product->name,
            'path' => trim($categoryPath.($categoryPath !== '' ? ' > ' : '').$product->name),
            'type' => 'product',
            'sku' => (string) ($product->sku ?? ''),
            'price' => (float) ($product->base_price ?? 0),
            'currency' => (string) ($product->currency ?: 'USD'),
            'imageUrl' => $image?->publicUrl(),
        ];
    }
}
