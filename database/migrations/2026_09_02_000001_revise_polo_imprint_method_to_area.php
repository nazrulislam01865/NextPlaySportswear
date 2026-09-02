<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_TYPE = 'polo_imprint_method_option';

    private const NEW_TYPE = 'polo_imprint_area_option';

    public function up(): void
    {
        $this->migrateType(self::OLD_TYPE, self::NEW_TYPE);
    }

    public function down(): void
    {
        $this->migrateType(self::NEW_TYPE, self::OLD_TYPE);
    }

    private function migrateType(string $from, string $to): void
    {
        if (! Schema::hasTable('jersey_customization_options')) {
            return;
        }

        DB::transaction(function () use ($from, $to): void {
            $sourceRows = DB::table('jersey_customization_options')
                ->where('type', $from)
                ->orderBy('id')
                ->get(['id', 'slug']);

            foreach ($sourceRows as $source) {
                $targetId = DB::table('jersey_customization_options')
                    ->where('type', $to)
                    ->where('slug', $source->slug)
                    ->value('id');

                if (! $targetId) {
                    DB::table('jersey_customization_options')
                        ->where('id', $source->id)
                        ->update(['type' => $to]);
                    continue;
                }

                $this->repointMasterOptionReferences((int) $source->id, (int) $targetId);
                DB::table('jersey_customization_options')->where('id', $source->id)->delete();
            }

            if (Schema::hasTable('product_option_groups') && Schema::hasColumn('product_option_groups', 'jersey_customization_type')) {
                DB::table('product_option_groups')
                    ->where('jersey_customization_type', $from)
                    ->update(['jersey_customization_type' => $to]);
            }
        });
    }

    private function repointMasterOptionReferences(int $sourceId, int $targetId): void
    {
        $referenceTables = [
            'product_option_values',
            'product_fabric_price_tables',
        ];

        foreach ($referenceTables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'jersey_customization_option_id')) {
                continue;
            }

            DB::table($table)
                ->where('jersey_customization_option_id', $sourceId)
                ->update(['jersey_customization_option_id' => $targetId]);
        }

        if (Schema::hasTable('jersey_customization_option_images')) {
            DB::table('jersey_customization_option_images')
                ->where('jersey_customization_option_id', $sourceId)
                ->update(['jersey_customization_option_id' => $targetId]);
        }
    }
};
