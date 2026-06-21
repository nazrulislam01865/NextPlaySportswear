<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Storefront\HomePageService;
use Illuminate\Contracts\View\View;

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
}
