<?php

use App\Support\AdminRbac;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_detail_ui_settings')) {
            Schema::create('product_detail_ui_settings', function (Blueprint $table): void {
                $table->id();
                $table->json('settings')->nullable();
                $table->timestamps();
            });
        }

        AdminRbac::syncDefaults(false);
    }

    public function down(): void
    {
        Schema::dropIfExists('product_detail_ui_settings');
    }
};
