<?php

namespace App\Services\Storefront;

class HomePageService
{
    public function __construct(
        private readonly CategoryCatalogService $categoryCatalog,
        private readonly ProductCatalogService $productCatalog,
        private readonly HomepageSliderService $homepageSlider,
        private readonly HomepageSectionService $homepageSections,
    ) {
    }
    public function getHomePageData(): array
    {
        $latestProductsFeed = $this->latestProductsFeed();

        return [
            'seo' => $this->seo(),
            'slides' => $this->homepageSlider->slides(),
            'homeSections' => $this->homepageSections->sections(),
            'categories' => $this->categories(),
            'featuredProducts' => $this->featuredProducts(),
            'latestProducts' => $latestProductsFeed['products'],
            'latestProductsSignature' => $latestProductsFeed['signature'],
            'bestSellingProducts' => $this->bestSellingProducts(),
            'sports' => $this->sports(),
        ];
    }

    private function seo(): array
    {
        return [
            'title' => 'Custom Sportswear, Team Uniforms & Jerseys | ' . config('storefront.name'),
            'description' => 'Shop custom sportswear, team uniforms, jerseys, hoodies, caps, bags, and promotional products for teams and events.',
            'robots' => 'index, follow',
            'canonical' => route('home'),
            'og_title' => 'Custom Sportswear, Team Uniforms & Jerseys | ' . config('storefront.name'),
            'og_description' => 'Custom sportswear, jerseys, uniforms, hoodies, caps, bags, and promotional products for teams, schools, businesses, and events.',
            'og_image' => asset('storage/storefront/home/hero.webp'),
        ];
    }

    private function categories(): array
    {
        return $this->categoriesForSection('shop_by_category', fn (): array => $this->automaticFeaturedCategories());
    }

    private function automaticFeaturedCategories(): array
    {
        return collect($this->categoryCatalog->collections())
            ->filter(fn (array $category): bool => (bool) ($category['is_featured'] ?? false))
            ->values()
            ->all();
    }

    private function sectionItems(string $key): array
    {
        $section = collect($this->homepageSections->sections())->firstWhere('key', $key);

        return is_array($section) && is_array($section['items'] ?? null) ? $section['items'] : [];
    }

    private function featuredProducts(): array
    {
        return $this->productCatalog->featured(
            max(1, (int) config('storefront.homepage.featured_products_limit', 12))
        );
    }

    /**
     * Return the current Latest Products storefront payload.
     *
     * The signature lets an already-open homepage check for catalog changes
     * without reloading the whole page or replacing unchanged markup.
     *
     * @return array{products: array<int, array<string, mixed>>, section: array<string, mixed>, signature: string}
     */
    public function latestProductsFeed(): array
    {
        $products = $this->productCatalog->latest(
            max(1, (int) config('storefront.homepage.latest_products_limit', 12))
        );
        $section = collect($this->homepageSections->sections())
            ->first(fn (array $item): bool => ($item['key'] ?? null) === 'new_arrivals'
                || ($item['component'] ?? null) === 'new_arrivals');

        return [
            'products' => $products,
            'section' => is_array($section) ? $section : [],
            'signature' => hash('sha256', serialize([$products, $section])),
        ];
    }

    private function bestSellingProducts(): array
    {
        return $this->productCatalog->bestSelling(
            max(1, (int) config('storefront.homepage.best_selling_products_limit', 5))
        );
    }

    private function sports(): array
    {
        return $this->categoriesForSection('shop_by_sport', fn (): array => collect($this->categoryCatalog->sports())
            ->values()
            ->all());
    }

    private function categoriesForSection(string $key, callable $fallback, ?int $limit = null): array
    {
        $ids = $this->categoryIdsForSection($key);

        if ($ids !== []) {
            $selectedLimit = $limit ?? count($ids);
            $selected = $this->categoryCatalog->categoriesByIds($ids, $selectedLimit);

            if ($selected !== []) {
                return $selected;
            }
        }

        return $fallback();
    }

    /** @return array<int, int> */
    private function categoryIdsForSection(string $key): array
    {
        return collect($this->sectionItems($key))
            ->pluck('category_id')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

}
