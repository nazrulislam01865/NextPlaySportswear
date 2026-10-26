from pathlib import Path
p=Path('/mnt/data/nextplay_logo_work/tests/Feature/Admin/StorefrontBrandingControlTest.php')
s=p.read_text()
assert s.rstrip().endswith('}')
s=s.rstrip()[:-1] + '''
    public function test_primary_logo_is_shown_on_admin_sidebar_and_admin_login(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')->put(route('admin.storefront-branding.update'), [
            'logo' => UploadedFile::fake()->image('site-logo.png', 600, 200),
        ])->assertSessionHasNoErrors();

        StorefrontBranding::flushCache();
        $url = StorefrontBranding::logoUrl();

        $this->get(route('admin.dashboard'))->assertOk()->assertSee($url, false);
        $this->get(route('admin.storefront-branding.edit'))->assertOk()->assertSee($url, false);

        auth('admin')->logout();
        $this->get(route('admin.login'))->assertOk()->assertSee($url, false);
    }

    public function test_separate_footer_logo_does_not_replace_site_logo_and_can_be_removed(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')->put(route('admin.storefront-branding.update'), [
            'logo' => UploadedFile::fake()->image('main-logo.png', 600, 200),
            'footer_logo' => UploadedFile::fake()->image('footer-logo.png', 600, 200),
        ])->assertSessionHasNoErrors();

        $setting = StorefrontBrandingSetting::query()->firstOrFail();
        $this->assertNotSame($setting->logo_path, $setting->footer_logo_path);
        Storage::disk('public')->assertExists($setting->logo_path);
        Storage::disk('public')->assertExists($setting->footer_logo_path);

        StorefrontBranding::flushCache();
        $this->assertNotSame(StorefrontBranding::logoUrl(), StorefrontBranding::footerLogoUrl());
        $this->get(route('home'))->assertOk()->assertSee(StorefrontBranding::logoUrl(), false)
            ->assertSee(StorefrontBranding::footerLogoUrl(), false);

        $footerPath = $setting->footer_logo_path;
        $mainPath = $setting->logo_path;
        $this->put(route('admin.storefront-branding.update'), [
            'remove_footer_logo' => '1',
        ])->assertSessionHasNoErrors();

        $setting->refresh();
        $this->assertNull($setting->footer_logo_path);
        $this->assertSame($mainPath, $setting->logo_path);
        Storage::disk('public')->assertMissing($footerPath);
        Storage::disk('public')->assertExists($mainPath);
        StorefrontBranding::flushCache();
        $this->assertSame(StorefrontBranding::logoUrl(), StorefrontBranding::footerLogoUrl());
    }

    public function test_replacing_only_footer_logo_preserves_main_logo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('branding/old-footer.png', 'footer');
        Storage::disk('public')->put('branding/old-main.png', 'main');
        StorefrontBrandingSetting::query()->create([
            'logo_path' => 'branding/old-main.png',
            'footer_logo_path' => 'branding/old-footer.png',
        ]);
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')->put(route('admin.storefront-branding.update'), [
            'footer_logo' => UploadedFile::fake()->image('new-footer.png', 600, 200),
        ])->assertSessionHasNoErrors();

        $setting = StorefrontBrandingSetting::query()->firstOrFail();
        $this->assertSame('branding/old-main.png', $setting->logo_path);
        Storage::disk('public')->assertExists('branding/old-main.png');
        Storage::disk('public')->assertMissing('branding/old-footer.png');
        Storage::disk('public')->assertExists($setting->footer_logo_path);
    }

    public function test_invalid_footer_logo_does_not_change_branding(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin, 'admin')->put(route('admin.storefront-branding.update'), [
            'footer_logo' => UploadedFile::fake()->create('unsafe.txt', 10, 'text/plain'),
        ])->assertSessionHasErrors('footer_logo');

        $this->assertDatabaseCount('storefront_branding_settings', 0);
    }
}
'''
p.write_text(s)
print('added focused tests')
