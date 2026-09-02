<?php

namespace Tests\Feature\Admin;

use App\Enums\WorldCupCustomizationType;
use App\Models\User;
use App\Models\WorldCupCustomizationOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorldCupCustomizationOptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_slug_is_kept_separate_between_world_cup_categories(): void
    {
        $user = User::factory()->create();

        WorldCupCustomizationOption::query()->create([
            'category_key' => 'headband',
            'type' => WorldCupCustomizationType::HeadbandMaterialsOption,
            'name' => 'Standard',
            'slug' => 'standard',
            'is_active' => true,
            'sort_order' => 0,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        WorldCupCustomizationOption::query()->create([
            'category_key' => 'fan_cap',
            'type' => WorldCupCustomizationType::FanCapMaterialsOption,
            'name' => 'Standard',
            'slug' => 'standard',
            'is_active' => true,
            'sort_order' => 0,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->assertSame(2, WorldCupCustomizationOption::query()->where('slug', 'standard')->count());
        $this->assertDatabaseHas('world_cup_customization_options', [
            'category_key' => 'headband',
            'type' => WorldCupCustomizationType::HeadbandMaterialsOption->value,
            'slug' => 'standard',
        ]);
        $this->assertDatabaseHas('world_cup_customization_options', [
            'category_key' => 'fan_cap',
            'type' => WorldCupCustomizationType::FanCapMaterialsOption->value,
            'slug' => 'standard',
        ]);
    }
}
