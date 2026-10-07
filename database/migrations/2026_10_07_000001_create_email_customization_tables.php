<?php

use App\Support\AdminRbac;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_global_brandings')) {
            Schema::create('email_global_brandings', function (Blueprint $table): void {
                $table->id();
                $table->string('logo_path')->nullable();
                $table->string('header_bg_color', 30)->default('#0B2A4A');
                $table->string('button_color', 30)->default('#F15A2B');
                $table->string('font_family', 50)->default('Inter');
                $table->text('footer_text')->nullable();
                $table->string('support_email', 150)->default('support@nextplay.com');
                $table->string('support_phone', 50)->nullable()->default('+1 (888) 123-4567');
                $table->json('social_links')->nullable();
                $table->boolean('is_published')->default(true);
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('email_templates')) {
            Schema::create('email_templates', function (Blueprint $table): void {
                $table->id();
                $table->string('key', 80)->unique();
                $table->string('name', 150);
                $table->string('description', 255)->nullable();
                $table->string('trigger_event', 100);
                $table->string('icon', 50)->default('mail');
                $table->string('status', 30)->default('published');
                $table->string('active_version', 20)->default('v1.0');
                $table->string('draft_version', 20)->nullable();
                $table->string('subject', 200);
                $table->string('preheader_text', 255)->nullable();
                $table->string('heading', 200);
                $table->text('intro_message')->nullable();
                $table->string('cta_label', 100)->nullable();
                $table->string('cta_url_type', 50)->default('order_details');
                $table->string('cta_custom_url', 255)->nullable();
                $table->json('visibility_settings')->nullable();
                $table->json('blocks')->nullable();
                $table->json('sample_data')->nullable();
                $table->timestamp('published_at')->nullable();
                $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('email_template_versions')) {
            Schema::create('email_template_versions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('email_template_id')->constrained('email_templates')->cascadeOnDelete();
                $table->string('version', 20);
                $table->string('status', 30)->default('published');
                $table->string('subject', 200);
                $table->string('preheader_text', 255)->nullable();
                $table->string('heading', 200);
                $table->text('intro_message')->nullable();
                $table->string('cta_label', 100)->nullable();
                $table->string('cta_url_type', 50)->default('order_details');
                $table->string('cta_custom_url', 255)->nullable();
                $table->json('visibility_settings')->nullable();
                $table->json('blocks')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        AdminRbac::syncDefaults(false);
    }

    public function down(): void
    {
        Schema::dropIfExists('email_template_versions');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('email_global_brandings');
    }
};
