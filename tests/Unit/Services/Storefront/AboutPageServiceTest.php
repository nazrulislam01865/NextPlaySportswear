<?php

namespace Tests\Unit\Services\Storefront;

use App\Models\AboutPageSetting;
use App\Services\Storefront\AboutPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AboutPageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_returns_current_approved_defaults_when_row_is_missing(): void
    {
        $about = app(AboutPageService::class)->settings();

        $this->assertSame('NEXTPLAY SPORTSWEAR', $about['hero']['eyebrow']);
        $this->assertSame('ABOUT NEXTPLAY', $about['hero']['title']);
        $this->assertSame('Sportswear for teams, clubs and people who love to play.', $about['hero']['description']);
        $this->assertSame('WHAT WE DO', $about['what_we_do']['title']);
        $this->assertSame('HOW WE WORK', $about['how_we_work']['title']);
        $this->assertSame('MADE FOR YOUR TEAM', $about['cta']['eyebrow']);
        $this->assertSame('NEED HELP?', $about['help']['title']);
        $this->assertSame('About NextPlay | '.config('storefront.name'), $about['seo']['title']);
    }

    public function test_settings_merges_missing_json_keys_from_defaults(): void
    {
        AboutPageSetting::query()->create([
            'hero' => ['title' => 'Custom About'],
        ]);

        $about = app(AboutPageService::class)->settings();

        $this->assertSame('Custom About', $about['hero']['title']);
        $this->assertSame('NEXTPLAY SPORTSWEAR', $about['hero']['eyebrow']);
        $this->assertSame('Sportswear for teams, clubs and people who love to play.', $about['hero']['description']);
    }

    public function test_settings_keeps_exactly_three_service_slots_in_canonical_id_order(): void
    {
        AboutPageSetting::query()->create([
            'what_we_do' => [
                'cards' => [
                    ['id' => 'bulk-orders', 'title' => 'Bulk custom'],
                    ['id' => 'custom-teamwear', 'title' => 'Team custom'],
                ],
            ],
        ]);

        $cards = app(AboutPageService::class)->settings()['what_we_do']['cards'];

        $this->assertCount(3, $cards);
        $this->assertSame(['custom-teamwear', 'sportswear-gear', 'bulk-orders'], array_column($cards, 'id'));
        $this->assertSame('Team custom', $cards[0]['title']);
        $this->assertSame('Sportswear & Gear', $cards[1]['title']);
        $this->assertSame('Bulk custom', $cards[2]['title']);
    }

    public function test_settings_keeps_exactly_four_process_slots_in_canonical_id_order(): void
    {
        AboutPageSetting::query()->create([
            'how_we_work' => [
                'steps' => [
                    ['id' => 'place-order', 'number' => '9'],
                    ['id' => 'choose-product', 'number' => 'A'],
                ],
            ],
        ]);

        $steps = app(AboutPageService::class)->settings()['how_we_work']['steps'];

        $this->assertCount(4, $steps);
        $this->assertSame(['choose-product', 'personalise', 'review-details', 'place-order'], array_column($steps, 'id'));
        $this->assertSame('A', $steps[0]['number']);
        $this->assertSame('9', $steps[3]['number']);
    }

    public function test_settings_keeps_exactly_four_gallery_slots_in_canonical_id_order(): void
    {
        AboutPageSetting::query()->create([
            'gallery' => [
                'items' => [
                    ['id' => 'celebration', 'image_alt' => 'Changed fourth'],
                ],
            ],
        ]);

        $items = app(AboutPageService::class)->settings()['gallery']['items'];

        $this->assertCount(4, $items);
        $this->assertSame(['team', 'fabric', 'number', 'celebration'], array_column($items, 'id'));
        $this->assertSame('Changed fourth', $items[3]['image_alt']);
    }

    public function test_unknown_and_duplicate_slot_ids_cannot_expand_or_reorder_fixed_slots(): void
    {
        AboutPageSetting::query()->create([
            'what_we_do' => [
                'cards' => [
                    ['id' => 'bulk-orders', 'title' => 'First bulk'],
                    ['id' => 'unknown', 'title' => 'Unknown'],
                    ['id' => 'bulk-orders', 'title' => 'Duplicate bulk'],
                    ['id' => 'custom-teamwear', 'title' => 'Team'],
                ],
            ],
            'how_we_work' => [
                'steps' => [
                    ['id' => 'place-order'],
                    ['id' => 'place-order'],
                    ['id' => 'unknown'],
                    ['id' => 'choose-product'],
                    ['id' => 'personalise'],
                ],
            ],
            'gallery' => [
                'items' => [
                    ['id' => 'number'],
                    ['id' => 'number'],
                    ['id' => 'unknown'],
                    ['id' => 'team'],
                    ['id' => 'fabric'],
                ],
            ],
        ]);

        $about = app(AboutPageService::class)->settings();

        $this->assertSame(['custom-teamwear', 'sportswear-gear', 'bulk-orders'], array_column($about['what_we_do']['cards'], 'id'));
        $this->assertSame(['choose-product', 'personalise', 'review-details', 'place-order'], array_column($about['how_we_work']['steps'], 'id'));
        $this->assertSame(['team', 'fabric', 'number', 'celebration'], array_column($about['gallery']['items'], 'id'));
        $this->assertCount(3, $about['what_we_do']['cards']);
        $this->assertCount(4, $about['how_we_work']['steps']);
        $this->assertCount(4, $about['gallery']['items']);
    }

    public function test_missing_custom_media_path_falls_back_to_shipped_default_asset_or_builtin_icon(): void
    {
        Storage::fake('public');

        AboutPageSetting::query()->create([
            'introduction' => ['image_path' => 'about-page/introduction/missing.webp'],
            'what_we_do' => [
                'cards' => [
                    ['id' => 'custom-teamwear', 'icon_path' => 'about-page/what-we-do/icons/missing.png'],
                ],
            ],
        ]);

        $about = app(AboutPageService::class)->settings();

        $this->assertStringContainsString('/images/storefront/about/team-intro.webp', $about['introduction']['image_url']);
        $this->assertNull($about['what_we_do']['cards'][0]['icon_url']);
        $this->assertSame('teamwear', $about['what_we_do']['cards'][0]['fallback_icon']);
    }
    public function test_malformed_about_media_path_falls_back_without_resolving_traversal(): void
    {
        Storage::fake('public');

        AboutPageSetting::query()->create([
            'introduction' => ['image_path' => 'about-page/introduction/../../private.webp'],
            'help' => ['icon_path' => 'about-page/help/icons/../private.png'],
        ]);

        $about = app(AboutPageService::class)->settings();

        $this->assertStringContainsString('/images/storefront/about/team-intro.webp', $about['introduction']['image_url']);
        $this->assertFalse($about['introduction']['has_custom_image']);
        $this->assertNull($about['help']['icon_url']);
        $this->assertFalse($about['help']['has_custom_icon']);
    }

    public function test_malformed_leaf_types_fall_back_to_default_scalar_values(): void
    {
        AboutPageSetting::query()->create([
            'hero' => ['title' => ['not' => 'text']],
            'what_we_do' => [
                'title' => ['not' => 'text'],
                'cards' => [
                    ['id' => 'custom-teamwear', 'title' => ['not' => 'text'], 'icon_path' => ['bad']],
                ],
            ],
            'cta' => ['primary_url' => ['not' => 'a url']],
        ]);

        $about = app(AboutPageService::class)->settings();

        $this->assertSame('ABOUT NEXTPLAY', $about['hero']['title']);
        $this->assertSame('WHAT WE DO', $about['what_we_do']['title']);
        $this->assertSame('Custom Teamwear', $about['what_we_do']['cards'][0]['title']);
        $this->assertNull($about['what_we_do']['cards'][0]['icon_path']);
        $this->assertSame('/products', $about['cta']['primary_url']);
    }

    public function test_malformed_persisted_button_destinations_fall_back_to_safe_defaults(): void
    {
        AboutPageSetting::query()->create([
            'cta' => [
                'primary_url' => 'javascript:alert(1)',
                'secondary_url' => '//evil.example/quote',
            ],
            'help' => ['button_url' => 'data:text/html,bad'],
        ]);

        $about = app(AboutPageService::class)->settings();

        $this->assertSame('/products', $about['cta']['primary_url']);
        $this->assertSame('/bulk-quote', $about['cta']['secondary_url']);
        $this->assertSame('/contact-us', $about['help']['button_url']);
    }

    public function test_malformed_json_cannot_override_code_owned_intro_or_help_fallbacks(): void
    {
        AboutPageSetting::query()->create([
            'introduction' => ['fallback_asset' => 'images/other.webp'],
            'help' => ['fallback_icon' => 'unknown-icon'],
        ]);

        $about = app(AboutPageService::class)->settings();

        $this->assertSame('images/storefront/about/team-intro.webp', $about['introduction']['fallback_asset']);
        $this->assertSame('headset', $about['help']['fallback_icon']);
    }

}
