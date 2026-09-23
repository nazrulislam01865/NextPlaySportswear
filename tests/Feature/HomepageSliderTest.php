<?php

namespace Tests\Feature;

use App\Models\HomepageSlide;
use App\Services\Storefront\HomepageSliderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageSliderTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_admin_managed_slide_is_rendered_on_homepage(): void
    {
        HomepageSlide::query()->create([
            'eyebrow' => 'Summer teams',
            'title' => 'Custom Summer Team Gear',
            'description' => 'A database-managed homepage promotion.',
            'image_url' => 'https://example.com/slider.webp',
            'image_alt' => 'Team wearing summer sportswear',
            'show_content' => true,
            'show_eyebrow' => true,
            'show_title' => true,
            'show_description' => true,
            'show_primary_button' => true,
            'primary_label' => 'Shop Gear',
            'primary_url' => '/products',
            'primary_target' => '_self',
            'show_secondary_button' => false,
            'secondary_target' => '_self',
            'image_focal_position' => 'center',
            'content_position' => 'left',
            'text_alignment' => 'left',
            'text_theme' => 'light',
            'overlay_color' => '#0D2545',
            'overlay_opacity' => 70,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        app(HomepageSliderService::class)->flushCache();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Custom Summer Team Gear')
            ->assertSee('Shop Gear');
    }

    public function test_image_only_slide_mutes_all_overlay_text(): void
    {
        HomepageSlide::query()->create([
            'title' => 'This text must stay hidden',
            'image_url' => 'https://example.com/image-only.webp',
            'image_alt' => 'Image only promotion',
            'show_content' => false,
            'show_eyebrow' => true,
            'show_title' => true,
            'show_description' => true,
            'show_primary_button' => true,
            'primary_target' => '_self',
            'show_secondary_button' => false,
            'secondary_target' => '_self',
            'image_focal_position' => 'center',
            'content_position' => 'left',
            'text_alignment' => 'left',
            'text_theme' => 'light',
            'overlay_color' => '#0D2545',
            'overlay_opacity' => 0,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        app(HomepageSliderService::class)->flushCache();

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('This text must stay hidden')
            ->assertSee('Image only promotion');
    }

    public function test_inactive_or_expired_slides_are_not_rendered(): void
    {
        foreach ([
            ['title' => 'Inactive Promotion', 'is_active' => false, 'starts_at' => null, 'ends_at' => null],
            ['title' => 'Expired Promotion', 'is_active' => true, 'starts_at' => now()->subDays(2), 'ends_at' => now()->subDay()],
        ] as $data) {
            HomepageSlide::query()->create(array_merge([
                'image_url' => 'https://example.com/hidden.webp',
                'image_alt' => 'Hidden promotion',
                'show_content' => true,
                'show_eyebrow' => false,
                'show_title' => true,
                'show_description' => false,
                'show_primary_button' => false,
                'primary_target' => '_self',
                'show_secondary_button' => false,
                'secondary_target' => '_self',
                'image_focal_position' => 'center',
                'content_position' => 'left',
                'text_alignment' => 'left',
                'text_theme' => 'light',
                'overlay_color' => '#0D2545',
                'overlay_opacity' => 70,
                'sort_order' => 10,
            ], $data));
        }

        app(HomepageSliderService::class)->flushCache();

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Inactive Promotion')
            ->assertDontSee('Expired Promotion');
    }
}
