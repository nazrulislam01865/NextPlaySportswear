<?php

namespace Tests\Feature\Admin;

use App\Enums\JerseyCustomizationType;
use App\Models\JerseyCustomizationOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JerseyCustomizationOptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_color_option_with_description_and_multiple_linked_images(): void
    {
        $admin = User::factory()->create([
            'role' => 'catalog_manager',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->post(
            route('admin.jersey-customization-options.store'),
            [
                'name' => 'Royal Blue',
                'type' => JerseyCustomizationType::Color->value,
                'color_hex' => '#2563EB',
                'description' => 'A bright team blue suitable for home and away jersey accents.',
                'images' => [
                    [
                        'name' => 'Front view',
                        'image_url' => 'https://example.com/royal-blue-front.jpg',
                        'is_primary' => '1',
                        'sort_order' => 0,
                    ],
                    [
                        'name' => 'Back view',
                        'image_url' => 'https://example.com/royal-blue-back.jpg',
                        'is_primary' => '0',
                        'sort_order' => 1,
                    ],
                ],
            ]
        );

        $option = JerseyCustomizationOption::query()->firstOrFail();

        $response->assertRedirect(route('admin.jersey-customization-options.edit', $option));
        $this->assertSame(JerseyCustomizationType::Color, $option->type);
        $this->assertSame('#2563EB', $option->color_hex);
        $this->assertSame(
            'A bright team blue suitable for home and away jersey accents.',
            $option->description
        );
        $this->assertTrue($option->is_active);
        $this->assertSame(0, $option->sort_order);
        $this->assertCount(2, $option->images);
        $this->assertSame('Front view', $option->primaryImage()->firstOrFail()->name);
    }

    public function test_color_value_is_required_only_for_color_options(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')->post(
            route('admin.jersey-customization-options.store'),
            [
                'name' => 'Black',
                'type' => JerseyCustomizationType::Color->value,
            ]
        )->assertSessionHasErrors('color_hex');

        $this->actingAs($admin, 'admin')->post(
            route('admin.jersey-customization-options.store'),
            [
                'name' => 'Dri-Fit Mesh',
                'type' => JerseyCustomizationType::Fabric->value,
                'description' => 'Lightweight breathable jersey fabric.',
            ]
        )->assertSessionDoesntHaveErrors('color_hex');
    }
}
