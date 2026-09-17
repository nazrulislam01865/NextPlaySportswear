<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

final class VueHomeController extends Controller
{
    public function __invoke(): View
    {
        return view('storefront-vue.home', [
            'siteName' => (string) config('storefront.name', 'NextPlay Sportswear'),
            'description' => (string) config('storefront.tagline', 'Custom sportswear for teams, clubs and events.'),
        ]);
    }
}
