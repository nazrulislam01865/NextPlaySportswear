<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Storefront\ProductCatalogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductCatalogService $productCatalogService
    ) {
    }

    public function index(Request $request): View
    {
        $query = $request->string('q')->trim()->toString();
        $products = $this->productCatalogService->search($query);

        return view('storefront.products.index', [
            'products' => $products,
            'query' => $query,
            'seo' => [
                'title' => filled($query)
                    ? 'Search results for ' . $query . ' | ' . config('storefront.name')
                    : 'Products | ' . config('storefront.name'),
                'description' => 'Browse custom jerseys, uniforms, hoodies, caps, bags, and bulk-ready team sportswear products.',
                'canonical' => route('products.index'),
            ],
        ]);
    }

    public function show(string $slug): View
    {
        $product = $this->productCatalogService->findBySlug($slug);

        abort_if(! $product, 404);

        return view('storefront.products.show', [
            'product' => $product,
            'relatedProducts' => $this->productCatalogService->relatedFor($product),
            'seo' => [
                'title' => $product['title'] . ' | ' . config('storefront.name'),
                'description' => $product['summary'],
                'canonical' => $product['url'],
                'og_title' => $product['title'] . ' | ' . config('storefront.name'),
                'og_description' => $product['summary'],
                'og_image' => $product['image'],
            ],
        ]);
    }
}
