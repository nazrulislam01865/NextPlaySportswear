<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductFilterSearchableSelectTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_filters_use_shared_dropdown_with_the_existing_query_names(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('data-np-admin-select', false)
            ->assertSee('name="status"', false)
            ->assertSee('name="category_id"', false)
            ->assertSee('name="production_methods"', false)
            ->assertSee('name="sort"', false)
            ->assertSee('Search categories...')
            ->assertSee('nextplay-admin-searchable-select.css')
            ->assertSee('nextplay-admin-searchable-select.js');
    }

    public function test_category_hierarchy_and_selected_filter_are_preserved(): void
    {
        $parent = Category::query()->create([
            'name' => 'Team Uniforms', 'slug' => 'team-uniforms',
            'description' => 'Team Uniforms category', 'image_url' => '', 'image_alt' => 'Team Uniforms',
            'depth' => 0, 'tree_path' => '0001', 'status' => 'active', 'is_active' => true,
        ]);
        $child = Category::query()->create([
            'name' => 'Soccer Kits', 'slug' => 'soccer-kits',
            'description' => 'Soccer Kits category', 'image_url' => '', 'image_alt' => 'Soccer Kits',
            'parent_id' => $parent->id, 'depth' => 1, 'tree_path' => '0001/0002',
            'status' => 'active', 'is_active' => true,
        ]);
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.products.index', ['category_id' => $child->id]))
            ->assertOk()
            ->assertSee('data-label="Soccer Kits"', false)
            ->assertSee('data-depth="1"', false)
            ->assertSee('— Soccer Kits')
            ->assertSee('value="'.$child->id.'" data-depth="1" data-label="Soccer Kits" selected', false);
    }

    public function test_other_product_filter_values_are_retained(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.products.index', [
                'status' => 'draft', 'production_methods' => 'disabled',
                'sort' => 'production_disabled',
            ]))
            ->assertOk()
            ->assertSee('value="draft" selected', false)
            ->assertSee('value="disabled" selected', false)
            ->assertSee('value="production_disabled" selected', false);
    }
}
