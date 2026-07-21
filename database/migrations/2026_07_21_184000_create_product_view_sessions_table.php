<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_view_sessions')) {
            return;
        }

        Schema::create('product_view_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('visitor_key', 64);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['product_id', 'visitor_key'], 'product_view_sessions_product_visitor_unique');
            $table->index(['product_id', 'last_seen_at'], 'product_view_sessions_product_last_seen_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_view_sessions');
    }
};
