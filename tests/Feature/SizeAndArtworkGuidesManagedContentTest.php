<?php

namespace Tests\Feature;

use App\Models\StorefrontPageSetting;
use App\Models\User;
use App\Services\Storefront\ArtworkGuidelinesPageService;
use App\Services\Storefront\SizeGuidePageService;
use App\Support\AdminRbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SizeAndArtworkGuidesManagedContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_size_guide_uses_the_new_layout_and_preserves_existing_reference_chart_values(): void
    {
        $this->get(route('size-guide'))
            ->assertOk()
            ->assertSee('np-size-card')
            ->assertSee('On this page')
            ->assertSee('Adult tops')
            ->assertSee('Youth tops')
            ->assertSee('Bottoms')
            ->assertSee('32–34 in')
            ->assertSee('Contact Support');
    }

    public function test_size_guide_editor_saves_and_public_page_reads_admin_content(): void
    {
        AdminRbac::syncDefaults(true);
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $payload = app(SizeGuidePageService::class)->defaults();
        $payload['hero']['title'] = 'Updated Team Size Guide';
        $payload['charts'][0]['rows'][0][1] = '33–35 in';
        $this->actingAs($admin, 'admin')
            ->put(route('admin.size-guide-page.update'), $payload)
            ->assertRedirect(route('admin.size-guide-page.edit'));

        $this->assertSame('Updated Team Size Guide', StorefrontPageSetting::query()->where('page_key', 'size-guide')->firstOrFail()->title);
        $this->get(route('size-guide'))
            ->assertOk()
            ->assertSee('Updated Team Size Guide')
            ->assertSee('33–35 in');
    }

    public function test_size_guide_rejects_invalid_chart_rows_without_saving(): void
    {
        AdminRbac::syncDefaults(true);
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $payload = app(SizeGuidePageService::class)->defaults();
        $payload['charts'][0]['rows'][0] = ['XS', 'invalid'];
        $this->actingAs($admin, 'admin')->put(route('admin.size-guide-page.update'), $payload)
            ->assertSessionHasErrors('charts.0.rows.0');
        $this->assertDatabaseMissing('storefront_page_settings', ['page_key' => 'size-guide']);
    }

    public function test_artwork_guidelines_still_uses_managed_six_section_design(): void
    {
        $defaults = app(ArtworkGuidelinesPageService::class)->defaults();
        StorefrontPageSetting::query()->create([
            'page_key' => 'artwork-guidelines',
            'title' => 'Artwork Guidelines',
            'content' => ['hero' => ['title' => 'Managed Artwork Guide']],
            'seo' => $defaults['seo'],
        ]);
        $this->get(route('artwork-guidelines'))
            ->assertOk()
            ->assertSee('Managed Artwork Guide')
            ->assertSee('np-artwork-card')
            ->assertSee('File formats')
            ->assertSee('Send your files');
    }
}
