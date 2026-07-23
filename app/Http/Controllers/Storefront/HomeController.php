<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Storefront\HomePageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(
        private readonly HomePageService $homePageService
    ) {
    }

    public function __invoke(): View
    {
        return view('storefront.home', $this->homePageService->getHomePageData());
    }

    public function latestProducts(Request $request): JsonResponse
    {
        $feed = $this->homePageService->latestProductsFeed();
        $currentSignature = trim((string) $request->query('signature', ''));
        $changed = $currentSignature === '' || ! hash_equals($feed['signature'], $currentSignature);

        $payload = [
            'changed' => $changed,
            'signature' => $feed['signature'],
            'checked_at' => now()->toIso8601String(),
        ];

        if ($changed) {
            $payload['html'] = view('components.storefront.home.latest-products', [
                'products' => $feed['products'],
                'section' => $feed['section'],
                'signature' => $feed['signature'],
            ])->render();
        }

        return response()->json($payload)->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
