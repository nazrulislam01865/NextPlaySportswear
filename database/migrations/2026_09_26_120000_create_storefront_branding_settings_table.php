<?php

use App\Support\AdminRbac;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('storefront_branding_settings')) {
            Schema::create('storefront_branding_settings', function (Blueprint $table): void {
                $table->id();
                $table->text('logo_path')->nullable();
                $table->timestamps();
            });
        }

        AdminRbac::syncDefaults(false);
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_branding_settings');
    }
};
