<?php

namespace Tests\Feature\Admin;

use App\Models\Gender;
use App\Models\Product;
use App\Models\User;
use App\Services\Storefront\ProductCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenderMasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_standard_gender_master_values_are_available_after_migration(): void
    {
        $this->assertSame(
            ['Men', 'Women', 'Kids', 'Unisex'],
            Gender::query()->ordered()->pluck('name')->all()
        );
    }

    public function test_catalog_admin_can_create_gender_master_data(): void
    {
        $admin = User::factory()->create([
            'role' => 'catalog_manager',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->post(route('admin.genders.store'), [
            'name' => 'Youth Unisex',
            'slug' => '',
            'is_active' => '1',
            'sort_order' => 50,
        ]);

        $response->assertRedirect(route('admin.genders.index'));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('genders', [
            'name' => 'Youth Unisex',
            'slug' => 'youth-unisex',
            'is_active' => 1,
            'sort_order' => 50,
        ]);
    }

    public function test_product_gender_is_nullable_and_master_deletion_clears_assignment(): void
    {
        $gender = Gender::query()->where('slug', 'men')->firstOrFail();

        $product = Product::query()->create([
            'name' => 'Gender Test Product',
            'slug' => 'gender-test-product',
            'sku' => 'GENDER-TEST-001',
            'status' => 'draft',
            'gender_id' => $gender->id,
            'base_price' => 10,
            'currency' => 'USD',
            'minimum_quantity' => 1,
        ]);

        $this->assertSame('Men', $product->gender()->firstOrFail()->name);

        $gender->delete();

        $this->assertNull($product->fresh()->gender_id);
    }

    public function test_selected_gender_is_exposed_in_storefront_product_details(): void
    {
        $gender = Gender::query()->where('slug', 'unisex')->firstOrFail();

        Product::query()->create([
            'name' => 'Unisex Detail Product',
            'slug' => 'unisex-detail-product',
            'sku' => 'GENDER-DETAIL-001',
            'status' => 'active',
            'gender_id' => $gender->id,
            'short_description' => 'Gender detail test.',
            'description_html' => '<p>Gender detail test.</p>',
            'base_price' => 25,
            'currency' => 'USD',
            'minimum_quantity' => 1,
            'is_active' => true,
            'published_at' => now(),
        ]);

        $product = app(ProductCatalogService::class)->findFullBySlug('unisex-detail-product');

        $this->assertNotNull($product);
        $this->assertSame('Unisex', $product['gender']);
        $this->assertSame('Unisex', $product['detail_information']['Gender']);
        $this->assertSame('Unisex', $product['summary_detail_information']['Gender']);
    }
}
