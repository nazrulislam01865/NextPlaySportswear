<?php

namespace Tests\Unit\Services\Storefront;

use App\Models\ShippingDeliveryPageSetting;
use App\Services\Storefront\ShippingDeliveryPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShippingDeliveryPageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_match_the_approved_delivery_prototype(): void
    {
        $page = app(ShippingDeliveryPageService::class)->settings();

        $this->assertSame('ORDER INFORMATION', $page['hero']['title']);
        $this->assertSame('DELIVERY', $page['delivery_intro']['title']);
        $this->assertSame(['before-you-order', 'artwork-customisation', 'after-you-order', 'delivery', 'help'], array_column($page['tabs'], 'id'));
        $this->assertCount(2, $page['info_cards']['cards']);
        $this->assertCount(3, $page['delivery_steps']['steps']);
        $this->assertCount(4, $page['faqs']['items']);
        $this->assertCount(4, $page['address_checklist']['items']);
        $this->assertSame('/track-order', $page['cta']['primary_url']);
        $this->assertSame('/terms-conditions', $page['cta']['policy_url']);
    }

    public function test_missing_settings_table_returns_defaults_instead_of_throwing(): void
    {
        Schema::dropIfExists('shipping_delivery_page_settings');
        $page = app(ShippingDeliveryPageService::class)->settings();

        $this->assertSame('ORDER INFORMATION', $page['hero']['title']);
        $this->assertCount(5, $page['tabs']);
    }

    public function test_malformed_and_reordered_slots_are_normalized_to_fixed_ids_and_scalar_defaults(): void
    {
        ShippingDeliveryPageSetting::query()->create([
            'hero' => ['title' => ['bad']],
            'tabs' => [
                ['id' => 'delivery', 'label' => 'Moved'],
                ['id' => 'delivery', 'label' => 'Duplicate'],
                ['id' => 'unknown', 'label' => 'Unknown'],
            ],
            'info_cards' => ['cards' => [['id' => 'after-dispatch', 'title' => 'Custom after'], ['id' => 'before-you-pay', 'title' => ['bad']]]],
            'delivery_steps' => ['steps' => [['id' => 'follow-dispatch-updates', 'number' => '9'], ['id' => 'confirm-address', 'number' => 'A']]],
            'faqs' => ['items' => [['id' => 'multiple-locations', 'question' => 'Custom question']]],
            'address_checklist' => ['items' => [['id' => 'contact-details', 'title' => 'Custom contact']]],
        ]);

        $page = app(ShippingDeliveryPageService::class)->settings();

        $this->assertSame('ORDER INFORMATION', $page['hero']['title']);
        $this->assertSame(['before-you-order', 'artwork-customisation', 'after-you-order', 'delivery', 'help'], array_column($page['tabs'], 'id'));
        $this->assertSame(['before-you-pay', 'after-dispatch'], array_column($page['info_cards']['cards'], 'id'));
        $this->assertSame(['confirm-address', 'choose-delivery-option', 'follow-dispatch-updates'], array_column($page['delivery_steps']['steps'], 'id'));
        $this->assertSame(['delivery-costs', 'change-address', 'tracking-number', 'multiple-locations'], array_column($page['faqs']['items'], 'id'));
        $this->assertSame(['recipient-name', 'full-address', 'postcode', 'contact-details'], array_column($page['address_checklist']['items'], 'id'));
        $this->assertSame('BEFORE YOU PAY', $page['info_cards']['cards'][0]['title']);
        $this->assertSame('Custom after', $page['info_cards']['cards'][1]['title']);
    }

    public function test_unsafe_persisted_destinations_fall_back_to_safe_defaults(): void
    {
        ShippingDeliveryPageSetting::query()->create([
            'cta' => ['primary_url' => 'javascript:alert(1)', 'policy_url' => '//evil.example/policy'],
        ]);

        $page = app(ShippingDeliveryPageService::class)->settings();
        $this->assertSame('/track-order', $page['cta']['primary_url']);
        $this->assertSame('/terms-conditions', $page['cta']['policy_url']);
    }

    public function test_missing_traversal_and_foreign_icon_paths_use_builtin_fallbacks(): void
    {
        Storage::fake('public');
        ShippingDeliveryPageSetting::query()->create([
            'info_cards' => ['cards' => [['id' => 'before-you-pay', 'icon_path' => 'shipping-delivery-page/info-cards/icons/missing.png']]],
            'notice' => ['icon_path' => 'shipping-delivery-page/notice/icons/../private.png'],
            'address_checklist' => ['items' => [['id' => 'recipient-name', 'icon_path' => 'about-page/help/icons/x.png']]],
        ]);

        $page = app(ShippingDeliveryPageService::class)->settings();
        $this->assertNull($page['info_cards']['cards'][0]['icon_url']);
        $this->assertSame('cart', $page['info_cards']['cards'][0]['fallback_icon']);
        $this->assertNull($page['notice']['icon_url']);
        $this->assertSame('info', $page['notice']['fallback_icon']);
        $this->assertNull($page['address_checklist']['items'][0]['icon_url']);
        $this->assertSame('user', $page['address_checklist']['items'][0]['fallback_icon']);
    }
}
