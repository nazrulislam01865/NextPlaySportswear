<?php

use App\Models\SaleBanner;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sale_banners') || ! Schema::hasColumn('sale_banners', 'placements')) {
            return;
        }

        $valid = SaleBanner::PLACEMENTS;

        DB::table('sale_banners')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($valid): void {
                foreach ($rows as $row) {
                    $placements = is_array($row->placements ?? null)
                        ? $row->placements
                        : json_decode((string) ($row->placements ?? '[]'), true);

                    $placements = is_array($placements) ? $placements : [];
                    $normalized = array_values(array_unique(array_filter(
                        array_map(fn ($value): string => trim((string) $value), $placements),
                        fn (string $value): bool => in_array($value, $valid, true)
                    )));

                    DB::table('sale_banners')->where('id', $row->id)->update([
                        'placements' => json_encode($normalized, JSON_UNESCAPED_SLASHES),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Retired All Products placements are intentionally not restored.
    }
};
