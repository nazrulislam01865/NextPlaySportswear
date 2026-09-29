<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_campaigns', function (Blueprint $table): void {
            $table->string('banner_heading', 255)->nullable()->after('banner_image_path');
            $table->string('banner_alt_text', 255)->nullable()->after('banner_heading');
            $table->string('banner_cta_label', 80)->nullable()->after('banner_alt_text');
            $table->string('banner_destination_link', 2048)->nullable()->after('banner_cta_label');
        });
    }

    public function down(): void
    {
        Schema::table('sale_campaigns', function (Blueprint $table): void {
            $table->dropColumn([
                'banner_heading',
                'banner_alt_text',
                'banner_cta_label',
                'banner_destination_link',
            ]);
        });
    }
};
