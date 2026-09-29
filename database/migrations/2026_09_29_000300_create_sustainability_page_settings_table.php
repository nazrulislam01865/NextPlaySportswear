<?php

use App\Support\AdminRbac;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sustainability_page_settings')) {
            Schema::create('sustainability_page_settings', function (Blueprint $table): void {
                $table->id();
                $table->json('hero')->nullable();
                $table->json('approach')->nullable();
                $table->json('features')->nullable();
                $table->json('informed')->nullable();
                $table->json('cta')->nullable();
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
        Schema::dropIfExists('sustainability_page_settings');
    }
};
