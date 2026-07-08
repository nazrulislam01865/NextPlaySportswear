<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('size_option_groups') && ! Schema::hasColumn('size_option_groups', 'customization_group')) {
            Schema::table('size_option_groups', function (Blueprint $table): void {
                $table->string('customization_group', 60)->default('jersey')->after('slug');
            });
        }

        if (Schema::hasTable('size_option_groups')) {
            DB::table('size_option_groups')
                ->whereNull('customization_group')
                ->orWhere('customization_group', '')
                ->update(['customization_group' => 'jersey']);

            try {
                Schema::table('size_option_groups', function (Blueprint $table): void {
                    $table->dropUnique('size_option_groups_slug_unique');
                });
            } catch (Throwable) {
                // Some existing databases may already have this index changed.
            }

            try {
                Schema::table('size_option_groups', function (Blueprint $table): void {
                    $table->unique(['customization_group', 'slug'], 'sog_customization_group_slug_uq');
                    $table->index(['customization_group', 'audience', 'is_active', 'sort_order'], 'sog_group_audience_active_sort_idx');
                });
            } catch (Throwable) {
                // Keep deployment safe if an index already exists from a previous run.
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('size_option_groups') || ! Schema::hasColumn('size_option_groups', 'customization_group')) {
            return;
        }

        try {
            Schema::table('size_option_groups', function (Blueprint $table): void {
                $table->dropUnique('sog_customization_group_slug_uq');
                $table->dropIndex('sog_group_audience_active_sort_idx');
            });
        } catch (Throwable) {
            // Ignore missing indexes during rollback.
        }

        Schema::table('size_option_groups', function (Blueprint $table): void {
            $table->dropColumn('customization_group');
        });

        try {
            Schema::table('size_option_groups', function (Blueprint $table): void {
                $table->unique('slug');
            });
        } catch (Throwable) {
            // Existing duplicate slugs across groups can prevent restoring the old global unique index.
        }
    }
};
