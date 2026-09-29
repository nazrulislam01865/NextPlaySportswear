<?php

use App\Support\AdminRbac;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('about_page_settings')) {
            Schema::create('about_page_settings', function (Blueprint $table): void {
                $table->id();
                $table->json('hero')->nullable();
                $table->json('introduction')->nullable();
                $table->json('what_we_do')->nullable();
                $table->json('how_we_work')->nullable();
                $table->json('gallery')->nullable();
                $table->json('cta')->nullable();
                $table->json('help')->nullable();
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
        Schema::dropIfExists('about_page_settings');
    }
};
