<?php

namespace Tests\Feature;

use App\Models\AboutPageSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AboutPageManagedContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_renders_every_saved_about_text_section_and_seo_value(): void
    {
        AboutPageSetting::query()->create($this->managedPayload());

        $response = $this->get(route('about'))->assertOk();

        foreach ([
            'Managed Eyebrow', 'Managed Hero', 'Managed hero description',
            'Managed intro title', 'Managed intro description',
            'Managed What We Do', 'Service One', 'Service Two', 'Service Three',
            'Managed How We Work', 'Step One', 'Step Two', 'Step Three', 'Step Four',
            'Managed CTA Eyebrow', 'Managed CTA Title', 'Primary Managed', 'Secondary Managed',
            'Managed Help', 'Managed help description', 'Managed Contact',
            'Managed SEO Title', 'Managed SEO Description',
        ] as $text) {
            $response->assertSee($text);
        }
    }

    public function test_storefront_renders_custom_intro_service_process_gallery_and_help_media_urls(): void
    {
        Storage::fake('public');
        foreach ($this->allMediaPaths() as $path) {
            Storage::disk('public')->put($path, 'image');
        }

        $payload = $this->managedPayload();
        $payload['introduction']['image_path'] = 'about-page/introduction/custom.jpg';
        foreach ($payload['what_we_do']['cards'] as $i => &$card) {
            $card['icon_path'] = "about-page/what-we-do/icons/service-$i.png";
        }
        unset($card);
        foreach ($payload['how_we_work']['steps'] as $i => &$step) {
            $step['icon_path'] = "about-page/how-we-work/icons/process-$i.png";
        }
        unset($step);
        foreach ($payload['gallery']['items'] as $i => &$item) {
            $item['image_path'] = "about-page/gallery/gallery-$i.jpg";
        }
        unset($item);
        $payload['help']['icon_path'] = 'about-page/help/icons/help.png';
        AboutPageSetting::query()->create($payload);

        $html = $this->get(route('about'))->assertOk()->getContent();
        foreach ($this->allMediaPaths() as $path) {
            $this->assertStringContainsString('/media/'.str_replace(' ', '%20', $path), $html);
        }
    }

    public function test_storefront_uses_builtin_icons_and_shipped_images_when_custom_media_is_removed(): void
    {
        AboutPageSetting::query()->create($this->managedPayload());

        $html = $this->get(route('about'))->assertOk()->getContent();
        $this->assertStringContainsString('images/storefront/about/team-intro.webp', $html);
        $this->assertStringContainsString('images/storefront/about/gallery-team.webp', $html);
        $this->assertStringNotContainsString('/media/about-page/', $html);
        $this->assertStringContainsString('<svg', $html);
    }

    public function test_storefront_defaults_render_when_about_settings_row_is_absent(): void
    {
        $this->get(route('about'))
            ->assertOk()
            ->assertSee('ABOUT NEXTPLAY')
            ->assertSee('Custom Teamwear')
            ->assertSee('Place your order')
            ->assertSee('NEED HELP?');
    }

    public function test_storefront_escapes_admin_managed_html_in_text_fields(): void
    {
        $payload = $this->managedPayload();
        $payload['hero']['title'] = '<script>alert(1)</script>Managed Hero';
        AboutPageSetting::query()->create($payload);

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;Managed Hero', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_storefront_keeps_exact_fixed_section_sequence_and_card_step_gallery_counts(): void
    {
        AboutPageSetting::query()->create($this->managedPayload());
        $html = $this->get(route('about'))->assertOk()->getContent();

        $positions = array_map(fn (string $needle): int => strpos($html, $needle), [
            'np-about-hero', 'np-about-intro', 'np-about-services-grid', 'np-about-process-grid', 'np-about-gallery', 'np-about-cta', 'np-about-help',
        ]);
        $this->assertSame($positions, [...$positions]);
        $sorted = $positions;
        sort($sorted);
        $this->assertSame($sorted, $positions);
        $this->assertSame(3, substr_count($html, 'class="np-about-service-card"'));
        $this->assertSame(4, substr_count($html, 'np-about-process-step-wrap'));
        $this->assertSame(4, substr_count($html, 'np-about-gallery__item np-about-gallery__item--'));
    }

    private function managedPayload(): array
    {
        return [
            'hero' => ['eyebrow' => 'Managed Eyebrow', 'title' => 'Managed Hero', 'description' => 'Managed hero description'],
            'introduction' => ['title' => 'Managed intro title', 'description' => 'Managed intro description', 'image_path' => null, 'image_alt' => 'Managed intro alt'],
            'what_we_do' => ['title' => 'Managed What We Do', 'cards' => [
                ['id' => 'custom-teamwear', 'title' => 'Service One', 'description' => 'Service one description', 'icon_path' => null, 'icon_alt' => 'Service one alt'],
                ['id' => 'sportswear-gear', 'title' => 'Service Two', 'description' => 'Service two description', 'icon_path' => null, 'icon_alt' => 'Service two alt'],
                ['id' => 'bulk-orders', 'title' => 'Service Three', 'description' => 'Service three description', 'icon_path' => null, 'icon_alt' => 'Service three alt'],
            ]],
            'how_we_work' => ['title' => 'Managed How We Work', 'steps' => [
                ['id' => 'choose-product', 'number' => 'A', 'title' => 'Step One', 'description' => 'Step one description', 'icon_path' => null, 'icon_alt' => 'Step one alt'],
                ['id' => 'personalise', 'number' => 'B', 'title' => 'Step Two', 'description' => 'Step two description', 'icon_path' => null, 'icon_alt' => 'Step two alt'],
                ['id' => 'review-details', 'number' => 'C', 'title' => 'Step Three', 'description' => 'Step three description', 'icon_path' => null, 'icon_alt' => 'Step three alt'],
                ['id' => 'place-order', 'number' => 'D', 'title' => 'Step Four', 'description' => 'Step four description', 'icon_path' => null, 'icon_alt' => 'Step four alt'],
            ]],
            'gallery' => ['items' => [
                ['id' => 'team', 'image_path' => null, 'image_alt' => 'Managed team alt'],
                ['id' => 'fabric', 'image_path' => null, 'image_alt' => 'Managed fabric alt'],
                ['id' => 'number', 'image_path' => null, 'image_alt' => 'Managed number alt'],
                ['id' => 'celebration', 'image_path' => null, 'image_alt' => 'Managed celebration alt'],
            ]],
            'cta' => ['eyebrow' => 'Managed CTA Eyebrow', 'title' => 'Managed CTA Title', 'primary_label' => 'Primary Managed', 'primary_url' => '/products', 'secondary_label' => 'Secondary Managed', 'secondary_url' => '/bulk-quote'],
            'help' => ['title' => 'Managed Help', 'description' => 'Managed help description', 'icon_path' => null, 'icon_alt' => 'Managed help alt', 'button_label' => 'Managed Contact', 'button_url' => '/contact-us'],
            'seo' => ['title' => 'Managed SEO Title', 'description' => 'Managed SEO Description'],
        ];
    }

    private function allMediaPaths(): array
    {
        return [
            'about-page/introduction/custom.jpg',
            'about-page/what-we-do/icons/service-0.png', 'about-page/what-we-do/icons/service-1.png', 'about-page/what-we-do/icons/service-2.png',
            'about-page/how-we-work/icons/process-0.png', 'about-page/how-we-work/icons/process-1.png', 'about-page/how-we-work/icons/process-2.png', 'about-page/how-we-work/icons/process-3.png',
            'about-page/gallery/gallery-0.jpg', 'about-page/gallery/gallery-1.jpg', 'about-page/gallery/gallery-2.jpg', 'about-page/gallery/gallery-3.jpg',
            'about-page/help/icons/help.png',
        ];
    }
}
