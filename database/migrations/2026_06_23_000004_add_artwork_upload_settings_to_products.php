<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->boolean('artwork_upload_enabled')->default(false)->after('jersey_roster_fields');
            $table->boolean('artwork_upload_required')->default(false)->after('artwork_upload_enabled');
            $table->string('artwork_upload_title', 180)->nullable()->after('artwork_upload_required');
            $table->text('artwork_upload_description')->nullable()->after('artwork_upload_title');
            $table->unsignedTinyInteger('artwork_upload_max_files')->default(5)->after('artwork_upload_description');
            $table->unsignedSmallInteger('artwork_upload_max_file_size_mb')->default(15)->after('artwork_upload_max_files');
            $table->string('artwork_upload_accepted_types', 500)
                ->default('pdf,svg,png,jpg,jpeg,webp')
                ->after('artwork_upload_max_file_size_mb');
        });

        // Preserve the intent of products that previously offered an artwork method.
        if (Schema::hasTable('product_artwork_methods')) {
            DB::table('products')
                ->whereExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('product_artwork_methods')
                        ->whereColumn('product_artwork_methods.product_id', 'products.id')
                        ->where('product_artwork_methods.is_active', true);
                })
                ->update([
                    'artwork_upload_enabled' => true,
                    'artwork_upload_title' => 'Upload Custom Artwork',
                    'artwork_upload_description' => 'Upload one or more artwork files for the production team.',
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn([
                'artwork_upload_enabled',
                'artwork_upload_required',
                'artwork_upload_title',
                'artwork_upload_description',
                'artwork_upload_max_files',
                'artwork_upload_max_file_size_mb',
                'artwork_upload_accepted_types',
            ]);
        });
    }
};
