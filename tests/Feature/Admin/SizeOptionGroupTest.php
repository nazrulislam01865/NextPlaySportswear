<?php

namespace Tests\Feature\Admin;

use App\Enums\SizeAudience;
use App\Models\SizeOptionGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SizeOptionGroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_reusable_size_group_with_formatted_size_chart(): void
    {
        $admin = User::factory()->create(['role' => 'catalog_manager', 'is_active' => true]);

        $response = $this->actingAs($admin, 'admin')->post(
            route('admin.size-option-groups.store'),
            [
                'name' => 'Adult Male',
                'audience' => SizeAudience::Male->value,
                'description_html' => '<p><strong>Measure around the chest.</strong></p><script>alert(1)</script>',
                'chart_html' => '<h3>Adult Male Size Chart</h3><table><thead><tr><th>Size</th><th>Chest</th></tr></thead><tbody><tr><td>S</td><td>36-38</td></tr></tbody></table><script>alert(1)</script>',
                'clear_chart_image' => '0',
                'sizes' => [
                    ['label' => 'S'],
                    ['label' => 'M'],
                ],
            ]
        );

        $group = SizeOptionGroup::query()->with('sizes')->firstOrFail();

        $response->assertRedirect(route('admin.size-option-groups.edit', $group));
        $this->assertSame(SizeAudience::Male, $group->audience);
        $this->assertStringContainsString('<table>', (string) $group->chart_html);
        $this->assertStringNotContainsString('<script', (string) $group->chart_html);
        $this->assertNull($group->chart_image_path);
        $this->assertNull($group->chart_image_url);
        $this->assertCount(2, $group->sizes);
        $this->assertStringNotContainsString('<script', (string) $group->description_html);
    }

    public function test_formatted_chart_and_chart_image_cannot_be_submitted_together(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')->post(
            route('admin.size-option-groups.store'),
            [
                'name' => 'Adult Female',
                'audience' => SizeAudience::Female->value,
                'chart_html' => '<table><tr><td>S</td></tr></table>',
                'chart_image' => UploadedFile::fake()->image('chart.png'),
                'clear_chart_image' => '0',
                'sizes' => [['label' => 'S']],
            ]
        )->assertSessionHasErrors('chart_html');
    }

    public function test_duplicate_size_labels_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')->post(
            route('admin.size-option-groups.store'),
            [
                'name' => 'Youth',
                'audience' => SizeAudience::Youth->value,
                'clear_chart_image' => '0',
                'sizes' => [
                    ['label' => 'M'],
                    ['label' => 'm'],
                ],
            ]
        )->assertSessionHasErrors('sizes.1.label');
    }
}
