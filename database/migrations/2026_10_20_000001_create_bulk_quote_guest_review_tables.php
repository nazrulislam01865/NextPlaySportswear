<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bulk_quote_requests', function (Blueprint $table): void {
            $table->char('guest_access_hash', 64)->nullable();
            $table->unsignedInteger('quotation_version')->default(0);
            $table->json('quotation_details')->nullable();
            $table->date('quotation_valid_until')->nullable();
            $table->timestamp('quotation_sent_at')->nullable();
            $table->string('guest_decision', 30)->nullable();
            $table->foreignId('converted_order_id')->nullable()->constrained('orders')->nullOnDelete();
        });
        Schema::create('bulk_quote_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bulk_quote_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sender', 10);
            $table->string('kind', 30)->default('message');
            $table->text('body');
            $table->json('change_types')->nullable();
            $table->timestamps();
            $table->index(['bulk_quote_request_id', 'created_at']);
        });
        Schema::create('bulk_quote_files', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bulk_quote_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bulk_quote_message_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('category', 20)->default('quotation');
            $table->string('path');
            $table->string('original_name', 190);
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size');
            $table->timestamps();
            $table->index(['bulk_quote_request_id', 'category', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_quote_files');
        Schema::dropIfExists('bulk_quote_messages');
        Schema::table('bulk_quote_requests', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('converted_order_id');
            $table->dropColumn(['guest_access_hash', 'quotation_version', 'quotation_details', 'quotation_valid_until', 'quotation_sent_at', 'guest_decision']);
        });
    }
};
