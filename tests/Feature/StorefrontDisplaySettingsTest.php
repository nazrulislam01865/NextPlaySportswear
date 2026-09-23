<?php

namespace Tests\Feature;

use App\Models\HomepageSection;
use App\Services\Storefront\HomepageSectionService;
use App\Support\HomepageSectionRegistry;
use App\Support\StorefrontDisplaySettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class StorefrontDisplaySettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        StorefrontDisplaySettings::flushCache();
        HomepageSectionRegistry::ensureRows();
    }

    public function test_inactive_homepage_section_is_not_restored_from_registry_defaults(): void
    {
        $section = HomepageSection::query()->where('key', 'testimonials')->firstOrFail();
        $section->update(['is_active' => false]);

        $service = app(HomepageSectionService::class);
        $service->flushCache();

        $this->assertFalse(collect($service->sections())->contains(
            fn (array $item): bool => ($item['key'] ?? null) === 'testimonials'
        ));
    }

    public function test_default_product_rating_can_be_hidden_from_admin_setting(): void
    {
        $this->updateDisplaySettings(showStatistics: true, showDefaultProductRatings: false);

        $html = Blade::render('<x-storefront.product-card :product="$product" />', [
            'product' => $this->productPayload(),
        ]);

        $this->assertStringNotContainsString('np-product-card-rating"', $html);
        $this->assertStringNotContainsString('(23 reviews)', $html);
    }

    public function test_default_product_rating_can_be_shown_from_admin_setting(): void
    {
        $this->updateDisplaySettings(showStatistics: true, showDefaultProductRatings: true);

        $html = Blade::render('<x-storefront.product-card :product="$product" />', [
            'product' => $this->productPayload(),
        ]);

        $this->assertStringContainsString('np-product-card-rating"', $html);
        $this->assertStringContainsString('4.8', $html);
        $this->assertStringContainsString('(23 reviews)', $html);
    }

    public function test_real_product_rating_is_not_hidden_when_fallback_rating_is_disabled(): void
    {
        $this->updateDisplaySettings(showStatistics: true, showDefaultProductRatings: false);
        $product = $this->productPayload();
        $product['rating'] = 4.6;
        $product['reviews_count'] = 11;

        $html = Blade::render('<x-storefront.product-card :product="$product" />', compact('product'));

        $this->assertStringContainsString('4.6', $html);
        $this->assertStringContainsString('(11 reviews)', $html);
    }

    public function test_testimonial_statistics_can_be_hidden_from_section_setting(): void
    {
        $section = $this->updateDisplaySettings(showStatistics: false, showDefaultProductRatings: true);
        $viewSection = HomepageSectionRegistry::mergeForView('testimonials', $section);

        $html = Blade::render('<x-storefront.home.testimonials :section="$section" />', [
            'section' => $viewSection,
        ]);

        $this->assertStringNotContainsString('aria-label="Customer testimonial statistics"', $html);
        $this->assertStringNotContainsString('<strong>4.9/5</strong>', $html);
    }

    public function test_testimonial_statistics_can_be_shown_from_section_setting(): void
    {
        $section = $this->updateDisplaySettings(showStatistics: true, showDefaultProductRatings: true);
        $viewSection = HomepageSectionRegistry::mergeForView('testimonials', $section);

        $html = Blade::render('<x-storefront.home.testimonials :section="$section" />', [
            'section' => $viewSection,
        ]);

        $this->assertStringContainsString('aria-label="Customer testimonial statistics"', $html);
        $this->assertStringContainsString('<strong>4.9/5</strong>', $html);
    }

    private function updateDisplaySettings(bool $showStatistics, bool $showDefaultProductRatings): HomepageSection
    {
        $section = HomepageSection::query()->where('key', 'testimonials')->firstOrFail();
        $section->update([
            'settings' => [
                'show_statistics' => $showStatistics,
                'show_default_product_ratings' => $showDefaultProductRatings,
            ],
        ]);

        StorefrontDisplaySettings::flushCache();

        return $section->fresh();
    }

    /** @return array<string, mixed> */
    private function productPayload(): array
    {
        return [
            'id' => 0,
            'title' => 'Test Product',
            'sku' => 'TEST-001',
            'category' => 'Jerseys',
            'price' => '$25.00',
            'base_price' => 25,
            'display_unit_price' => 25,
            'original_price' => 0,
            'currency' => 'USD',
            'is_customizable' => false,
            'image' => '/images/product-placeholder.svg',
            'alt' => 'Test Product',
            'url' => '#',
        ];
    }
}
