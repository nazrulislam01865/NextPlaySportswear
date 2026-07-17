<?php

namespace App\Services\Storefront;

use App\Services\Catalog\NavigationService;

class HomePageService
{
    public function __construct(
        private readonly CategoryCatalogService $categoryCatalog,
        private readonly ProductCatalogService $productCatalog,
        private readonly NavigationService $navigation,
        private readonly HomepageSliderService $homepageSlider,
        private readonly HomepageSectionService $homepageSections,
    ) {
    }
    public function getHomePageData(): array
    {
        return [
            'seo' => $this->seo(),
            'slides' => $this->homepageSlider->slides(),
            'homeSections' => $this->homepageSections->sections(),
            'categories' => $this->categories(),
            'buyerPaths' => $this->sectionItems('buyer_paths'),
            'featuredProducts' => $this->featuredProducts(),
            'latestProducts' => $this->latestProducts(),
            'bestSellingProducts' => $this->bestSellingProducts(),
            'popularSportsCategories' => $this->popularSportsCategories(),
            'bestSellingGearCategories' => $this->bestSellingGearCategories(),
            'sports' => $this->sports(),
            'processSteps' => $this->sectionItems('process'),
            'faqs' => $this->faqItems(),
            'navigation' => $this->navigation->items('header-primary'),
            'storefrontMenus' => $this->navigation->storefrontMenus(),
        ];
    }

    private function seo(): array
    {
        return [
            'title' => 'Custom Sportswear, Team Uniforms & Jerseys | ' . config('storefront.name'),
            'description' => 'Shop custom sportswear, team uniforms, jerseys, hoodies, caps, bags, and promotional products. Bulk quotes available for teams and events.',
            'robots' => 'index, follow',
            'canonical' => route('home'),
            'og_title' => 'Custom Sportswear, Team Uniforms & Jerseys | ' . config('storefront.name'),
            'og_description' => 'Custom sportswear, jerseys, uniforms, hoodies, caps, bags, and promotional products for teams, schools, businesses, and events.',
            'og_image' => asset('storage/storefront/home/hero.webp'),
        ];
    }

    private function categories(): array
    {
        return $this->categoriesForSection('categories', fn (): array => $this->automaticFeaturedCategories(), 6);
    }

    private function automaticFeaturedCategories(): array
    {
        return collect($this->categoryCatalog->collections())
            ->filter(fn (array $category): bool => (bool) ($category['is_featured'] ?? false))
            ->take(6)
            ->values()
            ->all();
    }

    private function sectionItems(string $key): array
    {
        $section = collect($this->homepageSections->sections())->firstWhere('key', $key);

        return is_array($section) && is_array($section['items'] ?? null) ? $section['items'] : [];
    }

    private function faqItems(): array
    {
        return collect($this->sectionItems('faq'))
            ->map(fn (array $item): array => [
                'question' => (string) ($item['title'] ?? ''),
                'answer' => (string) ($item['description'] ?? ''),
            ])
            ->filter(fn (array $item): bool => $item['question'] !== '' && $item['answer'] !== '')
            ->values()
            ->all();
    }

    private function buyerPaths(): array
    {
        return [
            [
                'icon' => '♜',
                'title' => 'Teams & Leagues',
                'description' => 'Uniforms and gear for full teams, clubs, and local leagues.',
                'url' => route('quote.request'),
            ],
            [
                'icon' => '★',
                'title' => 'Schools & Colleges',
                'description' => 'Custom jerseys, PE uniforms, event apparel, and spirit wear.',
                'url' => route('quote.request'),
            ],
            [
                'icon' => '▣',
                'title' => 'Businesses & Events',
                'description' => 'Branded apparel, caps, bags, and giveaway items.',
                'url' => route('quote.request'),
            ],
            [
                'icon' => '✓',
                'title' => 'Individual Buyers',
                'description' => 'Shop selected products online and customize where available.',
                'url' => route('products.index'),
            ],
        ];
    }

    private function featuredProducts(): array
    {
        return $this->productCatalog->featured(8);
    }

    private function latestProducts(): array
    {
        return $this->productCatalog->latest(4);
    }

    private function bestSellingProducts(): array
    {
        return $this->productCatalog->bestSelling(4);
    }

    private function popularSportsCategories(): array
    {
        return $this->categoriesForSection('popular_categories', fn (): array => collect($this->categoryCatalog->popularSportswearCategories())
            ->take(8)
            ->values()
            ->all(), 8);
    }

    private function bestSellingGearCategories(): array
    {
        return $this->categoriesForSection('best_selling_gear', fn (): array => $this->automaticFeaturedCategories(), 8);
    }

    private function sports(): array
    {
        return $this->categoriesForSection('shop_by_sport', fn (): array => collect($this->categoryCatalog->sports())
            ->take(10)
            ->values()
            ->all(), 10);
    }

    private function categoriesForSection(string $key, callable $fallback, int $limit): array
    {
        $ids = $this->categoryIdsForSection($key);

        if ($ids !== []) {
            $selected = $this->categoryCatalog->categoriesByIds($ids, $limit);

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

    private function processSteps(): array
    {
        return [
            ['title' => 'Choose Product', 'description' => 'Pick the product, sport, category, or apparel type.'],
            ['title' => 'Share Custom Details', 'description' => 'Send your logo, colors, names, numbers, size list, and quantity.'],
            ['title' => 'Review Mockup', 'description' => 'We prepare or review the artwork before production.'],
            ['title' => 'Confirm Order', 'description' => 'Approve the final details, price, and timeline.'],
            ['title' => 'Production & Shipping', 'description' => 'Your order goes into production and ships to your address.'],
        ];
    }

    private function faqs(): array
    {
        return [
            [
                'question' => 'Can I order one custom jersey?',
                'answer' => 'Yes, selected products can be ordered directly online. Some custom products may have a minimum order quantity.',
            ],
            [
                'question' => 'Do you offer bulk pricing?',
                'answer' => 'Yes. For larger orders, especially 500+ or 1,000+ pieces, please contact us for a custom quotation.',
            ],
            [
                'question' => 'Can I add player names and numbers?',
                'answer' => 'Yes. For jerseys and team uniforms, you can usually add names, numbers, logos, and team colors.',
            ],
            [
                'question' => 'Do you help with artwork or mockups?',
                'answer' => 'Yes. You can send your logo or design idea. A proof or mockup may be reviewed before production.',
            ],
            [
                'question' => 'How long does production take?',
                'answer' => 'Production time depends on the product, customization method, quantity, and order season. The timeline should be confirmed before production.',
            ],
            [
                'question' => 'Do you ship across the USA?',
                'answer' => 'Yes, shipping options should be shown or confirmed based on the order and delivery location.',
            ],
        ];
    }
}
