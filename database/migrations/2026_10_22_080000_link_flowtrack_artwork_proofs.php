<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('artwork_proofs', function (Blueprint $table): void {
            $table->unsignedBigInteger('flowtrack_job_id')->nullable()->after('order_item_id');
            $table->unsignedSmallInteger('flowtrack_slot')->nullable()->after('flowtrack_job_id');
            $table->unsignedBigInteger('flowtrack_document_id')->nullable()->after('flowtrack_job_id');
            $table->unsignedBigInteger('flowtrack_revision_id')->default(0)->after('flowtrack_document_id');
            $table->unique(['order_id', 'flowtrack_document_id', 'flowtrack_revision_id'], 'artwork_flowtrack_document_revision_unique');
            $table->index(['order_item_id', 'flowtrack_slot', 'version'], 'artwork_proof_slot_version_idx');
        });
        Schema::table('artwork_proof_messages', function (Blueprint $table): void {
            $table->unsignedBigInteger('flowtrack_activity_id')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('artwork_proof_messages', fn (Blueprint $table) => $table->dropColumn('flowtrack_activity_id'));
        Schema::table('artwork_proofs', function (Blueprint $table): void {
            $table->dropUnique('artwork_flowtrack_document_revision_unique');
            $table->dropIndex('artwork_proof_slot_version_idx');
            $table->dropColumn(['flowtrack_job_id', 'flowtrack_slot', 'flowtrack_document_id', 'flowtrack_revision_id']);
        });
    }
};
