<?php

namespace Tests\Feature\Admin;

use App\Models\AboutPageSetting;
use App\Models\User;
use App\Support\AdminRbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AboutPageAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        AdminRbac::syncDefaults(true);
    }

    public function test_super_admin_can_open_and_update_about_page_editor(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')->get(route('admin.about-page.edit'))->assertOk();
        $this->actingAs($admin, 'admin')->put(route('admin.about-page.update'), $this->payload(['hero' => [
            'eyebrow' => 'NEXTPLAY SPORTSWEAR',
            'title' => 'UPDATED ABOUT',
            'description' => 'Updated hero description.',
        ]]))->assertRedirect(route('admin.about-page.edit'));

        $this->assertSame('UPDATED ABOUT', AboutPageSetting::query()->firstOrFail()->hero['title']);
    }

    public function test_admin_and_content_manager_receive_about_view_and_manage_defaults(): void
    {
        foreach (['admin', 'content_manager'] as $role) {
            $user = User::factory()->create(['role' => $role, 'is_active' => true]);
            $this->assertTrue($user->canAdmin('about_page.view'));
            $this->assertTrue($user->canAdmin('about_page.manage'));
        }
    }

    public function test_view_only_admin_can_open_editor_but_cannot_update(): void
    {
        $user = User::factory()->create(['role' => 'content_manager', 'is_active' => true]);
        $this->setPermission('content_manager', 'about_page.view', true);
        $this->setPermission('content_manager', 'about_page.manage', false);

        $this->actingAs($user, 'admin')->get(route('admin.about-page.edit'))->assertOk();
        $this->actingAs($user, 'admin')->put(route('admin.about-page.update'), $this->payload())->assertForbidden();
    }

    public function test_admin_without_view_permission_cannot_open_editor(): void
    {
        $user = User::factory()->create(['role' => 'content_manager', 'is_active' => true]);
        $this->setPermission('content_manager', 'about_page.view', false);

        $this->actingAs($user, 'admin')->get(route('admin.about-page.edit'))->assertForbidden();
    }

    public function test_about_page_sidebar_link_is_visible_only_with_view_permission(): void
    {
        $user = User::factory()->create(['role' => 'content_manager', 'is_active' => true]);
        $this->setPermission('content_manager', 'about_page.view', true);
        $this->actingAs($user, 'admin')->get(route('admin.dashboard'))->assertSee('About Page');

        $this->setPermission('content_manager', 'about_page.view', false);
        $this->actingAs($user->fresh(), 'admin')->get(route('admin.dashboard'))->assertDontSee('About Page');
    }

    public function test_failed_database_save_rolls_back_staged_uploads_and_preserves_previous_media(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('about-page/introduction/current.jpg', 'current');
        AboutPageSetting::query()->create([
            'introduction' => ['image_path' => 'about-page/introduction/current.jpg'],
        ]);
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        DB::listen(static function (): void {
            // Listener exists only to ensure DB is active in this integration test.
        });

        $payload = $this->payload();
        $payload['introduction_image'] = UploadedFile::fake()->image('new.jpg');

        // Force persistence failure by making updated_by violate the users FK after authentication.
        $admin->delete();
        $this->actingAs($admin, 'admin')->put(route('admin.about-page.update'), $payload);

        Storage::disk('public')->assertExists('about-page/introduction/current.jpg');
        $this->assertSame('about-page/introduction/current.jpg', AboutPageSetting::query()->firstOrFail()->introduction['image_path']);
    }

    private function setPermission(string $roleSlug, string $permissionKey, bool $allowed): void
    {
        $roleId = DB::table('admin_roles')->where('slug', $roleSlug)->value('id');
        $permissionId = DB::table('admin_permissions')->where('key', $permissionKey)->value('id');
        DB::table('admin_role_permissions')
            ->where('role_id', $roleId)
            ->where('permission_id', $permissionId)
            ->update(['allowed' => $allowed]);
    }

    private function payload(array $overrides = []): array
    {
        $base = [
            'hero' => ['eyebrow' => 'NEXTPLAY SPORTSWEAR', 'title' => 'ABOUT NEXTPLAY', 'description' => 'Sportswear for teams, clubs and people who love to play.'],
            'introduction' => ['title' => 'Intro title', 'description' => 'Intro description', 'image_alt' => 'Intro image'],
            'what_we_do' => ['title' => 'WHAT WE DO', 'cards' => [
                ['id' => 'custom-teamwear', 'title' => 'Custom Teamwear', 'description' => 'Description', 'icon_alt' => 'Teamwear icon'],
                ['id' => 'sportswear-gear', 'title' => 'Sportswear & Gear', 'description' => 'Description', 'icon_alt' => 'Sportswear icon'],
                ['id' => 'bulk-orders', 'title' => 'Bulk Orders', 'description' => 'Description', 'icon_alt' => 'Bulk icon'],
            ]],
            'how_we_work' => ['title' => 'HOW WE WORK', 'steps' => [
                ['id' => 'choose-product', 'number' => '1', 'title' => 'Choose', 'description' => 'Description', 'icon_alt' => 'Choose icon'],
                ['id' => 'personalise', 'number' => '2', 'title' => 'Personalise', 'description' => 'Description', 'icon_alt' => 'Personalise icon'],
                ['id' => 'review-details', 'number' => '3', 'title' => 'Review', 'description' => 'Description', 'icon_alt' => 'Review icon'],
                ['id' => 'place-order', 'number' => '4', 'title' => 'Order', 'description' => 'Description', 'icon_alt' => 'Order icon'],
            ]],
            'gallery' => ['items' => [
                ['id' => 'team', 'image_alt' => 'Team'], ['id' => 'fabric', 'image_alt' => 'Fabric'], ['id' => 'number', 'image_alt' => 'Number'], ['id' => 'celebration', 'image_alt' => 'Celebration'],
            ]],
            'cta' => ['eyebrow' => 'MADE FOR YOUR TEAM', 'title' => 'CTA title', 'primary_label' => 'EXPLORE PRODUCTS', 'primary_url' => '/products', 'secondary_label' => 'REQUEST A BULK QUOTE', 'secondary_url' => '/bulk-quote'],
            'help' => ['title' => 'NEED HELP?', 'description' => 'Help description', 'icon_alt' => 'Help icon', 'button_label' => 'Contact Us', 'button_url' => '/contact-us'],
            'seo' => ['title' => 'About NextPlay', 'description' => 'SEO description'],
        ];

        return array_replace_recursive($base, $overrides);
    }
}
