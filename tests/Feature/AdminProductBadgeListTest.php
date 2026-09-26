<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductBadgeListTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_list_flag_uses_the_gallery_badge_label(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        Product::query()->create([
            'name' => 'Custom Baseball Jersey',
            'slug' => 'custom-baseball-jersey',
            'sku' => 'NPS-BSB-017',
            'status' => 'active',
            'badge_label' => 'Limited Edition',
            'base_price' => 12,
            'currency' => 'USD',
            'minimum_quantity' => 1,
            'is_customizable' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('<span class="product-flag product-flag--customizable">Limited Edition</span>', false)
            ->assertDontSee('<span class="product-flag product-flag--customizable">Customizable</span>', false);
    }

    public function test_product_list_does_not_invent_customizable_flag_when_gallery_badge_is_empty(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        Product::query()->create([
            'name' => 'Plain Baseball Jersey',
            'slug' => 'plain-baseball-jersey',
            'sku' => 'NPS-BSB-018',
            'status' => 'active',
            'badge_label' => null,
            'base_price' => 12,
            'currency' => 'USD',
            'minimum_quantity' => 1,
            'is_customizable' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertDontSee('<span class="product-flag product-flag--customizable">Customizable</span>', false);
    }
}
