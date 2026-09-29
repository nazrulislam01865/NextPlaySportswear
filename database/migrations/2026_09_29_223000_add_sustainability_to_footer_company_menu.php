<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('menus') || ! Schema::hasTable('menu_items')) {
            return;
        }

        $menu = DB::table('menus')->where('location', 'footer-company')->first();

        if (! $menu) {
            return;
        }

        $exists = DB::table('menu_items')
            ->where('menu_id', $menu->id)
            ->whereNull('parent_id')
            ->where('link_type', 'route')
            ->where('route_name', 'sustainability')
            ->exists();

        if (! $exists) {
            $nextSortOrder = ((int) DB::table('menu_items')
                ->where('menu_id', $menu->id)
                ->whereNull('parent_id')
                ->max('sort_order')) + 1;

            $now = now();

            DB::table('menu_items')->insert([
                'menu_id' => $menu->id,
                'parent_id' => null,
                'label' => 'Sustainability',
                'link_type' => 'route',
                'category_id' => null,
                'route_name' => 'sustainability',
                'url' => null,
                'target' => '_self',
                'css_class' => null,
                'is_active' => true,
                'sort_order' => $nextSortOrder,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Cache::forget('catalog.navigation.v10.footer-company');
        Cache::forget('catalog.navigation.footer-company');
    }

    public function down(): void
    {
        if (! Schema::hasTable('menus') || ! Schema::hasTable('menu_items')) {
            return;
        }

        $menuId = DB::table('menus')->where('location', 'footer-company')->value('id');

        if ($menuId) {
            DB::table('menu_items')
                ->where('menu_id', $menuId)
                ->whereNull('parent_id')
                ->where('label', 'Sustainability')
                ->where('link_type', 'route')
                ->where('route_name', 'sustainability')
                ->delete();
        }

        Cache::forget('catalog.navigation.v10.footer-company');
        Cache::forget('catalog.navigation.footer-company');
    }
};
