<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveSaleBannerRequest;
use App\Models\Product;
use App\Models\SaleBanner;
use App\Models\SaleCampaign;
use App\Models\TimeZone;
use App\Services\Promotions\PromotionScheduleService;
use App\Services\Promotions\SaleBannerService;
use App\Support\PublicMedia;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class SaleBannerController extends Controller
{
    public function __construct(
        private readonly SaleBannerService $bannerService,
        private readonly PromotionScheduleService $schedule,
    ) {
    }

    public function index(Request $request): View
    {
        $banners = SaleBanner::query()
            ->with('campaign')
            ->orderBy('sort_order')
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        $selected = null;
        $creating = $request->boolean('new');
        if (! $creating) {
            $requestedId = (int) $request->query('banner', 0);
            $selected = $requestedId > 0
                ? $banners->firstWhere('id', $requestedId)
                : $banners->first();
        }

        $timeZones = TimeZone::query()->active()->ordered()->get(['id', 'label', 'identifier']);
        $defaultTimezone = (string) ($timeZones->first()?->identifier ?? 'Europe/London');
        $campaigns = SaleCampaign::query()
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'status', 'starts_at', 'ends_at', 'timezone']);

        $previewProducts = Product::query()
            ->published()
            ->with('images')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn (Product $product): array => [
                'name' => (string) $product->name,
                'price' => (float) ($product->base_price ?? 0),
                'currency' => (string) ($product->currency ?: 'GBP'),
                'image' => $product->primaryImageUrl(),
            ])
            ->values();

        return view('admin.promotions.banners.index', [
            'banners' => $banners,
            'selectedBanner' => $selected,
            'creating' => $creating || $selected === null,
            'campaigns' => $campaigns,
            'timeZones' => $timeZones,
            'defaultTimezone' => $defaultTimezone,
            'previewProducts' => $previewProducts,
            'bannerStatuses' => $banners->mapWithKeys(fn (SaleBanner $banner): array => [
                $banner->id => $this->bannerService->status($banner),
            ]),
            'bannerScheduleTimezones' => $banners->mapWithKeys(function (SaleBanner $banner): array {
                $timezone = $banner->inherit_campaign_schedule
                    ? ($banner->campaign?->timezone ?: $banner->timezone)
                    : $banner->timezone;

                return [$banner->id => $this->schedule->normalizeTimezone($timezone)];
            }),
            'initialBanner' => $this->formPayload($selected, $defaultTimezone),
        ]);
    }

    public function store(SaveSaleBannerRequest $request): RedirectResponse
    {
        return $this->persist($request, new SaleBanner(), true);
    }

    public function update(SaveSaleBannerRequest $request, SaleBanner $saleBanner): RedirectResponse
    {
        return $this->persist($request, $saleBanner, false);
    }

    public function destroy(SaleBanner $saleBanner): RedirectResponse
    {
        $paths = array_filter([$saleBanner->desktop_image_path, $saleBanner->mobile_image_path]);
        $saleBanner->delete();
        foreach ($paths as $path) {
            Storage::disk('public')->delete((string) $path);
        }

        return redirect()->route('admin.promotions.banners.index')->with('status', 'Sale banner deleted successfully.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'integer', 'exists:sale_banners,id'],
        ]);

        DB::transaction(function () use ($validated): void {
            foreach (array_values($validated['ids']) as $index => $id) {
                SaleBanner::query()->whereKey((int) $id)->update(['sort_order' => $index + 1]);
            }
        });

        return response()->json(['ok' => true]);
    }

    private function persist(SaveSaleBannerRequest $request, SaleBanner $banner, bool $creating): RedirectResponse
    {
        $data = $request->validated();
        $oldDesktop = $banner->desktop_image_path;
        $oldMobile = $banner->mobile_image_path;
        $stored = [];

        $startsAt = null;
        $endsAt = null;
        if (! $data['inherit_campaign_schedule']) {
            $startsAt = CarbonImmutable::createFromFormat('Y-m-d H:i', $data['start_date'].' '.$data['start_time'], $data['timezone'])->utc();
            $endsAt = CarbonImmutable::createFromFormat('Y-m-d H:i', $data['end_date'].' '.$data['end_time'], $data['timezone'])->utc();
            if ($endsAt->lessThanOrEqualTo($startsAt)) {
                return back()->withErrors(['end_date' => 'The banner end date and time must be after the start date and time.'])->withInput();
            }
        } elseif (empty($data['sale_campaign_id'])) {
            return back()->withErrors(['sale_campaign_id' => 'Select a campaign when inheriting the campaign schedule.'])->withInput();
        }

        try {
            DB::transaction(function () use ($request, $data, $banner, $creating, $startsAt, $endsAt, &$stored): void {
                $userId = auth('admin')->id();
                if ($creating) {
                    $banner->sort_order = ((int) SaleBanner::query()->max('sort_order')) + 1;
                    $banner->created_by = $userId;
                }

                $banner->fill([
                    'sale_campaign_id' => $data['sale_campaign_id'] ?: null,
                    'name' => $data['name'],
                    'alt_text' => $data['alt_text'],
                    'heading' => $data['heading'],
                    'cta_label' => $data['cta_label'],
                    'destination_link' => trim((string) $data['destination_link']),
                    'placements' => array_values($data['placements']),
                    'priority' => (int) $data['priority'],
                    'inherit_campaign_schedule' => (bool) $data['inherit_campaign_schedule'],
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'timezone' => $data['timezone'],
                    'is_active' => true,
                    'updated_by' => $userId,
                ]);
                $banner->save();

                if ($request->hasFile('desktop_image')) {
                    $stored['desktop'] = $request->file('desktop_image')->store("promotions/sale-banners/{$banner->id}", 'public');
                    $banner->desktop_image_path = $stored['desktop'];
                }

                if ($request->hasFile('mobile_image')) {
                    $stored['mobile'] = $request->file('mobile_image')->store("promotions/sale-banners/{$banner->id}", 'public');
                    $banner->mobile_image_path = $stored['mobile'];
                } elseif ($request->boolean('remove_mobile_image')) {
                    $banner->mobile_image_path = null;
                }

                $banner->save();
            });
        } catch (Throwable $exception) {
            foreach ($stored as $path) {
                Storage::disk('public')->delete((string) $path);
            }
            throw $exception;
        }

        if (isset($stored['desktop']) && filled($oldDesktop)) {
            Storage::disk('public')->delete((string) $oldDesktop);
        }
        if ((isset($stored['mobile']) || $request->boolean('remove_mobile_image')) && filled($oldMobile)) {
            Storage::disk('public')->delete((string) $oldMobile);
        }

        return redirect()
            ->route('admin.promotions.banners.index', ['banner' => $banner->id])
            ->with('status', $creating ? 'Sale banner added successfully.' : 'Sale banner updated successfully.');
    }

    /** @return array<string, mixed> */
    private function formPayload(?SaleBanner $banner, string $defaultTimezone): array
    {
        $requestedTimezone = (string) old('timezone', $banner?->timezone ?: $defaultTimezone);
        $timezone = $this->schedule->normalizeTimezone($requestedTimezone ?: $defaultTimezone);
        $now = CarbonImmutable::now($timezone);
        $startsAt = $banner?->starts_at?->copy()->timezone($timezone);
        $endsAt = $banner?->ends_at?->copy()->timezone($timezone);

        return [
            'id' => $banner?->id,
            'name' => (string) old('name', $banner?->name ?? ''),
            'campaignId' => (string) old('sale_campaign_id', $banner?->sale_campaign_id ?? ''),
            'desktopImageUrl' => $banner && filled($banner->desktop_image_path) ? PublicMedia::storedPathUrl((string) $banner->desktop_image_path) : '',
            'mobileImageUrl' => $banner && filled($banner->mobile_image_path) ? PublicMedia::storedPathUrl((string) $banner->mobile_image_path) : '',
            'altText' => (string) old('alt_text', $banner?->alt_text ?? ''),
            'heading' => (string) old('heading', $banner?->heading ?? ''),
            'ctaLabel' => (string) old('cta_label', $banner?->cta_label ?? ''),
            'destinationLink' => (string) old('destination_link', $banner?->destination_link ?? '/sale'),
            'placements' => array_values((array) old('placements', $banner?->placements ?? [SaleBanner::PLACEMENT_SALE_TOP])),
            'priority' => (int) old('priority', $banner?->priority ?? 1),
            'inheritSchedule' => filter_var(old('inherit_campaign_schedule', $banner?->inherit_campaign_schedule ?? true), FILTER_VALIDATE_BOOLEAN),
            'timezone' => $timezone,
            'startDate' => (string) old('start_date', $startsAt?->format('Y-m-d') ?? $now->format('Y-m-d')),
            'startTime' => (string) old('start_time', $startsAt?->format('H:i') ?? '00:00'),
            'endDate' => (string) old('end_date', $endsAt?->format('Y-m-d') ?? $now->addMonth()->format('Y-m-d')),
            'endTime' => (string) old('end_time', $endsAt?->format('H:i') ?? '23:59'),
        ];
    }
}
