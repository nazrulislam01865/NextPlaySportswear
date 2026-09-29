<?php

use App\Support\PromotionBannerPlacement;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sale_campaigns')) {
            Schema::table('sale_campaigns', function (Blueprint $table): void {
                if (! Schema::hasColumn('sale_campaigns', 'banner_mobile_image_path')) {
                    $table->string('banner_mobile_image_path')->nullable()->after('banner_image_path');
                }
                if (! Schema::hasColumn('sale_campaigns', 'banner_placements')) {
                    $table->json('banner_placements')->nullable()->after('banner_mobile_image_path');
                }
            });

            DB::table('sale_campaigns')
                ->whereNotNull('banner_image_path')
                ->whereNull('banner_placements')
                ->update([
                    'banner_placements' => json_encode([PromotionBannerPlacement::SALE_TOP], JSON_UNESCAPED_SLASHES),
                ]);
        }

        if (! Schema::hasTable('sale_banners') || ! Schema::hasColumn('sale_banners', 'placements')) {
            return;
        }

        $aliases = [
            'sale_after_row_2' => PromotionBannerPlacement::SALE_MIDDLE,
            'product_top' => PromotionBannerPlacement::ALL_PRODUCTS_TOP,
            'product_after_row_2' => PromotionBannerPlacement::ALL_PRODUCTS_MIDDLE,
        ];

        DB::table('sale_banners')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($aliases): void {
                foreach ($rows as $row) {
                    $placements = is_array($row->placements ?? null)
                        ? $row->placements
                        : json_decode((string) ($row->placements ?? '[]'), true);
                    $placements = is_array($placements) ? $placements : [];

                    $normalized = [];
                    foreach ($placements as $placement) {
                        $placement = trim((string) $placement);
                        $placement = $aliases[$placement] ?? $placement;
                        if (in_array($placement, PromotionBannerPlacement::ALL, true) && ! in_array($placement, $normalized, true)) {
                            $normalized[] = $placement;
                        }
                    }

                    DB::table('sale_banners')->where('id', $row->id)->update([
                        'placements' => json_encode($normalized, JSON_UNESCAPED_SLASHES),
                    ]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sale_campaigns')) {
            return;
        }

        Schema::table('sale_campaigns', function (Blueprint $table): void {
            if (Schema::hasColumn('sale_campaigns', 'banner_placements')) {
                $table->dropColumn('banner_placements');
            }
            if (Schema::hasColumn('sale_campaigns', 'banner_mobile_image_path')) {
                $table->dropColumn('banner_mobile_image_path');
            }
        });
    }
};
