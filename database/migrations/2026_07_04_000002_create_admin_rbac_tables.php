<?php

use App\Support\AdminRbac;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_roles')) {
            Schema::create('admin_roles', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_system')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('admin_permissions')) {
            Schema::create('admin_permissions', function (Blueprint $table): void {
                $table->id();
                $table->string('key')->unique();
                $table->string('module')->index();
                $table->string('action', 40)->default('View')->index();
                $table->string('label');
                $table->text('description')->nullable();
                $table->string('route_name')->nullable()->index();
                $table->unsignedInteger('sort_order')->default(0)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('admin_role_permissions')) {
            Schema::create('admin_role_permissions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('role_id')->constrained('admin_roles')->cascadeOnDelete();
                $table->foreignId('permission_id')->constrained('admin_permissions')->cascadeOnDelete();
                $table->boolean('allowed')->default(false);
                $table->timestamps();

                $table->unique(['role_id', 'permission_id']);
            });
        }

        AdminRbac::syncDefaults(true);
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_role_permissions');
        Schema::dropIfExists('admin_permissions');
        Schema::dropIfExists('admin_roles');
    }
};
