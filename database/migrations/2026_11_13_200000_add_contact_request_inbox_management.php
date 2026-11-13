<?php

use App\Support\AdminRbac;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table): void {
            $table->text('internal_note')->nullable();
            $table->index(['status', 'id'], 'contact_messages_status_id_index');
            $table->index(['topic', 'id'], 'contact_messages_topic_id_index');
        });

        // Register new, separate Commerce permissions without overwriting existing role choices.
        AdminRbac::syncDefaults(false);
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table): void {
            $table->dropIndex('contact_messages_status_id_index');
            $table->dropIndex('contact_messages_topic_id_index');
            $table->dropColumn('internal_note');
        });
        // Preserve admin permission grants on rollback.
    }
};
