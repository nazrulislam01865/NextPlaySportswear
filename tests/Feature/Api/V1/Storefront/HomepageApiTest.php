<?php

namespace Tests\Feature\Api\V1\Storefront;

use App\Services\Storefront\HomePageService;
use App\ViewModels\Catalog\NavigationItem;
use Tests\TestCase;

class HomepageApiTest extends TestCase
{
    public function test_homepage_endpoint_serializes_existing_homepage_service_data_without_blade_html(): void
    {
        $child = NavigationItem::fromArray([
            'label' => 'Jerseys',
            'link_type' => 'custom',
            'url' => '/products?category=jerseys',
            'target' => '_self',
            'children' => [],
        ]);
        $navigation = NavigationItem::fromArray([
            'label' => 'Shop Products',
            'link_type' => 'custom',
            'url' => '/products',
            'target' => '_self',
            'children' => [[
                'label' => $child->label,
                'link_type' => $child->link_type,
                'url' => $child->url,
                'target' => $child->target,
                'children' => [],
            ]],
        ]);

        $payload = [
            'seo' => [
                'title' => 'NextPlay',
                'description' => 'Custom sportswear.',
                'canonical' => 'https://example.test/',
            ],
            'slides' => [['id' => 1, 'title' => 'Hero']],
            'homeSections' => [['key' => 'featured', 'title' => 'Featured']],
            'categories' => [['id' => 2, 'name' => 'Jerseys']],
            'buyerPaths' => [['title' => 'Teams']],
            'featuredProducts' => [['id' => 10, 'name' => 'Featured Jersey']],
            'latestProducts' => [['id' => 11, 'name' => 'Latest Jersey']],
            'latestProductsSignature' => 'latest-signature',
            'bestSellingProducts' => [['id' => 12, 'name' => 'Best Seller']],
            'bestSellingGearCategories' => [['id' => 3, 'name' => 'Team Gear']],
            'sports' => [['name' => 'Basketball']],
            'processSteps' => [['title' => 'Design']],
            'faqs' => [['question' => 'How long?', 'answer' => 'It depends.']],
            'navigation' => collect([$navigation]),
            'storefrontMenus' => [
                'header' => collect([$navigation]),
                'footer_shop' => collect(),
            ],
        ];

        $this->mock(HomePageService::class)
            ->shouldReceive('getHomePageData')
            ->once()
            ->andReturn($payload);

        $response = $this->getJson('/api/v1/home');

        $response->assertOk()
            ->assertHeader('X-Request-ID')
            ->assertJsonPath('data.seo.title', 'NextPlay')
            ->assertJsonPath('data.sections.0.key', 'featured')
            ->assertJsonPath('data.latest_products_signature', 'latest-signature')
            ->assertJsonPath('data.navigation.0.label', 'Shop Products')
            ->assertJsonPath('data.navigation.0.children.0.label', 'Jerseys')
            ->assertJsonPath('data.menus.header.0.url', '/products')
            ->assertJsonStructure(['data', 'meta', 'request_id']);

        $body = strtolower($response->getContent());
        $this->assertStringNotContainsString('<html', $body);
        $this->assertStringNotContainsString('<x-', $body);
        $this->assertStringNotContainsString('item_html', $body);
    }

    public function test_homepage_endpoint_exposes_only_the_explicit_public_contract(): void
    {
        $payload = [
            'seo' => [],
            'slides' => [],
            'homeSections' => [],
            'categories' => [],
            'buyerPaths' => [],
            'featuredProducts' => [],
            'latestProducts' => [],
            'latestProductsSignature' => 'signature',
            'bestSellingProducts' => [],
            'bestSellingGearCategories' => [],
            'sports' => [],
            'processSteps' => [],
            'faqs' => [],
            'navigation' => collect(),
            'storefrontMenus' => [],
            'internal_debug_payload' => ['must_not' => 'leak'],
        ];

        $this->mock(HomePageService::class)
            ->shouldReceive('getHomePageData')
            ->once()
            ->andReturn($payload);

        $response = $this->getJson('/api/v1/home')->assertOk();

        $this->assertArrayNotHasKey('internal_debug_payload', $response->json('data'));
    }
}
