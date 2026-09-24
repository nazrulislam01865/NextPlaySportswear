<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_methods')) {
            return;
        }

        $addIconPath = ! Schema::hasColumn('payment_methods', 'footer_icon_path');
        $addIconAlt = ! Schema::hasColumn('payment_methods', 'footer_icon_alt');
        $addShowInFooter = ! Schema::hasColumn('payment_methods', 'show_in_footer');

        if ($addIconPath || $addIconAlt || $addShowInFooter) {
            Schema::table('payment_methods', function (Blueprint $table) use ($addIconPath, $addIconAlt, $addShowInFooter): void {
                if ($addIconPath) {
                    $table->string('footer_icon_path')->nullable()->after('badge');
                }

                if ($addIconAlt) {
                    $table->string('footer_icon_alt', 180)->nullable()->after('footer_icon_path');
                }

                if ($addShowInFooter) {
                    $table->boolean('show_in_footer')->default(false)->after('is_active');
                }
            });
        }

        // Existing active online methods are sensible footer defaults. Manual
        // and inactive methods remain hidden until an admin explicitly enables
        // them, preventing the footer from advertising unavailable checkout
        // options.
        if (Schema::hasColumn('payment_methods', 'show_in_footer')) {
            DB::table('payment_methods')
                ->where('is_active', true)
                ->where('is_online', true)
                ->update(['show_in_footer' => true]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('payment_methods')) {
            return;
        }

        $columns = [];
        foreach (['footer_icon_path', 'footer_icon_alt', 'show_in_footer'] as $column) {
            if (Schema::hasColumn('payment_methods', $column)) {
                $columns[] = $column;
            }
        }

        if ($columns !== []) {
            Schema::table('payment_methods', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};
