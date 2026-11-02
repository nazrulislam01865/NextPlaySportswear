<?php

namespace Tests\Feature;

use App\Models\ShippingDeliveryPageSetting;
use App\Models\StorefrontPageSetting;
use App\Models\User;
use App\Services\Storefront\ShippingDeliveryPageService;
use App\Support\AdminRbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingDeliveryPageManagedContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_shipping_design_displays_its_managed_sections(): void
    {
        $defaults = app(ShippingDeliveryPageService::class)->defaults();
        StorefrontPageSetting::query()->create([
            'page_key' => 'shipping-delivery',
            'title' => 'Updated Delivery Heading',
            'content' => [
                'display_header' => ['title' => 'Updated Delivery Heading', 'subtitle' => 'New delivery description'],
                'sections' => ['01' => ['title' => 'Managed Before Ordering'], '06' => ['btn_text' => 'Custom tracking text']],
            ],
            'seo' => ['title' => 'Managed Shipping SEO', 'description' => 'Managed shipping description'],
        ]);
        $this->get(route('shipping'))->assertOk()
            ->assertSee('Updated Delivery Heading')
            ->assertSee('Managed Before Ordering')
            ->assertSee('Custom tracking text')
            ->assertSee('Managed Shipping SEO')
            ->assertSee($defaults['sections']['03']['title']);
    }

    public function test_new_admin_editor_saves_separately_without_erasing_legacy_shipping_settings(): void
    {
        AdminRbac::syncDefaults(true);
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $service = app(ShippingDeliveryPageService::class);
        $defaults = $service->defaults();
        ShippingDeliveryPageSetting::query()->create($defaults);

        $data = [
            'display_header' => $defaults['display_header'],
            'sections' => $defaults['sections'],
            'seo' => $defaults['seo'],
        ];
        $data['display_header']['title'] = 'New Managed Shipping Heading';
        $this->actingAs($admin, 'admin')->put(route('admin.shipping-delivery-page.update'), $data)
            ->assertRedirect(route('admin.shipping-delivery-page.edit'));
        $this->assertSame('New Managed Shipping Heading', StorefrontPageSetting::query()->where('page_key', 'shipping-delivery')->firstOrFail()->title);
        $this->assertSame('ORDER INFORMATION', ShippingDeliveryPageSetting::query()->firstOrFail()->hero['title']);
        $this->get(route('shipping'))->assertOk()->assertSee('New Managed Shipping Heading');
    }

    public function test_new_editor_rejects_unsafe_tracking_urls(): void
    {
        AdminRbac::syncDefaults(true);
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $defaults = app(ShippingDeliveryPageService::class)->defaults();
        $data = ['display_header' => $defaults['display_header'], 'sections' => $defaults['sections'], 'seo' => $defaults['seo']];
        $data['sections']['06']['btn_url'] = 'javascript:alert(1)';
        $this->actingAs($admin, 'admin')->put(route('admin.shipping-delivery-page.update'), $data)
            ->assertSessionHasErrors('sections.06.btn_url');
        $this->assertSame(0, StorefrontPageSetting::query()->where('page_key', 'shipping-delivery')->count());
    }
}
