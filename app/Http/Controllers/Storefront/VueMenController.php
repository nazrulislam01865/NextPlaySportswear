<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

final class VueMenController extends Controller
{
    public function __invoke(): View
    {
        $siteName = (string) config('storefront.name', 'NextPlay Sportswear');

        return view('storefront-vue.home', [
            'siteName' => $siteName,
            'pageTitle' => 'Men | '.$siteName,
            'description' => 'Shop NextPlay men\'s sportswear, jerseys, shorts, T-shirts and tracksuits.',
        ]);
    }
}
