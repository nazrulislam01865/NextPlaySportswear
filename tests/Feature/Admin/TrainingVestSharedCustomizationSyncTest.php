<?php

namespace Tests\Feature\Admin;

use App\Enums\JerseyCustomizationType;
use App\Enums\TrainingVestCustomizationType;
use App\Models\JerseyCustomizationOption;
use App\Models\TrainingVestSizeOptionGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingVestSharedCustomizationSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_training_vest_option_is_mirrored_for_shared_product_charges(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')->post(
            route('admin.training-vest-customization-options.store'),
            [
                'type' => TrainingVestCustomizationType::Color->value,
                'name' => 'Safety Orange',
                'slug' => 'safety-orange',
                'color_hex' => '#FF6600',
                'is_active' => '1',
                'sort_order' => 0,
            ]
        )->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('jersey_customization_options', [
            'type' => JerseyCustomizationType::TrainingVestColorOption->value,
            'slug' => 'safety-orange',
            'color_hex' => '#FF6600',
        ]);
    }

    public function test_training_vest_size_group_values_are_mirrored_for_shared_product_charges(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $response = $this->actingAs($admin, 'admin')->post(
            route('admin.training-vest-size-option-groups.store'),
            [
                'name' => 'Adult Sizes',
                'slug' => 'adult-sizes',
                'audience' => 'unisex',
                'sizes' => [
                    ['label' => 'Medium', 'code' => 'M'],
                    ['label' => 'Large', 'code' => 'L'],
                ],
            ]
        );
        $response->assertSessionDoesntHaveErrors();

        $group = TrainingVestSizeOptionGroup::query()->where('slug', 'adult-sizes')->firstOrFail();
        $this->assertSame(2, JerseyCustomizationOption::query()
            ->where('type', JerseyCustomizationType::TrainingVestSizeOption->value)
            ->where('slug', 'like', 'tvg-'.$group->id.'-%')
            ->count());
    }
}
