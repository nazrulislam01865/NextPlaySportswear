<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, string> */
    private const TYPE_MAP = [
        'color' => 'training_vest_color_option',
        'fabric' => 'training_vest_fabric_option',
        'vest_type' => 'training_vest_vest_type_option',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('jersey_customization_options')) {
            return;
        }

        $this->mirrorLegacyOptions();
        $this->mirrorSizeOptions();
    }

    public function down(): void
    {
        if (! Schema::hasTable('jersey_customization_options')) {
            return;
        }

        DB::table('jersey_customization_options')
            ->whereIn('type', [
                'training_vest_color_option',
                'training_vest_fabric_option',
                'training_vest_size_option',
                'training_vest_vest_type_option',
            ])
            ->delete();
    }

    private function mirrorLegacyOptions(): void
    {
        if (! Schema::hasTable('training_vest_customization_options')) {
            return;
        }

        $rows = DB::table('training_vest_customization_options')
            ->whereIn('type', array_keys(self::TYPE_MAP))
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $type = self::TYPE_MAP[$row->type] ?? null;
            if ($type === null) {
                continue;
            }

            DB::table('jersey_customization_options')->updateOrInsert(
                ['type' => $type, 'slug' => $row->slug],
                [
                    'name' => $row->name,
                    'color_hex' => $row->color_hex,
                    'description' => $row->description,
                    'is_active' => $row->is_active,
                    'sort_order' => $row->sort_order,
                    'created_by' => $row->created_by,
                    'updated_by' => $row->updated_by,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]
            );

            if (! Schema::hasTable('training_vest_customization_option_images') || ! Schema::hasTable('jersey_customization_option_images')) {
                continue;
            }

            $mirrorId = DB::table('jersey_customization_options')
                ->where('type', $type)
                ->where('slug', $row->slug)
                ->value('id');

            DB::table('jersey_customization_option_images')
                ->where('jersey_customization_option_id', $mirrorId)
                ->delete();

            $images = DB::table('training_vest_customization_option_images')
                ->where('training_vest_customization_option_id', $row->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            foreach ($images as $image) {
                DB::table('jersey_customization_option_images')->insert([
                    'jersey_customization_option_id' => $mirrorId,
                    'name' => $image->name,
                    'image_path' => $image->image_path,
                    'image_url' => $image->image_url,
                    'is_primary' => $image->is_primary,
                    'sort_order' => $image->sort_order,
                    'created_at' => $image->created_at,
                    'updated_at' => $image->updated_at,
                ]);
            }
        }
    }

    private function mirrorSizeOptions(): void
    {
        if (! Schema::hasTable('training_vest_size_option_groups') || ! Schema::hasTable('training_vest_size_options')) {
            return;
        }

        $groups = DB::table('training_vest_size_option_groups')->orderBy('id')->get();
        foreach ($groups as $group) {
            $sizes = DB::table('training_vest_size_options')
                ->where('training_vest_size_option_group_id', $group->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            foreach ($sizes as $size) {
                $slug = 'tvg-'.$group->id.'-'.strtolower(trim($size->code));
                DB::table('jersey_customization_options')->updateOrInsert(
                    ['type' => 'training_vest_size_option', 'slug' => $slug],
                    [
                        'name' => $group->name.' — '.$size->label,
                        'color_hex' => null,
                        'description' => filled($group->description_html) ? trim(strip_tags((string) $group->description_html)) : null,
                        'is_active' => (bool) $group->is_active && (bool) $size->is_active,
                        'sort_order' => ((int) $group->sort_order * 1000) + (int) $size->sort_order,
                        'created_by' => $group->created_by,
                        'updated_by' => $group->updated_by,
                        'created_at' => $size->created_at,
                        'updated_at' => $size->updated_at,
                    ]
                );
            }
        }
    }
};
