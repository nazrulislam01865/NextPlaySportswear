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
        $productIds = collect((array) $request->input('product_ids', []))
            ->merge([$request->input('product_id')])
            ->filter(fn ($id): bool => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->take(40)
            ->values();

        if ($productIds->isEmpty() || ! Schema::hasTable('product_view_sessions')) {
            return response()->json(['activities' => []]);
        }

        $validProductIds = Product::query()
            ->published()
            ->whereIn('id', $productIds->all())
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values();

        if ($validProductIds->isEmpty()) {
            return response()->json(['activities' => []]);
        }

        $visitorId = $this->visitorId($request);
        $visitorKey = hash('sha256', $visitorId.'|'.(string) config('app.key'));
        $now = now();
        $activeMinutes = max(1, min(60, (int) config('storefront.product_cards.live_viewer_window_minutes', 5)));
        $pruneMinutes = max(30, $activeMinutes * 6);

        DB::table('product_view_sessions')
            ->where('last_seen_at', '<', $now->copy()->subMinutes($pruneMinutes))
            ->delete();

        foreach ($validProductIds as $productId) {
            DB::table('product_view_sessions')->updateOrInsert(
                [
                    'product_id' => $productId,
                    'visitor_key' => $visitorKey,
                ],
                [
                    'first_seen_at' => $now,
                    'last_seen_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $activeCutoff = $now->copy()->subMinutes($activeMinutes);
        $counts = DB::table('product_view_sessions')
            ->whereIn('product_id', $validProductIds->all())
            ->where('last_seen_at', '>=', $activeCutoff)
            ->select('product_id')
            ->selectRaw('COUNT(*) as active_count')
            ->groupBy('product_id')
            ->pluck('active_count', 'product_id');

        $activities = $validProductIds
            ->mapWithKeys(function (int $productId) use ($counts): array {
                $count = (int) ($counts[$productId] ?? 0);

                if ($count <= 0) {
                    return [$productId => null];
                }

                return [$productId => [
                    'active_count' => $count,
                    'label' => $count.' shopper'.($count === 1 ? '' : 's').' viewing now',
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
