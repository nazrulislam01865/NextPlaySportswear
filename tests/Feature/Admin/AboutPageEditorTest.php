<?php

namespace Tests\Feature\Admin;

use App\Models\AboutPageSetting;
use App\Models\User;
use App\Support\AdminRbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AboutPageEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        AdminRbac::syncDefaults(true);
    }

    public function test_editor_renders_all_seven_fixed_sections_and_seo_fields(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $html = $this->actingAs($admin, 'admin')->get(route('admin.about-page.edit'))->assertOk()->getContent();

        foreach (['Hero', 'Introduction', 'What We Do', 'How We Work', 'Gallery', 'Made for Your Team', 'Need Help', 'SEO'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
        $this->assertStringContainsString('name="seo[title]"', $html);
        $this->assertStringContainsString('name="seo[description]"', $html);
    }

    public function test_editor_renders_three_service_card_editors_four_process_editors_and_four_gallery_editors(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $html = $this->actingAs($admin, 'admin')->get(route('admin.about-page.edit'))->getContent();

        $this->assertSame(3, substr_count($html, 'data-about-service-editor'));
        $this->assertSame(4, substr_count($html, 'data-about-process-editor'));
        $this->assertSame(4, substr_count($html, 'data-about-gallery-editor'));
    }

    public function test_editor_contains_upload_replace_remove_and_alt_controls_for_every_required_media_slot(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $html = $this->actingAs($admin, 'admin')->get(route('admin.about-page.edit'))->getContent();

        $this->assertSame(13, preg_match_all('/type="file"/', $html));
        $this->assertStringContainsString('name="introduction_image"', $html);
        $this->assertStringContainsString('name="help_icon"', $html);
        for ($i = 0; $i < 3; $i++) {
            $this->assertStringContainsString('name="service_icon_'.$i.'"', $html);
        }
        for ($i = 0; $i < 4; $i++) {
            $this->assertStringContainsString('name="process_icon_'.$i.'"', $html);
            $this->assertStringContainsString('name="gallery_image_'.$i.'"', $html);
        }
    }

    public function test_editor_contains_no_section_reorder_add_delete_or_visibility_controls(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $html = strtolower($this->actingAs($admin, 'admin')->get(route('admin.about-page.edit'))->getContent());

        $this->assertStringNotContainsString('reorder section', $html);
        $this->assertStringNotContainsString('add section', $html);
        $this->assertStringNotContainsString('delete section', $html);
        $this->assertStringNotContainsString('section visibility', $html);
    }

    public function test_view_only_admin_sees_current_content_but_update_controls_are_disabled_or_absent(): void
    {
        $admin = User::factory()->create(['role' => 'content_manager', 'is_active' => true]);
        $this->setPermission('content_manager', 'about_page.manage', false);

        $html = $this->actingAs($admin, 'admin')->get(route('admin.about-page.edit'))->assertOk()->getContent();
        $this->assertStringContainsString('ABOUT NEXTPLAY', $html);
        $this->assertStringNotContainsString('>Save About Page<', str_replace("\n", '', $html));
    }

    public function test_validation_errors_preserve_old_text_input_and_current_media_previews(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        AboutPageSetting::query()->create([
            'introduction' => ['image_path' => null, 'image_alt' => 'Persisted alt'],
        ]);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.about-page.edit'))
            ->put(route('admin.about-page.update'), ['hero' => ['title' => 'Typed but invalid']])
            ->assertSessionHasErrors();

        $this->actingAs($admin, 'admin')->get(route('admin.about-page.edit'))
            ->assertSee('Typed but invalid')
            ->assertSee('team-intro.webp', false);
    }

    private function setPermission(string $roleSlug, string $permissionKey, bool $allowed): void
    {
        $roleId = DB::table('admin_roles')->where('slug', $roleSlug)->value('id');
        $permissionId = DB::table('admin_permissions')->where('key', $permissionKey)->value('id');
        DB::table('admin_role_permissions')->where('role_id', $roleId)->where('permission_id', $permissionId)->update(['allowed' => $allowed]);
    }
}
