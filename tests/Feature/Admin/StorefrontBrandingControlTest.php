<?php

namespace Tests\Feature\Admin;

use App\Models\StorefrontBrandingSetting;
use App\Models\User;
use App\Support\StorefrontBranding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorefrontBrandingControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_upload_a_storefront_logo_and_it_is_used_on_storefront(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $response = $this->actingAs($admin, 'admin')->put(route('admin.storefront-branding.update'), [
            'logo' => UploadedFile::fake()->image('nextplay-logo.png', 600, 200),
        ]);

        $response->assertRedirect(route('admin.storefront-branding.edit'));
        $response->assertSessionHasNoErrors();

        $setting = StorefrontBrandingSetting::query()->firstOrFail();
        $this->assertNotNull($setting->logo_path);
        Storage::disk('public')->assertExists($setting->logo_path);

        StorefrontBranding::flushCache();
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(StorefrontBranding::logoUrl(), false);
    }

    public function test_replacing_logo_deletes_only_the_previous_branding_upload(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('branding/old-logo.png', 'old');

        StorefrontBrandingSetting::query()->create(['logo_path' => 'branding/old-logo.png']);
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')->put(route('admin.storefront-branding.update'), [
            'logo' => UploadedFile::fake()->image('replacement.png', 800, 250),
        ])->assertSessionHasNoErrors();

        $setting = StorefrontBrandingSetting::query()->firstOrFail();
        $this->assertNotSame('branding/old-logo.png', $setting->logo_path);
        Storage::disk('public')->assertMissing('branding/old-logo.png');
        Storage::disk('public')->assertExists($setting->logo_path);
    }

    public function test_admin_can_remove_custom_logo_and_fallback_logo_remains_available(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('branding/custom-logo.png', 'logo');
        StorefrontBrandingSetting::query()->create(['logo_path' => 'branding/custom-logo.png']);
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')->put(route('admin.storefront-branding.update'), [
            'remove_logo' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertNull(StorefrontBrandingSetting::query()->firstOrFail()->logo_path);
        Storage::disk('public')->assertMissing('branding/custom-logo.png');

        StorefrontBranding::flushCache();
        $this->assertStringContainsString('/images/logo.png', StorefrontBranding::logoUrl());
    }

    public function test_invalid_non_image_logo_is_rejected(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')->from(route('admin.storefront-branding.edit'))->put(route('admin.storefront-branding.update'), [
            'logo' => UploadedFile::fake()->create('logo.txt', 10, 'text/plain'),
        ])->assertSessionHasErrors('logo');

        $this->assertDatabaseCount('storefront_branding_settings', 0);
    }
    public function test_super_admin_can_upload_favicon_without_changing_logo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('branding/current-logo.png', 'logo');
        StorefrontBrandingSetting::query()->create(['logo_path' => 'branding/current-logo.png']);
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')->put(route('admin.storefront-branding.update'), [
            'favicon' => UploadedFile::fake()->image('nextplay-favicon.png', 32, 32),
        ])->assertSessionHasNoErrors();

        $setting = StorefrontBrandingSetting::query()->firstOrFail();
        $this->assertSame('branding/current-logo.png', $setting->logo_path);
        Storage::disk('public')->assertExists($setting->favicon_path);
        Storage::disk('public')->assertExists($setting->logo_path);

        StorefrontBranding::flushCache();
        $this->get(route('home'))->assertOk()->assertSee(StorefrontBranding::faviconUrl(), false);
    }

    public function test_replacing_favicon_removes_previous_favicon_but_retains_logo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('branding/old-favicon.png', 'favicon');
        Storage::disk('public')->put('branding/custom-logo.png', 'logo');
        StorefrontBrandingSetting::query()->create([
            'favicon_path' => 'branding/old-favicon.png',
            'logo_path' => 'branding/custom-logo.png',
        ]);
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')->put(route('admin.storefront-branding.update'), [
            'favicon' => UploadedFile::fake()->image('replacement-favicon.png', 48, 48),
        ])->assertSessionHasNoErrors();

        $setting = StorefrontBrandingSetting::query()->firstOrFail();
        Storage::disk('public')->assertMissing('branding/old-favicon.png');
        Storage::disk('public')->assertExists($setting->favicon_path);
        Storage::disk('public')->assertExists('branding/custom-logo.png');
    }

    public function test_removing_favicon_restores_default_without_touching_logo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('branding/custom-favicon.png', 'favicon');
        StorefrontBrandingSetting::query()->create(['favicon_path' => 'branding/custom-favicon.png']);
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')->put(route('admin.storefront-branding.update'), [
            'remove_favicon' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertNull(StorefrontBrandingSetting::query()->firstOrFail()->favicon_path);
        Storage::disk('public')->assertMissing('branding/custom-favicon.png');
        StorefrontBranding::flushCache();
        $this->assertStringContainsString('/images/logo.png', StorefrontBranding::faviconUrl());
    }

    public function test_invalid_favicon_is_rejected(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')->from(route('admin.storefront-branding.edit'))
            ->put(route('admin.storefront-branding.update'), [
                'favicon' => UploadedFile::fake()->create('malicious.txt', 10, 'text/plain'),
            ])->assertSessionHasErrors('favicon');

        $this->assertDatabaseCount('storefront_branding_settings', 0);
    }

}
