<?php

use App\Models\AdminPermission;
use App\Support\AdminRbac;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        AdminRbac::syncDefaults(false);
    }

    public function down(): void
    {
        if (Schema::hasTable('admin_permissions')) {
            AdminPermission::query()->where('key', 'customers.view')->delete();
        }
    }
};
