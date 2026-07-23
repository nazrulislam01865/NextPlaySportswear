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
        $latestProductsFeed = $this->latestProductsFeed();

        return [
            'seo' => $this->seo(),
            'slides' => $this->homepageSlider->slides(),
            'homeSections' => $this->homepageSections->sections(),
            'categories' => $this->categories(),
            'buyerPaths' => $this->sectionItems('buyer_paths'),
            'featuredProducts' => $this->featuredProducts(),
            'latestProducts' => $latestProductsFeed['products'],
            'latestProductsSignature' => $latestProductsFeed['signature'],
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
        return $this->categoriesForSection('categories', fn (): array => $this->automaticFeaturedCategories());
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
        return $this->productCatalog->featured(null);
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
        $products = $this->productCatalog->latest(12);
        $section = collect($this->homepageSections->sections())
            ->first(fn (array $item): bool => ($item['key'] ?? null) === 'latest_products'
                || ($item['component'] ?? null) === 'latest_products');

        return [
            'products' => $products,
            'section' => is_array($section) ? $section : [],
            'signature' => hash('sha256', serialize([$products, $section])),
        ];
    }

    private function bestSellingProducts(): array
    {
        return $this->productCatalog->bestSelling(4);
    }

    private function popularSportsCategories(): array
    {
        return $this->categoriesForSection('popular_categories', fn (): array => collect($this->categoryCatalog->popularSportswearCategories(null))
            ->values()
            ->all());
    }

    private function bestSellingGearCategories(): array
    {
        $items = collect($this->sectionItems('best_selling_gear'))
            ->filter(fn (array $item): bool => (int) ($item['category_id'] ?? 0) > 0 || filled($item['title'] ?? null))
            ->values();

        if ($items->isNotEmpty()) {
            $categoryIds = $items
                ->pluck('category_id')
                ->map(fn ($id): int => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->values()
                ->all();

            $categoriesById = collect($this->categoryCatalog->categoriesByIds($categoryIds, max(1, count($categoryIds))))->keyBy('id');

            $cards = $items
                ->map(function (array $item) use ($categoriesById): array {
                    $categoryId = (int) ($item['category_id'] ?? 0);
                    $category = $categoryId > 0 ? (array) ($categoriesById->get($categoryId) ?? []) : [];

                    return $this->gearCardFromItem($item, $category);
                })
                ->filter(fn (array $card): bool => filled($card['short_title'] ?? null) && filled($card['url'] ?? null))
                ->values()
                ->all();

            if ($cards !== []) {
                return $cards;
            }
        }

        return $this->automaticBestSellingGearCategories();
    }

    private function automaticBestSellingGearCategories(): array
    {
        $categories = collect($this->categoryCatalog->collections());
        $usedKeys = [];

        $preferred = [
            [
                'title' => 'Performance Apparel',
                'description' => 'Custom performance apparel for active brands, teams, events, and promotional programs.',
                'needles' => ['performance apparel', 'performance', 'apparel'],
            ],
            [
                'title' => 'Accessories',
                'description' => 'Custom accessories that add value to teamwear, fanwear, and promotional campaigns.',
                'needles' => ['accessories', 'accessory'],
            ],
            [
                'title' => 'Bags',
                'description' => 'Custom sports and promotional bags for teams, schools, events, and branded merchandise programs.',
                'needles' => ['bags', 'bag', 'sports bags'],
            ],
            [
                'title' => 'Drinkware',
                'description' => 'Custom drinkware for teams, gyms, schools, giveaways, and retail promotions.',
                'needles' => ['drinkware', 'bottle', 'tumbler'],
            ],
            [
                'title' => 'Headwear',
                'description' => 'Custom headwear for teams, events, merch collections, and corporate branding.',
                'needles' => ['headwear', 'caps', 'hats', 'cap'],
            ],
        ];

        $cards = collect($preferred)
            ->map(function (array $slot) use ($categories, &$usedKeys): ?array {
                $category = $this->findPreferredGearCategory($categories, (array) $slot['needles'], $usedKeys);

                if (! $category) {
                    return null;
                }

                $usedKeys[] = (string) ($category['id'] ?? $category['slug'] ?? $slot['title']);

                return $this->gearCardFromItem([
                    'title' => $slot['title'],
                    'description' => $slot['description'],
                    'label' => 'Shop Category',
                ], $category);
            })
            ->filter()
            ->values();

        if ($cards->count() < 5) {
            $extra = $categories
                ->reject(fn (array $category): bool => in_array((string) ($category['id'] ?? $category['slug'] ?? ''), $usedKeys, true))
                ->map(fn (array $category): array => $this->gearCardFromItem(['label' => 'Shop Category'], $category));

            $cards = $cards->merge($extra);
        }

        return $cards->take(5)->values()->all();
    }

    private function findPreferredGearCategory($categories, array $needles, array $usedKeys): ?array
    {
        foreach ($categories as $category) {
            $key = (string) ($category['id'] ?? $category['slug'] ?? '');

            if ($key !== '' && in_array($key, $usedKeys, true)) {
                continue;
            }

            $haystack = strtolower(implode(' ', array_filter([
                $category['slug'] ?? null,
                $category['short_title'] ?? null,
                $category['title'] ?? null,
                $category['description'] ?? null,
            ])));

            foreach ($needles as $needle) {
                if ($needle !== '' && str_contains($haystack, strtolower((string) $needle))) {
                    return $category;
                }
            }
        }

        return null;
    }

    private function gearCardFromItem(array $item, array $category): array
    {
        $title = trim((string) ($item['title'] ?? '')) ?: (string) ($category['short_title'] ?? $category['title'] ?? '');
        $description = trim((string) ($item['description'] ?? '')) ?: (string) ($category['description'] ?? 'Custom team gear for clubs, schools, events, and branded programs.');
        $url = trim((string) ($item['url'] ?? '')) ?: (string) ($category['url'] ?? route('products.index'));
        $image = trim((string) ($item['image_url'] ?? '')) ?: (string) ($category['image'] ?? asset('images/category-placeholder.svg'));
        $alt = trim((string) ($item['image_alt'] ?? '')) ?: (string) ($category['alt'] ?? ($title !== '' ? $title.' category image' : 'Best-selling gear category image'));
        $label = trim((string) ($item['label'] ?? '')) ?: (string) ($category['link_label'] ?? 'Shop Category');

        return array_merge($category, [
            'short_title' => $title,
            'title' => $title,
            'description' => $description,
            'url' => $url,
            'image' => $image,
            'alt' => $alt,
            'link_label' => $label,
        ]);
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
