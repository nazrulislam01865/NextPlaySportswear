<?php

namespace App\Http\Controllers\Api\V1\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Storefront\HomepageResource;
use App\Services\Storefront\HomePageService;

final class HomeController extends Controller
{
    public function __invoke(HomePageService $homePage): HomepageResource
    {
        return new HomepageResource($homePage->getHomePageData());
    }
}
