<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\CategoryFilterRequest;
use App\Models\UrlRedirect;
use App\Services\Catalog\CategoryContentService;
use App\Services\Storefront\CategoryCatalogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryCatalogService $catalog,
        private readonly CategoryContentService $content,
    ) {}

    public function index(): View
    {
        $collections = $this->catalog->collections();
        $sports = $this->catalog->sports();

        return view('storefront.categories.index', [
            'sports' => $sports,
            'collections' => $collections,
            'filterTags' => $this->catalog->filterTags(),
            'faqs' => [],
            'seo' => [
                'title' => 'Custom Sportswear Categories | Jerseys, Uniforms, Apparel & Gear',
                'description' => 'Browse custom products through a structured category hierarchy for sports, apparel, accessories, events, and promotional needs.',
                'canonical' => route('categories.index'),
            ],
            'structuredData' => [[
                '@context' => 'https://schema.org', '@type' => 'CollectionPage',
                'name' => 'Shop Custom Sportswear by Category', 'url' => route('categories.index'),
            ]],
        ]);
    }

    public function show(CategoryFilterRequest $request, string $slug): View|RedirectResponse
    {
        $category = $this->catalog->findBySlug($slug);
        if (! $category) {
            $redirect = UrlRedirect::query()->where('old_path', '/category/'.$slug)->where('is_active', true)->first();
            abort_unless($redirect, 404);
            return redirect($redirect->new_path, $redirect->status_code);
        }

        $filters = $request->filters();
        if (! $request->filled('sort')) {
            $filters['sort'] = $category->default_product_sort ?: 'featured';
        }
        $products = $this->catalog->productsFor($category, $filters);
        $breadcrumbs = $this->catalog->breadcrumbs($category);
        $hasFilters = $filters['q'] !== '' || $filters['subcategory'] !== [] || $filters['attributes'] !== []
            || $filters['min_price'] !== null || $filters['max_price'] !== null || $filters['in_stock']
            || $filters['customizable'] || $filters['sort'] !== ($category->default_product_sort ?: 'featured');
        $categoryData = $this->catalog->categoryData($category);

        $schemas = [[
            '@context' => 'https://schema.org', '@type' => 'BreadcrumbList',
            'itemListElement' => $breadcrumbs->values()->map(fn ($item, $index) => [
                '@type' => 'ListItem', 'position' => $index + 1, 'name' => $item->name,
                'item' => route('categories.show', $item->slug),
            ])->all(),
        ]];
        if ($category->faqs->where('is_active', true)->isNotEmpty() && ! $hasFilters) {
            $schemas[] = [
                '@context' => 'https://schema.org', '@type' => 'FAQPage',
                'mainEntity' => $category->faqs->where('is_active', true)->map(fn ($faq) => [
                    '@type' => 'Question', 'name' => $faq->question,
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($faq->answer_html)],
                ])->values()->all(),
            ];
        }
        if (! $hasFilters && is_array($category->schema_json) && $category->schema_json !== []) {
            $schemas[] = $category->schema_json;
        }

        return view('storefront.categories.show', [
            'categoryModel' => $category,
            'category' => $categoryData,
            'breadcrumbs' => $breadcrumbs,
            'products' => $products,
            'filters' => $filters,
            'filterOptions' => $this->catalog->filterOptions($category),
            'relatedCategories' => $this->catalog->relatedCategories($category),
            'contentBlocks' => $this->content->resolve($category),
            'hasFilters' => $hasFilters,
            'seo' => [
                'title' => ($category->meta_title ?: $category->name).' | '.config('storefront.name'),
                'description' => $category->meta_description ?: $category->short_description ?: $category->description,
                'canonical' => $category->canonical_url ?: route('categories.show', $category->slug),
                'robots' => $hasFilters || ! $category->robots_index ? 'noindex, follow' : ($category->robots_follow ? 'index, follow' : 'index, nofollow'),
                'og_title' => $category->og_title ?: $category->name,
                'og_description' => $category->og_description ?: $category->meta_description,
                'og_image' => $category->ogImageUrl(),
            ],
            'structuredData' => $schemas,
        ]);
    }
}
