<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProductActivityController extends Controller
{
    public function track(Request $request): JsonResponse
    {
        $requestedProductIds = collect((array) $request->input('product_ids', []))
            ->filter(fn ($id): bool => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->take(40)
            ->values();

        $viewedProductId = is_numeric($request->input('viewed_product_id'))
            ? (int) $request->input('viewed_product_id')
            : 0;

        $allProductIds = $requestedProductIds
            ->when($viewedProductId > 0, fn ($ids) => $ids->push($viewedProductId))
            ->unique()
            ->take(40)
            ->values();

        if (
            $allProductIds->isEmpty()
            || ! Schema::hasTable('product_view_sessions')
            || ! Schema::hasColumn('product_view_sessions', 'viewed_detail_at')
        ) {
            return response()->json(['activities' => []]);
        }

        $validProductIds = Product::query()
            ->published()
            ->whereIn('id', $allProductIds->all())
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values();

        if ($validProductIds->isEmpty()) {
            return response()->json(['activities' => []]);
        }

        $now = now();
        $visitorId = $this->visitorId($request);
        $visitorKey = hash('sha256', $visitorId.'|'.(string) config('app.key'));

        // Record a visit only when the browser is actually on a product-details page.
        // Merely displaying a product card never counts as a product visit.
        if ($viewedProductId > 0 && $validProductIds->contains($viewedProductId)) {
            $existing = DB::table('product_view_sessions')
                ->where('product_id', $viewedProductId)
                ->where('visitor_key', $visitorKey)
                ->exists();

            if ($existing) {
                DB::table('product_view_sessions')
                    ->where('product_id', $viewedProductId)
                    ->where('visitor_key', $visitorKey)
                    ->update([
                        'last_seen_at' => $now,
                        'viewed_detail_at' => $now,
                        'updated_at' => $now,
                    ]);
            } else {
                DB::table('product_view_sessions')->insert([
                    'product_id' => $viewedProductId,
                    'visitor_key' => $visitorKey,
                    'first_seen_at' => $now,
                    'last_seen_at' => $now,
                    'viewed_detail_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Keep the table compact while retaining enough history for weekly counts.
        DB::table('product_view_sessions')
            ->where(function ($query) use ($now): void {
                $query->where('viewed_detail_at', '<', $now->copy()->subDays(90))
                    ->orWhere(function ($nested) use ($now): void {
                        $nested->whereNull('viewed_detail_at')
                            ->where('last_seen_at', '<', $now->copy()->subDays(30));
                    });
            })
            ->delete();

        $weekCutoff = $now->copy()->subDays(7);
        $counts = DB::table('product_view_sessions')
            ->whereIn('product_id', $validProductIds->all())
            ->whereNotNull('viewed_detail_at')
            ->where('viewed_detail_at', '>=', $weekCutoff)
            ->select('product_id')
            ->selectRaw('COUNT(*) as weekly_count')
            ->groupBy('product_id')
            ->pluck('weekly_count', 'product_id');

        $activities = $validProductIds
            ->mapWithKeys(function (int $productId) use ($counts): array {
                $count = (int) ($counts[$productId] ?? 0);

                if ($count <= 0) {
                    return [$productId => null];
                }

                return [$productId => [
                    'weekly_count' => $count,
                    'label' => $count.' shopper'.($count === 1 ? '' : 's').' visited this week',
                ]];
            })
            ->filter()
            ->all();

        return response()
            ->json(['activities' => $activities])
            ->cookie(
                'np_visitor_id',
                $visitorId,
                60 * 24 * 365,
                '/',
                null,
                $request->isSecure(),
                true,
                false,
                'Lax'
            );
    }

    private function visitorId(Request $request): string
    {
        $visitorId = (string) $request->cookie('np_visitor_id', '');

        if (preg_match('/^[A-Za-z0-9\-]{16,80}$/', $visitorId) === 1) {
            return $visitorId;
        }

        return (string) Str::uuid();
    }
}
