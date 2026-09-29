<?php

namespace Tests\Feature;

use App\Models\ShippingDeliveryPageSetting;
use App\Services\Storefront\ShippingDeliveryPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingDeliveryPageManagedContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_renders_managed_delivery_content_and_seo_with_fixed_structure(): void
    {
        $payload = app(ShippingDeliveryPageService::class)->defaults();
        $payload['hero']['title'] = 'MANAGED ORDER INFORMATION';
        $payload['tabs'][3]['label'] = 'Managed Delivery';
        $payload['info_cards']['cards'][0]['title'] = 'Managed Before Pay';
        $payload['faqs']['items'][0]['question'] = 'Managed delivery question?';
        $payload['address_checklist']['items'][0]['title'] = 'Managed Recipient';
        $payload['cta']['primary_label'] = 'MANAGED TRACK';
        $payload['seo'] = ['title' => 'Managed Shipping SEO', 'description' => 'Managed shipping description'];
        ShippingDeliveryPageSetting::query()->create($payload);

        $response = $this->get(route('shipping'))->assertOk();
        $response->assertSee('MANAGED ORDER INFORMATION')
            ->assertSee('Managed Delivery')
            ->assertSee('Managed Before Pay')
            ->assertSee('Managed delivery question?')
            ->assertSee('Managed Recipient')
            ->assertSee('MANAGED TRACK')
            ->assertSee('Managed Shipping SEO');

        $html = $response->getContent();
        $this->assertSame(2, substr_count($html, 'class="np-shipping-delivery-info-card"'));
        $this->assertSame(3, substr_count($html, 'np-shipping-delivery-timeline-step__number'));
        $this->assertSame(4, substr_count($html, 'class="np-shipping-delivery-faq"'));
        $this->assertSame(4, substr_count($html, 'class="np-shipping-delivery-checklist-row"'));
    }
}
