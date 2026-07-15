<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_vest_size_option_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 160);
            $table->string('slug', 180)->unique();
            $table->string('audience', 40);
            $table->longText('description_html')->nullable();
            $table->longText('chart_html')->nullable();
            $table->string('chart_title')->nullable();
            $table->text('chart_note')->nullable();
            $table->json('chart_columns')->nullable();
            $table->json('chart_rows')->nullable();
            $table->string('chart_image_path', 2048)->nullable();
            $table->string('chart_image_url', 2048)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'sort_order'], 'tvsog_active_sort_index');
        });

        Schema::create('training_vest_size_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('training_vest_size_option_group_id');
            $table->foreign(
                'training_vest_size_option_group_id',
                'tvso_group_fk'
            )
                ->references('id')
                ->on('training_vest_size_option_groups')
                ->cascadeOnDelete();
            $table->string('label', 80);
            $table->string('code', 80);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(
                ['training_vest_size_option_group_id', 'code'],
                'tvso_group_code_unique'
            );
            $table->index(
                ['training_vest_size_option_group_id', 'sort_order'],
                'tvso_group_sort_index'
            );
        });

        if (Schema::hasTable('training_vest_customization_options')) {
            $existingSizes = DB::table('training_vest_customization_options')
                ->where('type', 'size')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['name', 'slug', 'is_active', 'sort_order', 'created_by', 'updated_by']);

            if ($existingSizes->isNotEmpty()) {
                $now = now();
                $groupId = DB::table('training_vest_size_option_groups')->insertGetId([
                    'name' => 'Training Vest Sizes',
                    'slug' => 'training-vest-sizes',
                    'audience' => 'unisex',
                    'description_html' => null,
                    'chart_html' => null,
                    'chart_title' => null,
                    'chart_note' => null,
                    'chart_columns' => json_encode([]),
                    'chart_rows' => json_encode([]),
                    'chart_image_path' => null,
                    'chart_image_url' => null,
                    'is_active' => true,
                    'sort_order' => 0,
                    'created_by' => $existingSizes->first()->created_by,
                    'updated_by' => $existingSizes->first()->updated_by,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                foreach ($existingSizes as $index => $size) {
                    DB::table('training_vest_size_options')->insert([
                        'training_vest_size_option_group_id' => $groupId,
                        'label' => $size->name,
                        'code' => $size->slug ?: 'size-'.($index + 1),
                        'is_active' => (bool) $size->is_active,
                        'sort_order' => $index,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('training_vest_size_options');
        Schema::dropIfExists('training_vest_size_option_groups');
    }
};
