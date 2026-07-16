<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('newsletter_subscribers')) {
            return;
        }

        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email', 190)->unique();
            $table->string('status', 30)->default('active')->index();
            $table->string('source', 80)->nullable()->index();
            $table->timestamp('subscribed_at')->nullable()->index();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->char('ip_hash', 64)->nullable()->index();
            $table->char('user_agent_hash', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
    }
};
