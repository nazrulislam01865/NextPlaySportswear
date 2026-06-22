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
    ) {
    }
    public function getHomePageData(): array
    {
        return [
            'seo' => $this->seo(),
            'slides' => $this->homepageSlider->slides(),
            'categories' => $this->categories(),
            'buyerPaths' => $this->buyerPaths(),
            'featuredProducts' => $this->featuredProducts(),
            'sports' => $this->sports(),
            'processSteps' => $this->processSteps(),
            'faqs' => $this->faqs(),
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
            'og_image' => 'https://images.unsplash.com/photo-1517466787929-bc90951d0974?auto=format&fit=crop&w=1200&q=80',
        ];
    }

    private function categories(): array
    {
        return collect($this->categoryCatalog->collections())
            ->sortByDesc(fn (array $category): bool => (bool) ($category['is_featured'] ?? false))
            ->take(6)
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
        return collect($this->productCatalog->featured())
            ->take(8)
            ->values()
            ->all();
    }

    private function sports(): array
    {
        return collect($this->categoryCatalog->sports())
            ->take(10)
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
                'answer' => 'Yes. For larger orders, please contact us for a custom quotation.',
            ],
            [
                'question' => 'Can I add player names and numbers?',
                'answer' => 'Yes. For jerseys and team uniforms, you can usually add names, numbers, logos, and team colors.',
            ],
            [
                'question' => 'Do you help with artwork or mockups?',
                'answer' => 'Yes. You can send your logo or design idea. A proof or mockup may be reviewed before production.',
            ],
        ];
    }
}
