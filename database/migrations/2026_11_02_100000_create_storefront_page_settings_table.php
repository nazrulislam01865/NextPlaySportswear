<?php

use App\Support\AdminRbac;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('storefront_page_settings')) {
            Schema::create('storefront_page_settings', function (Blueprint $table): void {
                $table->id();
                $table->string('page_key', 64)->unique();
                $table->string('title')->nullable();
                $table->json('content')->nullable();
                $table->json('seo')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        AdminRbac::syncDefaults(false);
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_page_settings');
    }
};

