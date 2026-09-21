<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('storefront_settings')) {
            return;
        }

        if (DB::table('storefront_settings')->where('key', 'navigation')->exists()) {
            return;
        }

        $header = DB::table('storefront_settings')->where('key', 'header')->first();
        if (! $header) {
            return;
        }

        $settings = json_decode((string) $header->settings, true);
        $navigation = is_array($settings) ? ($settings['navigation'] ?? null) : null;

        if (! is_array($navigation) || ! isset($navigation['items']) || ! is_array($navigation['items'])) {
            return;
        }

        DB::table('storefront_settings')->insert([
            'key' => 'navigation',
            'settings' => json_encode($navigation, JSON_UNESCAPED_SLASHES),
            'updated_by' => $header->updated_by,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Intentionally keep the extracted navigation row on rollback.
        // Deleting it could destroy admin changes made after this migration.
    }
};
