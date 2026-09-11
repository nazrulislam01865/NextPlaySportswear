<?php

use App\Models\AdminPermission;
use App\Support\AdminRbac;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'suspended_at')) {
                $table->timestamp('suspended_at')->nullable()->after('auth_session_version');
            }

            if (! Schema::hasColumn('users', 'suspension_reason')) {
                $table->string('suspension_reason', 500)->nullable()->after('suspended_at');
            }

            if (! Schema::hasColumn('users', 'suspended_by')) {
                $table->foreignId('suspended_by')
                    ->nullable()
                    ->after('suspension_reason')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'reactivated_at')) {
                $table->timestamp('reactivated_at')->nullable()->after('suspended_by');
            }

            if (! Schema::hasColumn('users', 'reactivated_by')) {
                $table->foreignId('reactivated_by')
                    ->nullable()
                    ->after('reactivated_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        AdminRbac::syncDefaults(false);
    }

    public function down(): void
    {
        if (Schema::hasTable('admin_permissions')) {
            AdminPermission::query()->where('key', 'customers.manage')->delete();
        }

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'reactivated_by')) {
                $table->dropConstrainedForeignId('reactivated_by');
            }
            if (Schema::hasColumn('users', 'reactivated_at')) {
                $table->dropColumn('reactivated_at');
            }
            if (Schema::hasColumn('users', 'suspended_by')) {
                $table->dropConstrainedForeignId('suspended_by');
            }
            if (Schema::hasColumn('users', 'suspension_reason')) {
                $table->dropColumn('suspension_reason');
            }
            if (Schema::hasColumn('users', 'suspended_at')) {
                $table->dropColumn('suspended_at');
            }
        });
    }
};
