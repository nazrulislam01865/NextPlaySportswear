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

        $section = DB::table('homepage_sections')->where('key', 'audience')->first(['id', 'items']);

        if (! $section || ! is_string($section->items) || $section->items === '') {
            return;
        }

        $items = json_decode($section->items, true);

        if (! is_array($items)) {
            return;
        }

        $changed = false;

        foreach ($items as &$item) {
            if (! is_array($item)) {
                continue;
            }

            if (($item['id'] ?? null) === 'men' && ($item['url'] ?? null) === '/products?q=men') {
                $item['url'] = '/men';
                $changed = true;
            }
        }
        unset($item);

        if ($changed) {
            DB::table('homepage_sections')
                ->where('id', $section->id)
                ->update([
                    'items' => json_encode($items, JSON_UNESCAPED_SLASHES),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('homepage_sections')) {
            return;
        }

        $section = DB::table('homepage_sections')->where('key', 'audience')->first(['id', 'items']);

        if (! $section || ! is_string($section->items) || $section->items === '') {
            return;
        }

        $items = json_decode($section->items, true);

        if (! is_array($items)) {
            return;
        }

        $changed = false;

        foreach ($items as &$item) {
            if (! is_array($item)) {
                continue;
            }

            if (($item['id'] ?? null) === 'men' && ($item['url'] ?? null) === '/men') {
                $item['url'] = '/products?q=men';
                $changed = true;
            }
        }
        unset($item);

        if ($changed) {
            DB::table('homepage_sections')
                ->where('id', $section->id)
                ->update([
                    'items' => json_encode($items, JSON_UNESCAPED_SLASHES),
                    'updated_at' => now(),
                ]);
        }
    }
};
