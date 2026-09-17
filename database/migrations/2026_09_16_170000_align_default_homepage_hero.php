<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('homepage_slides')) {
            return;
        }

        // Only upgrade the untouched legacy seed. Admin-authored homepage slides are preserved.
        DB::table('homepage_slides')
            ->where('title', 'Build Your Team Jersey with Name, Number, and Logo')
            ->where('image_url', 'like', '%photo-1519861531473-9200262188bf%')
            ->update([
                'title' => 'YOUR TEAM YOUR KITS',
                'eyebrow' => null,
                'description' => 'Performance sportswear for players, teams and clubs — from training essentials to match-day kits.',
                'image_path' => null,
                'image_url' => 'https://i.pinimg.com/originals/8c/a5/a0/8ca5a08d215145b191f8a3eda395c50b.jpg',
                'image_alt' => 'Two basketball players competing on an outdoor court',
                'image_focal_position' => 'center',
                'show_content' => true,
                'show_eyebrow' => false,
                'show_title' => true,
                'show_description' => true,
                'show_primary_button' => true,
                'primary_label' => 'SHOP PRODUCTS',
                'primary_url' => '/products',
                'primary_target' => '_self',
                'show_secondary_button' => true,
                'secondary_label' => 'CUSTOMIZE YOUR GEAR',
                'secondary_url' => '/products',
                'secondary_target' => '_self',
                'content_position' => 'left',
                'text_alignment' => 'left',
                'text_theme' => 'light',
                'overlay_color' => '#0D2545',
                'overlay_opacity' => 28,
                'sort_order' => 10,
                'updated_at' => now(),
            ]);

        Cache::forget('storefront.homepage-slider.v1');
        Cache::forget('storefront.homepage-slider.v2');
    }

    public function down(): void
    {
        // Visual-content migration is intentionally non-destructive on rollback.
    }
};
