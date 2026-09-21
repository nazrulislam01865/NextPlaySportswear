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

        if (! Schema::hasTable('admin_permissions')) {
            return;
        }

        $legacyIds = DB::table('admin_permissions')
            ->whereIn('key', ['menus.view', 'menus.manage'])
            ->pluck('id');

        if (Schema::hasTable('admin_role_permissions') && $legacyIds->isNotEmpty()) {
            DB::table('admin_role_permissions')->whereIn('permission_id', $legacyIds)->delete();
        }

        if ($legacyIds->isNotEmpty()) {
            DB::table('admin_permissions')->whereIn('id', $legacyIds)->delete();
        }
    }

    public function down(): void
    {
        // The legacy admin feature is intentionally retired. Restoring it requires
        // restoring its code and then syncing permissions from that revision.
    }
};
