<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('homepage_sections')) {
            return;
        }

        DB::table('homepage_sections')
            ->where('key', 'shop_by_sport')
            ->where(function ($query): void {
                $query->whereNull('description')
                    ->orWhere('description', '')
                    ->orWhere('description', 'Choose sport categories, subcategories, or sub-subcategories from the admin catalog, or let this section load automatically.')
                    ->orWhere('description', 'Active sport categories from the admin catalog appear here automatically.');
            })
            ->update([
                'eyebrow' => 'Find your sport',
                'title' => 'Shop by Sport',
                'description' => 'Browse uniforms, apparel and gear by sport.',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('homepage_sections')) {
            return;
        }

        DB::table('homepage_sections')
            ->where('key', 'shop_by_sport')
            ->where('description', 'Browse uniforms, apparel and gear by sport.')
            ->update([
                'description' => 'Choose sport categories, subcategories, or sub-subcategories from the admin catalog, or let this section load automatically.',
                'updated_at' => now(),
            ]);
    }
};
