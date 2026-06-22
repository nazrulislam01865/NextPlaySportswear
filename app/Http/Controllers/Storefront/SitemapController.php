<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $categories = Category::query()
            ->storefrontReachable()
            ->where('robots_index', true)
            ->select(['slug', 'updated_at'])
            ->orderBy('id')
            ->get();

        $products = Product::query()
            ->published()
            ->where('robots_index', true)
            ->select(['slug', 'updated_at'])
            ->orderBy('id')
            ->get();

        return response()
            ->view('storefront.sitemap', compact('categories', 'products'))
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=900');
    }
}
