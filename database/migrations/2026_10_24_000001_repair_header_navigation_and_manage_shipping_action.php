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

        DB::transaction(function (): void {
            $menu = DB::table('menus')->where('location', 'header-primary')->first(['id']);
            if (! $menu) {
                // A fresh installation uses NavigationService's default
                // shipping action until the primary menu is created.
                return;
            }

            $items = DB::table('menu_items')
                ->where('menu_id', $menu->id)
                ->whereNull('parent_id')
                ->orderByDesc('is_active')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            $seen = [];
            foreach ($items as $item) {
                // The first active link wins. A CSS class, target, or visibility
                // difference does not justify three identical destinations.
                $destination = match ($item->link_type) {
                    'category' => (string) ($item->category_id ?? ''),
                    'route' => trim((string) ($item->route_name ?? '')),
                    'custom' => trim((string) ($item->url ?? '')),
                    default => '',
                };
                $signature = implode('|', [
                    mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $item->label))),
                    (string) $item->link_type,
                    $destination,
                ]);
                if (! isset($seen[$signature])) {
                    $seen[$signature] = $item->id;
                    continue;
                }

                $keptId = $seen[$signature];
                DB::table('menu_items')->where('parent_id', $item->id)->update(['parent_id' => $keptId]);
                DB::table('menu_items')->where('id', $item->id)->delete();
            }

            $shipping = DB::table('menu_items')
                ->where('menu_id', $menu->id)
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->get(['id', 'label', 'css_class', 'route_name', 'sort_order'])
                ->first(fn ($item): bool => in_array('np-shipping-action', preg_split('/\s+/', trim((string) $item->css_class)) ?: [], true));

            if (! $shipping) {
                $shipping = DB::table('menu_items')
                    ->where('menu_id', $menu->id)
                    ->whereNull('parent_id')
                    ->where('link_type', 'route')
                    ->where('route_name', 'shipping')
                    ->first(['id', 'label', 'css_class']);
            }

            if ($shipping) {
                $classes = preg_split('/\s+/', trim((string) $shipping->css_class)) ?: [];
                if (! in_array('np-shipping-action', $classes, true)) {
                    DB::table('menu_items')->where('id', $shipping->id)->update([
                        'css_class' => trim(implode(' ', array_filter([...$classes, 'np-shipping-action']))),
                        'updated_at' => now(),
                    ]);
                }
            } else {
                DB::table('menu_items')->insert([
                    'menu_id' => $menu->id,
                    'parent_id' => null,
                    'label' => 'USA SHIPPING',
                    'link_type' => 'route',
                    'category_id' => null,
                    'route_name' => 'shipping',
                    'url' => null,
                    'target' => '_self',
                    'css_class' => 'np-shipping-action',
                    'is_active' => true,
                    'sort_order' => ((int) DB::table('menu_items')->where('menu_id', $menu->id)->whereNull('parent_id')->max('sort_order')) + 10,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        // Invalidate persisted scalar navigation from every previous release.
        foreach (['v10', 'v11'] as $version) {
            Cache::forget("catalog.navigation.{$version}.header-primary");
        }
        Cache::forget('catalog.navigation.header-primary');
    }

    public function down(): void
    {
        // Keep admins' shipping edits and cleaned menu rows when rolling back.
        // This is a one-time data repair, not a schema change.
    }
};
