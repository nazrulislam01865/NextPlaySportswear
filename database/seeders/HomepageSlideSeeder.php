<?php

namespace Database\Seeders;

use App\Models\HomepageSlide;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class HomepageSlideSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('homepage_slides')) {
            return;
        }

        if (HomepageSlide::query()->exists()) {
            return;
        }

        $slides = [
            [
                'title' => 'Build Your Team Jersey with Name, Number, and Logo',
                'eyebrow' => 'Custom jerseys USA',
                'description' => 'Choose colors, add player details, upload your artwork, and prepare your order for teams, schools, clubs, and fans.',
                'image_url' => 'https://images.unsplash.com/photo-1519861531473-9200262188bf?auto=format&fit=crop&w=1600&q=80',
                'image_alt' => 'Custom jerseys and sportswear for team order',
                'primary_label' => 'Shop Custom Jerseys',
                'primary_url' => '#products',
                'secondary_label' => 'Request Team Quote',
                'secondary_url' => '#bulk',
                'show_secondary_button' => true,
                'sort_order' => 10,
            ],
            [
                'title' => 'Uniform Sets for Schools, Leagues, and Clubs',
                'eyebrow' => 'Team uniforms',
                'description' => 'Order full team sets with size lists, player names, numbers, colors, and design support before production.',
                'image_url' => 'https://images.unsplash.com/photo-1551958219-acbc608c6377?auto=format&fit=crop&w=1600&q=80',
                'image_alt' => 'Custom team uniforms hanging in locker room',
                'primary_label' => 'Shop Uniforms',
                'primary_url' => '#categories',
                'secondary_label' => 'How It Works',
                'secondary_url' => '#process',
                'show_secondary_button' => true,
                'sort_order' => 20,
            ],
            [
                'title' => 'Bulk Sportswear for Events, Businesses, and Teams',
                'eyebrow' => 'Bulk orders',
                'description' => 'Get pricing support for larger quantities, promotional products, caps, bags, hoodies, jerseys, and event apparel.',
                'image_url' => 'https://images.unsplash.com/photo-1526232761682-d26e03ac148e?auto=format&fit=crop&w=1600&q=80',
                'image_alt' => 'Players on field for sports team event',
                'primary_label' => 'Request Bulk Quote',
                'primary_url' => '#bulk',
                'secondary_label' => 'View Team Gear',
                'secondary_url' => '#gear',
                'show_secondary_button' => true,
                'sort_order' => 30,
            ],
        ];

        foreach ($slides as $slide) {
            HomepageSlide::query()->create(array_merge([
                'show_content' => true,
                'show_eyebrow' => true,
                'show_title' => true,
                'show_description' => true,
                'show_primary_button' => true,
                'primary_target' => '_self',
                'secondary_target' => '_self',
                'image_focal_position' => 'center',
                'content_position' => 'left',
                'text_alignment' => 'left',
                'text_theme' => 'light',
                'overlay_color' => '#0D2545',
                'overlay_opacity' => 72,
                'is_active' => true,
            ], $slide));
        }

        Cache::forget('storefront.homepage-slider.v1');
    }
}
