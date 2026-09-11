<?php

use App\Support\AdminRbac;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        AdminRbac::syncDefaults(false);
    }

    public function down(): void
    {
        if (! Schema::hasTable('admin_permissions')) {
            return;
        }

        $permissionIds = DB::table('admin_permissions')
            ->whereIn('key', ['media.view', 'media.manage'])
            ->pluck('id');

        if (Schema::hasTable('admin_role_permissions') && $permissionIds->isNotEmpty()) {
            DB::table('admin_role_permissions')->whereIn('permission_id', $permissionIds)->delete();
        }

        DB::table('admin_permissions')->whereIn('id', $permissionIds)->delete();
    }
};
