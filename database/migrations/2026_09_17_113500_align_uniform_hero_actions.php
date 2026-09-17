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

        // Update only the untouched seeded uniform slide. Admin-authored slide copy stays intact.
        DB::table('homepage_slides')
            ->where('title', 'Uniform Sets for Schools, Leagues, and Clubs')
            ->where('primary_label', 'Shop Uniforms')
            ->where('secondary_label', 'How It Works')
            ->update([
                'title' => 'YOUR TEAM YOUR KITS',
                'eyebrow' => null,
                'description' => 'Performance sportswear for players, teams and clubs — from training essentials to match-day kits.',
                'show_eyebrow' => false,
                'primary_label' => 'SHOP PRODUCTS',
                'primary_url' => '/products',
                'secondary_label' => 'CUSTOMIZE YOUR GEAR',
                'secondary_url' => '/products',
                'updated_at' => now(),
            ]);

        Cache::forget('storefront.homepage-slider.v1');
        Cache::forget('storefront.homepage-slider.v2');
    }

    public function down(): void
    {
        // Content alignment is intentionally non-destructive on rollback.
    }
};
