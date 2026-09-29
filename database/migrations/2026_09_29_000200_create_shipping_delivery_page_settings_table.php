<?php

use App\Support\AdminRbac;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shipping_delivery_page_settings')) {
            Schema::create('shipping_delivery_page_settings', function (Blueprint $table): void {
                $table->id();
                $table->json('hero')->nullable();
                $table->json('tabs')->nullable();
                $table->json('delivery_intro')->nullable();
                $table->json('info_cards')->nullable();
                $table->json('delivery_steps')->nullable();
                $table->json('notice')->nullable();
                $table->json('faqs')->nullable();
                $table->json('address_checklist')->nullable();
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
        Schema::dropIfExists('shipping_delivery_page_settings');
    }
};
