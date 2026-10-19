<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('artwork_proofs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('status', 30)->default('pending_review');
            $table->string('design_name', 190)->nullable();
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type', 80);
            $table->unsignedBigInteger('file_size')->default(0);
            $table->text('designer_notes')->nullable();
            $table->text('customer_feedback')->nullable();
            $table->json('change_types')->nullable();
            $table->string('priority', 16)->nullable();
            $table->string('reference_path')->nullable();
            $table->string('reference_name')->nullable();
            $table->string('reference_mime_type', 80)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('revision_requested_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
            $table->unique(['order_item_id', 'version']);
            $table->index(['order_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('artwork_proof_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('artwork_proof_id')->constrained()->cascadeOnDelete();
            $table->string('sender_role', 16);
            $table->text('message');
            $table->timestamps();
            $table->index(['artwork_proof_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artwork_proof_messages');
        Schema::dropIfExists('artwork_proofs');
    }
};
