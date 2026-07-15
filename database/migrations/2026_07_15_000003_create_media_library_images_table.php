<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('media_library_images')) {
            Schema::create('media_library_images', function (Blueprint $table): void {
                $table->id();
                $table->string('path', 2048)->nullable();
                $table->string('url', 2048)->nullable();
                $table->string('name', 255);
                $table->string('alt_text', 255)->nullable();
                $table->string('mime_type', 120)->nullable();
                $table->unsignedBigInteger('size_bytes')->nullable();
                $table->unsignedInteger('width')->nullable();
                $table->unsignedInteger('height')->nullable();
                $table->string('source', 40)->default('upload');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['source', 'created_at']);
                $table->index('name');
            });
        }

        if (Schema::hasTable('product_images') && ! Schema::hasColumn('product_images', 'media_library_image_id')) {
            Schema::table('product_images', function (Blueprint $table): void {
                $table->foreignId('media_library_image_id')
                    ->nullable()
                    ->after('product_id')
                    ->constrained('media_library_images')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('product_images') && Schema::hasColumn('product_images', 'media_library_image_id')) {
            DB::table('product_images')
                ->whereNull('media_library_image_id')
                ->where(function ($query): void {
                    $query->whereNotNull('path')->orWhereNotNull('url');
                })
                ->orderBy('id')
                ->chunkById(100, function ($images): void {
                    foreach ($images as $image) {
                        $name = $image->alt_text ?: 'Product image';
                        $mediaId = DB::table('media_library_images')->insertGetId([
                            'path' => $image->path,
                            'url' => $image->url,
                            'name' => $name,
                            'alt_text' => $image->alt_text,
                            'source' => 'product',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        DB::table('product_images')
                            ->where('id', $image->id)
                            ->update(['media_library_image_id' => $mediaId]);
                    }
                });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_images') && Schema::hasColumn('product_images', 'media_library_image_id')) {
            Schema::table('product_images', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('media_library_image_id');
            });
        }

        Schema::dropIfExists('media_library_images');
    }
};
